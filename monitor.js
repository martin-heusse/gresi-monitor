let nbMeterDone=0,nbMeters=0,nbMetersOK=0;
let myLabels=[];
let myTs=[];
let myData=[];
let zc={zoomChart:null}; // holds the pointer to the zoom chart object, to destroy it when needed
let mc={mainChart:null}; // holds the pointer to the main chart object
let meterNames={}; // family_serial -> name (see functions makeMeterKey(), splitMeterKey())
let peakPower={}; // serial -> peak_power
let endDate=null;
let nbProdDone=0;nbProd=0;totalProdMonth=0
let prodArray=[];
let prodString="";

let zoomNbDays=0.7;
let mainNbDays=7;
let mainNbDays10mn=0.7;
let lastShownHour=20; // !! UTC
let threshAdjustDate=6;// if last ts in db is before threshAdjustDate oclock in the morning, adjust date
// Retun a string for displaying the date of the provided timestamp
function convertTS(ts){
    // Create a new JavaScript Date object based on the timestamp
    // multiplied by 1000 so that the argument is in milliseconds, not seconds.
    let dbdate = new Date(ts*1000);
    let tzOffset=dbdate.getTimezoneOffset();
    let date=new Date(ts*1000+tzOffset*60*1000);
    let day = "" +date.getDate();
    let month = ""+(date.getMonth()+1);
//     let year = ""+date.getFullYear();
    // Hours part from the timestamp
    let hours = date.getHours();
    // Minutes part from the timestamp
    let minutes = "0" + date.getMinutes();
    // Seconds part from the timestamp
//     let seconds = "0" + date.getSeconds();

    // Will display time in 10:30:23 format
//     return day +"/" + month + "/"+year+ " "+ hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
    return day +"/" + month + " "+ hours + "h" + ':' + minutes.substr(-2) ;
//     return date.toLocaleString();
}

function makeMeterKey(family,serial){return family.toString()+"_"+serial.toString()}
function splitMeterKey(key){return {family:key.split('_')[0],serial:key.split('_')[1]};}

function prepareZoom(listCounters){
    let myOptions="<option></option>";
    let zoomMeterInPost=document.getElementById("postzoommeter").value;
    listCounters.forEach(function(element) {
        let isSelected=(element==zoomMeterInPost)?"selected":"";
        myOptions+="<option value="+element+" "+isSelected+">"+meterNames[element]+"</option>";
        });
    document.getElementById('zoomSelect').innerHTML=myOptions;
}

function retrieveMeters(listLoc,dataLoc) {
    // Get the list of serial numbers and call retrieveData() to eventually plot the data
    $.getJSON(listLoc, function(result){
        let i=0;
        let lastTsInData=0;
        // Meters to consider if in $_GET... (copied into hidden meterstoconsider by php)
        let metersToShow=document.getElementById('meterstoconsider').value.split(",");

        document.getElementById('progress').innerHTML="Récupération des données";
        document.getElementById('progressEnd').innerHTML=">";

        let destCtx=document.getElementById("globalChart").getContext('2d');
        let lasttsCorrected = 0;
        for (i=0;i<result.length;i++){
            // Select the meters with a name matching the requested ones (or all meters if empty list)
            if(metersToShow[0].length==0 || metersToShow.findIndex(function(cur){return result[i].name.search(cur)>=0})>=0){
                nbMeters++;
                meterNames[makeMeterKey(result[i].family,result[i].serial)]=result[i].name;
                peakPower[result[i].serial]=result[i].peak_power;
                lasttsCorrected= parseInt(result[i].lastts)+ parseInt(result[i].timeoffset);
                if (lasttsCorrected>lastTsInData) lastTsInData=lasttsCorrected;
                setTimeout(retrieveData, i*1,result[i],dataLoc,destCtx,null);  // setTimeout paces the calls to the web API, here each ms
            }
        }
        //Adjust the enddate according to lastTs from the meters
        console.log("lastTsInData:"+convertTS(lastTsInData));
        console.log("limit:"+convertTS(tsfromEndDate(endDate)+(-lastShownHour+threshAdjustDate)*3600));
        if(lastTsInData<tsfromEndDate(endDate)+(-lastShownHour+threshAdjustDate)*3600){
            let d=new Date((lastTsInData)*1000);
            if(d.getHours()<threshAdjustDate) d=new Date((lastTsInData-24*3600)*1000);
            console.log(d);
            endDate=new Date(Date.UTC(d.getFullYear(),d.getMonth(),d.getDate(), 0, 0, 0));
            console.log("endDate set: "+endDate);
            $("#enddate")[0].value="";
        }
        prepareZoom(Object.keys(meterNames));
        displayMonthly(endDate,Object.keys(meterNames));

    });
}

function tsfromEndDate(endDate){
    if (endDate.getTime()>0){
        return Math.round(endDate.getTime()/1000)+lastShownHour*3600;
    }
    else {
        let d = new Date(Date.now());
        let thisIstheEnd=Date.UTC(d.getFullYear(),d.getMonth(),d.getDate(), lastShownHour, 0, 0);
        return Math.round(thisIstheEnd/ 1000);
    }
}

function updateMainChart(fontsize){
    mc.mainChart.options.legend.labels.fontSize=fontsize;
    mc.mainChart.options.scales.xAxes[0].ticks.fontSize=fontsize;
    mc.mainChart.update();
    mc.mainChart.resize();
}

function adjustTime(measureArray){
  //"result" is in UTC time zone, irrad is in eastern europe time zone !!
  let adjustedArray = measureArray.map(function callback(currentValue){
                          let ed = new Date(currentValue.ts*1000);
                          let tzOffset=ed.getTimezoneOffset();
                          ed=ed.valueOf()-tzOffset*60*1000;
                          let edUtc=new Date(ed);
                          currentValue.ts=edUtc/1000;
                          return currentValue;
                          });
  return adjustedArray;
}

function colIndexFor(serialString){
    namesArray=[];
    for (let prop in meterNames) {
        if (meterNames.hasOwnProperty(prop)) { 
            namesArray.push(meterNames[prop]);
        }
    }
    return namesArray.sort().indexOf(meterNames[serialString]);
}

/**
 * Compute the sun radiation for a given solar panel at a given time
 *
 * @param LAT {number} latitude of the location
 * @param betta {number} inclination of the panel
 * @param gamma {number} azimuth of the panel
 * @param Dh {number} optimal irradiance of the panel
 * @param date {Date} date of the calculation
 * @returns {number} the radiation
 */
function computeSunRadiation(LAT, betta, gamma, Dh, date) {
    // Get current hours (H) and minutes (m)
    const H = date.getHours();
    const m = date.getMinutes();

    // Get number of days passed since the beginning of the year
    const begin = new Date(date.getFullYear(), 0, 1);
    const d = Math.ceil((date - begin) / 86_400_000); // 3600 * 24 * 1000

    // Compute angle time (AH)
    const EoT = 229.18 * (0.000075 + 0.001868 * Math.cos(d) - 0.032077 * Math.sin(d) - 0.014615 * Math.cos(2 * d) - 0.040849 * Math.sin(2 * d));
    const LongV = 0; // TODO
    const time = (H + m / 60) /*+ (EoT + LongV)*/;
    const AH = (15 * (time - 12)) * (Math.PI / 180);

    // Compute earth declination (delta)
    const delta = Math.asin(
        Math.sin(
            -23.44 * (Math.PI / 180)
        ) *
        Math.cos(
            ((2 * Math.PI) / 365.24) * (d + 10) +
            ((2 * Math.PI) / Math.PI) * 0.0167 * Math.sin(
                ((2 * Math.PI) / 365.24) * (d - 2)
            )
        )
    );

    // Compute sun elevation (HS)
    const HS = Math.asin(
        (Math.sin(LAT) * Math.sin(delta)) +
        (Math.cos(LAT) * Math.cos(delta) * Math.cos(AH))
    );

    // Compute sun azimuth (AZ)
    let A = Math.acos(
        (Math.sin(delta) * Math.cos(LAT) - Math.cos(delta) * Math.sin(LAT) * Math.cos(AH)) /
        Math.cos(HS)
    );
    if (AH > 0)
        A = 2 * Math.PI - A;
    const AZ = A;

    // Compute incidence angle (alpha)
    const alpha = Math.acos((Math.cos(betta) * Math.sin(HS)) + (Math.sin(betta) * Math.cos(HS) * Math.cos(AZ - gamma)));

    return Math.cos(alpha) * Dh;
}

function getTheoricOutput(zc) {
    let theoric = [];
    myTs.forEach((ts) => {
        // TODO : get the real latitude of the solar panel + betta and gamma + Dh
        const sr = computeSunRadiation(45 * (Math.PI / 180),
            20 * (Math.PI / 180),
            250 * (Math.PI / 180),
            100,
            new Date(ts * 1000 - 3600 * 1000));
        //theoric.push(sr)
        theoric.push(sr < 0 ? 0 : sr);
    });

    zc.zoomChart.data.datasets.push({
        label: 'Theoric output',
        fill: false,
        borderColor: 'grey',
        data: theoric
    });

    let optimal = [];
    myTs.forEach((ts) => {
        const sr = computeSunRadiation(45 * (Math.PI / 180),
            45 * (Math.PI / 180),
            180 * (Math.PI / 180),
            100,
            new Date(ts * 1000 - 3600 * 1000));
        optimal.push(sr < 0 ? 0 : sr);
    });

    zc.zoomChart.data.datasets.push({
        label: 'Optimal output',
        fill: false,
        borderColor: 'green',
        data: optimal
    });

    zc.zoomChart.update();
}

function retrieveData(serialInfo,dataLoc,destCtx,zc) {
    let serialNum=serialInfo.serial;
    let family=serialInfo.family;
    let whToW=1; // This is for Wh to W conversion when step is 1h...
//http://localhost/~heusse/Monitor/get1h.php?serial=216670215&start=1512814200&end=1512823800
    let borderDash=[];
    let bkgdCol='rgba(0, 0, 0, 0.03)';
    let ts = tsfromEndDate(endDate);

    if(zc){ //zc==null means main graph
        nbDays=zoomNbDays;
        whToW=6;
    }
    else{
        if($("#radio1h").prop( "checked" ))
          nbDays=mainNbDays;
        else{
          nbDays=mainNbDays10mn;
          whToW=6;
        }
    }
    let myUrl=dataLoc+"?family="+family+"&serial="+serialNum+"&start="
              +(ts-nbDays*24*3600)+"&end="+ts;
    console.log(myUrl);
    $.getJSON(myUrl, function(result){
        if(result.length>0){
            // Process the data that was just fetched
            let ithMeterData=[];
            let colIndex=colIndexFor(makeMeterKey(family, serialNum));
            if(myLabels.length<result.length) { // only override labels if more data in this set
                myLabels = [];
                myTs = [];
            }
            if(zc || !$("#radio1h").prop( "checked" ))
              result=adjustTime(result);
            result.forEach(function(item) {
                if(myLabels.length<result.length) {
                    myLabels.push(convertTS(item.ts)); // overriding previous ones, if there is more data
                    myTs.push(item.ts);
                }
                wToWc=(!zc)?1/peakPower[serialNum]:1; // W / Wc in main  graph, kW in the other
                if(item.prod>=0)ithMeterData.push(Math.round(item.prod*whToW*wToWc)/1000);
                else ithMeterData.push(null);
                });
            let max=ithMeterData.reduce(function(a, b) {
                    if (a != null || b != null)
                        return Math.max(a, b);
                    else return null;
                });
            if(max===0){
                borderDash=[2,4];
                bkgdCol='rgba(150, 0, 0, 1)';
            }
            else if(max===null){
                borderDash=[2,4];
            }

            let newData={label: meterNames[makeMeterKey(family, serialNum)], // ""+resultAdj[0].serial+" "+  
                borderColor: `hsl(${Math.round((1+colIndex)/(nbMeters)*360)+45}, 100%,50%)`,
//                 pointBorderWidth: peakPower[serialNum]/12,
//                 borderWidth:1,
                pointBorderWidth: 0.8,
                borderWidth:peakPower[serialNum]/12,
                borderDash:borderDash,
                data: ithMeterData,
                backgroundColor:bkgdCol,
                family: family,
                serial: serialNum
                };
            if(zc)
                myData.push(newData);
            else
                myData.push(newData);
        }
        nbMetersOK++;
        dataRetrieved("+",destCtx,zc);
    })
    .fail(function() {
        console.log( "error retrieve");
        dataRetrieved("x",destCtx,zc);
    });
}

function orderDataEntries(a,b){return (colIndexFor(makeMeterKey(a.family,a.serial))>colIndexFor(makeMeterKey(b.family,b.serial)));}

function dataRetrieved(statusChar,destCtx,zc){
    nbMeterDone++;
    document.getElementById('progress').innerHTML+=statusChar;
    // Once all the data is in myData array, plot it
    if(nbMeterDone==nbMeters){
        myData.sort(orderDataEntries);
        console.log(myData);
        document.getElementById('progress').innerHTML=nbMetersOK+" compteur(s) récupéré(s) !";
        document.getElementById('progressEnd').innerHTML="";
        $("#zoomSelect").prop('disabled', false);
        if(zc==null){
            trimData();
            mc.mainChart=doPlot(destCtx,1); 
            if(document.getElementById("postzoommeter").value.length>0){
                zoomSelected();
            }
        }
        else{
            zc.zoomChart=doPlot(destCtx,0);
            retrieveIrrad(zc);
            retrieveRef(zc);
            getTheoricOutput(zc);
        }
    }

}

function dataIndexLeft(dat){
  var i;
  for (i=0;i<dat.length;i++){
    if(dat[i]>=0.001){
      return i;
    }
  }
  return dat.length;
}
function dataIndexRight(dat){
  var i;
  for (i=dat.length-1;i>=0;i--){
    if(dat[i]>=0.001){
      return i;
    }
  }
  return 0;
}

function trimData(){
  // Find index of first non null data, last non null data
  let indexBoundaries=myData.reduce(function(cur,b){
      leftmin= Math.min(cur.lm,dataIndexLeft(b.data));
      rightmax= Math.max(cur.rm,dataIndexRight(b.data));
      return {lm:leftmin,rm:rightmax};
  },{lm:myLabels.length,rm:0});
  //adjust
  console.log(indexBoundaries)
  console.log(myLabels.length)
  if(indexBoundaries.lm>1) --indexBoundaries.lm;
  if(indexBoundaries.rm<=myLabels.length-3) indexBoundaries.rm=indexBoundaries.rm+2;
  console.log(indexBoundaries)
  // trim labels, data
  myLabels.splice(0,indexBoundaries.lm);
  myLabels.splice(indexBoundaries.rm-indexBoundaries.lm,myLabels.length);
  myData.forEach(function(item){item.data.splice(0,indexBoundaries.lm);
                                item.data.splice(indexBoundaries.rm-indexBoundaries.lm,item.length)});
}

function doPlot(destCtx,isMainPlot){
    if (myData.length==0){
        let chart = new Chart(destCtx, {
            options: {
                title: {
                  display: true,
                  text: '>>>> Pas de données (pour cette date ?) <<<<'
                }
            }
        });
        return chart;
    }
    let chart = new Chart(destCtx, {
        // The type of chart we want to create
        type: 'line',

        // The data for our dataset
        data: {
        labels:myLabels, // This is always the same -> take the last one
        datasets: myData
        },

        // Configuration options go here
        options: {
            maintainAspectRatio: false,
            scales: {
            // Add label on Y axes
            yAxes: [{
                scaleLabel: {
                display: true,
                labelString: isMainPlot?'W / Wc':'kW'
                }
                }]
            },
            elements: {line: {cubicInterpolationMode: 'monotone'}}
        }
    });
    return chart;
}

function zoomSelected(){
    let destCtx=document.getElementById("zoomChart").getContext('2d');
    let familySerial=splitMeterKey(document.getElementById("zoomSelect").value);
    serialName=document.getElementById("zoomSelect").name;
    nbMeters=1;nbMeterDone=0;nbMetersOK=0
    myLabels=[];
    myData=[];
    if(zc.zoomChart){
        zc.zoomChart.destroy();
    }
    document.getElementById('progress').innerHTML="Récupération des données";
    retrieveData({family:familySerial.family,serial:familySerial.serial,name:serialName},document.getElementById("dataUrl10mn").value,destCtx,zc);

    // reduce size of main chart
    gcd=document.getElementById("globalChartDiv");
    if(familySerial.serial>0){
        gcd.style.height="35vh";
        updateMainChart(8);
//         window.scrollTo(0,Math.round($("#zoomSelect").offset().top));
    }
    else{
        gcd.style.height="70vh";
        updateMainChart(12);
//         window.scrollTo(0,0);
    }
}

function hideAll(){
    mc.mainChart.data.datasets.forEach(function(ds) {
      ds.hidden = !ds.hidden;
    });
    mc.mainChart.update();
}

function GenerateURL(){
    let listShown=[]
    mc.mainChart.data.datasets.forEach(function(ds) {
        if(ds.hidden != true){
            if(ds._meta[0].hidden != true)
                listShown.push(ds.label);
        }
        if(ds.hidden == true){
            if(ds._meta[0].hidden == false)
                listShown.push(ds.label);
        }
    });
    window.open(window.location.href.split("?")[0]+"?meters="+listShown.join());
}


function findTSMatching(element){
    //"this" is date label from the zoom chart
    return 0==this.localeCompare(convertTS(element.ts-1800));
}

function retrieveIrrad(zc){
    if(! $("#irradBox").prop( "checked" ))
        return;
    // so what meter are we talking about? No need to check for family, as only one has irrad data
    serialNum=splitMeterKey(document.getElementById("zoomSelect").value).serial;
    // where is the data?
    irradLoc=document.getElementById("dataUrl1h").value;
    //when?
    let ts = tsfromEndDate(endDate);
    let nbDays=zoomNbDays;
    let myUrl=irradLoc+"?serial="+serialNum+"&start="+(ts-nbDays*24*3600)+"&end="+ts;
    $.getJSON(myUrl, function(result){
            chartData=zc.zoomChart.data;
            //console.log(chartData); //chartData.labels chartData.datasets[]
//             console.log(result); //chartData.labels chartData.datasets[]
            irradData=[]

            for (var i = 0, len = chartData.labels.length; i < len; i++) {
                let irradIndex=result.findIndex(findTSMatching,chartData.labels[i]);
                if(irradIndex>0){
                    irradData.push(result[irradIndex].irrad * peakPower[serialNum]/1000 );
                }
                else{
                    irradData.push(null);
                }
            }
            chartData.datasets.push({label:"Satellite",
                                     borderColor: "red",
                                     pointBorderWidth: 0.75,
                                     borderWidth:1,
                                     pointStyle:"star",
                                     pointRadius:6,
                                     data:irradData});
            zc.zoomChart.update();
        });
}

function retrieveRef(zc){
    // so what meter are we talking about?
    let famser=splitMeterKey(document.getElementById("zoomSelect").value);
    let serialFamily=famser.family;
    let serialNum=famser.serial;
    if($("#zoomenddate")[0].value.length==0 || serialNum.length==0)
      return;
    // where is the data?
    dataLoc=document.getElementById("dataUrl10mn").value;
    //when?
    let ts = tsfromEndDate(new Date(Date.parse($("#zoomenddate")[0].value)));
    let nbDays=zoomNbDays;
    let whToW=6;

    let myUrl=dataLoc+"?family="+serialFamily+"&serial="+serialNum+"&start="+(ts-nbDays*24*3600)+"&end="+ts;
    $.getJSON(myUrl, function(result){
            chartData=zc.zoomChart.data;
            console.log("chartdatalenght="+chartData.labels.length)
            //console.log(chartData); //chartData.labels chartData.datasets[]
//             console.log(result); //chartData.labels chartData.datasets[]
            refData=[];
            result=adjustTime(result);
            for (var i = 0, len = chartData.labels.length, lenr = result.length; i < len && i<lenr; i++) {
                refData.push(result[i].prod*whToW/1000);
            }
            chartData.datasets.push({label:"Référence",
                                     borderColor: "blue",
                                     pointBorderWidth: 0,
                                     borderWidth:0.5,
                                     data:refData});
            zc.zoomChart.update();
        });
}

function displayMonthly(endDate,meterList){
    let ts = tsfromEndDate(endDate)-3601;// go back in time 1h, to not fall on the next month when it's the last day
    console.log("displayMontlhy"+ts);
    let myDate=new Date(ts*1000);
    prodString = "<B>Productions pour le mois "+ (myDate.getMonth()+1) + "/"+ myDate.getFullYear() +"</B> (kWh)<BR/>";
    prodString +=  '<table class="dm">';
    nbProd=meterList.length;
    let familySerial={};
    for (i=0;i<nbProd;i++){
        familySerial=splitMeterKey(meterList[i]);
        let myUrl=document.getElementById("dataUrlMonth").value + "?family=" + familySerial.family + "&serial=" + familySerial.serial +"&end=" +ts;
        console.log(myUrl);
        $.getJSON(myUrl, function(result){
            for (x in result){ //only one iteration / x is the meter key (<family>_<serial>)
                if(result[x]>0){
                  prodArray.push({name:meterNames[x],prod:Math.round(result[x]/1000)});
                  totalProdMonth+=result[x]/1000;
                }
            }
            nbProdDone++;
            if(nbProdDone==nbProd){
                prodArray.sort(function(a, b){return a.name.localeCompare(b.name);});
                console.log(prodArray);
                for (j=0;j<prodArray.length;j++){
                    prodString+="<TR><TD>";
                    prodString+= " "+ prodArray[j].name + " </TD><TD align='right'> "+ prodArray[j].prod  +"</TD></TR>";
                }
                prodString+="<TR><TD>";
                prodString+= " <B>Total</B> </TD><TD align='right'> "+ Math.round(totalProdMonth)  +" </TD></TR>";
                prodString+="</TABLE>";
                $("#MonthlyProd")[0].innerHTML+=prodString;
            }
        })
    }
}

// This is the function that triggers everything
$( document ).ready(function() {
    endDate=new Date($("#enddate")[0].value);
//     console.log(endDate.getTime());

    // The URLs are in 2 hidden elements in the HTML
    let dataLoc=($("#radio1h").prop( "checked" ))?document.getElementById("dataUrl1h").value:document.getElementById("dataUrl10mn").value;
    retrieveMeters( document.getElementById("listUrl").value,dataLoc);
    $("#zoomSelect").change(zoomSelected);
    $("#irradBox").change(zoomSelected);
    $("#zoomenddate").change(zoomSelected);
    $("#hideAll").click(hideAll);
    $("#GenerateURL").click(GenerateURL);

    // reset previous values of irradiation and ref date in zoom
    if(document.getElementById("postirradbox").value.length>0){
        document.getElementById("irradBox").checked = true;
    }
    if(document.getElementById("postzoomenddate").value.length>0){
        document.getElementById("zoomenddate").value = document.getElementById("postzoomenddate").value;
    }
        
    });

