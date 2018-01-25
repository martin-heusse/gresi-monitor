let nbMeterDone=0,nbMeters=0,nbMetersOK=0;
let myLabels=[];
let myData=[];
let zc={zoomChart:null}; // holds the pointer to the zoom chart object, to destroy it when needed
let mc={mainChart:null}; // holds the pointer to the main chart object
let meterNames={}; // serial -> name
let peakPower={}; // serial -> peak_power
let endDate=null;
let nbProdDone=0;nbProd=0;
let prodString="";

let zoomNbDays=0.8;
let mainNbDays=7;
let mainNbDays10mn=1.75;

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
//     let minutes = "0" + date.getMinutes();
    // Seconds part from the timestamp
//     let seconds = "0" + date.getSeconds();

    // Will display time in 10:30:23 format
//     return day +"/" + month + "/"+year+ " "+ hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
    return day +"/" + month + " "+ hours + "h" ;
//     return date.toLocaleString();
}

function prepareZoom(listCounters){
    myOptions="<option></option>";
    listCounters.forEach(function(element) {
        myOptions+="<option value="+element.serial+">"+element.name+"</option>";
        });
    document.getElementById('zoomSelect').innerHTML=myOptions;
}

function retrieveMeters(listLoc,dataLoc) {
    // Get the list of serial numbers and call retrieveData() to eventually plot the data
    $.getJSON(listLoc, function(result){
        let i=0;
        console.log(result);
        prepareZoom(result);
        displayMonthly(endDate,result);


        document.getElementById('progress').innerHTML="Récupération des données";
        document.getElementById('progressEnd').innerHTML=">";

        let destCtx=document.getElementById("globalChart").getContext('2d');
        nbMeters=result.length;

        for (i=0;i<result.length;i++){
            meterNames[result[i].serial]=result[i].name;
            peakPower[result[i].serial]=result[i].peak_power;
            setTimeout(retrieveData, i*20,result[i],dataLoc,destCtx,null);  // setTimeout programs the calls to retrieveData once/second, in order to comply with rbeesolar policy
        }
    });
}

function tsfromEndDate(endDate){
    let d = new Date(Date.now()+24*3600*1000);
    midnight=Date.UTC(d.getFullYear(),d.getMonth(),d.getDate(), 0, 0, 0);
    let ts = Math.round(midnight/ 1000);
    if (endDate.getTime()>0){
        ts=Math.round(endDate.getTime()/1000)+24*3600;
    }
    return ts;
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

function retrieveData(serialInfo,dataLoc,destCtx,zc) {
    console.log("zc: "+zc);
    let serialNum=serialInfo.serial;
    let whToW=1; // This is for Wh to W conversion when step is 1h...
//http://localhost/~heusse/Monitor/get1h.php?serial=216670215&start=1512814200&end=1512823800
    let borderDash=[];
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
    let myUrl=dataLoc+"?serial="+serialNum+"&start="+(ts-nbDays*24*3600)+"&end="+ts;
    console.log(myUrl);
    $.getJSON(myUrl, function(result){
        if(result.length>0){
            // Process the data that was just fetched
            let ithMeterData=[];
            let colIndex=(Object.keys(meterNames)).indexOf(result[0].serial.toString());
            myLabels=[];
            if(zc || !$("#radio1h").prop( "checked" ))
              result=adjustTime(result);
            result.forEach(function(item) {
                myLabels.push(convertTS(item.ts)); // overriding previous ones, but it's always the same thing, as ensured by the php call
                wToWc=(!zc)?1/peakPower[result[0].serial]:1; // W / Wc in main  graph, kW in the other
                ithMeterData.push(Math.round(item.prod*whToW*wToWc)/1000);
                });
            let max=ithMeterData.reduce(function(a, b) {
                    return Math.max(a, b);
                });
            if(max==0){
                borderDash=[2,4];
            }
            let newData={label: meterNames[result[0].serial], // ""+resultAdj[0].serial+" "+  
                borderColor: `hsl(${Math.round((1+colIndex)/(nbMeters)*360)+45}, 100%,50%)`,
//                 pointBorderWidth: peakPower[result[0].serial]/12,
//                 borderWidth:1,
                pointBorderWidth: 0.2,
                borderWidth:peakPower[result[0].serial]/12,
                borderDash:borderDash,
                data: ithMeterData,
                backgroundColor:'rgba(0, 0, 0, 0.05)'
                };
            if(zc)
                myData.push(newData);
            else
                myData[colIndex]=newData;
        }
        nbMetersOK++;
        dataRetrieved("+",destCtx,zc);
    })
    .fail(function() {
        console.log( "error retrieve");
        dataRetrieved("x",destCtx,zc);
    });
}

function dataRetrieved(statusChar,destCtx,zc){
    nbMeterDone++;
    document.getElementById('progress').innerHTML+=statusChar;
    // Once all the data is in myData array, plot it
    if(nbMeterDone==nbMeters){
        document.getElementById('progress').innerHTML=nbMetersOK+" compteur(s) récupéré(s) !";
        document.getElementById('progressEnd').innerHTML="";
        $("#zoomSelect").prop('disabled', false);
        if(zc==null)
            mc.mainChart=doPlot(destCtx,1); 
        else{
            zc.zoomChart=doPlot(destCtx,0);
            retrieveIrrad(zc);
            retrieveRef(zc);
        }
    }

}

function doPlot(destCtx,isMainPlot){
    if (myData.length==0){
        console
        let chart = new Chart(destCtx, {
            options: {
                title: {
                  display: true,
                  text: '>>>> Pas de données pour cette date <<<<'
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
    let serialNum=0;
    let destCtx=document.getElementById("zoomChart").getContext('2d');
    serialNum=document.getElementById("zoomSelect").value;
    serialName=document.getElementById("zoomSelect").name;
    nbMeters=1;nbMeterDone=0;nbMetersOK=0
    myLabels=[];
    myData=[];
    if(zc.zoomChart){
        zc.zoomChart.destroy();
    }
    document.getElementById('progress').innerHTML="Récupération des données";
    retrieveData({serial:serialNum,name:serialName},document.getElementById("dataUrl10mn").value,destCtx,zc);

    // reduce size of main chart
    gcd=document.getElementById("globalChartDiv");
    if(serialNum>0){
//         gcd.style.height="35vh";
//         updateMainChart(8);
        window.scrollTo(0,Math.round($("#zoomSelect").offset().top));
    }
    else{
//         gcd.style.height="";
//         updateMainChart(12);
        window.scrollTo(0,0);
    }
}

function findTSMatching(element){
    //"this" is date label from the zoom chart
    return 0==this.localeCompare(convertTS(element.ts-1800));
}

function retrieveIrrad(zc){
    if(! $("#irradBox").prop( "checked" ))
        return;
    // so what meter are we talking about?
    serialNum=document.getElementById("zoomSelect").value;
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
                                     pointBorderWidth: 0,
                                     borderWidth:1,
                                     data:irradData});
            zc.zoomChart.update();
        });
}

function retrieveRef(zc){
    // so what meter are we talking about?
    serialNum=document.getElementById("zoomSelect").value;
    if($("#zoomenddate")[0].value.length==0 || serialNum.length==0)
      return;
    // where is the data?
    dataLoc=document.getElementById("dataUrl10mn").value;
    //when?
    let ts = tsfromEndDate(new Date(Date.parse($("#zoomenddate")[0].value)));
    let nbDays=zoomNbDays;
    let whToW=6;

    let myUrl=dataLoc+"?serial="+serialNum+"&start="+(ts-nbDays*24*3600)+"&end="+ts;
    $.getJSON(myUrl, function(result){
            chartData=zc.zoomChart.data;
            console.log(result);
            console.log("chartdatalenght="+chartData.labels.length)
            //console.log(chartData); //chartData.labels chartData.datasets[]
//             console.log(result); //chartData.labels chartData.datasets[]
            refData=[];
            result=adjustTime(result);
            for (var i = 0, len = chartData.labels.length, lenr = result.length; i < len && i<lenr; i++) {
                refData.push(result[i].prod*whToW/1000);
            }
            chartData.datasets.push({label:"ref",
                                     borderColor: "blue",
                                     pointBorderWidth: 0,
                                     borderWidth:0.5,
                                     data:refData});
            zc.zoomChart.update();
        });
}

function displayMonthly(endDate,counterList){
    let ts = tsfromEndDate(endDate)-3601;// go back in time 1h, to not fall on the next month when it's the last day
    console.log("displayMontly"+ts);
    let myDate=new Date(ts*1000);
    prodString = "Production pour le mois "+ (myDate.getMonth()+1) + "/"+ myDate.getFullYear() +"<BR>";
    prodString +=  '<table class="dm">';
    nbProd=counterList.length;
    for (i=0;i<nbProd;i++){
        let myUrl=document.getElementById("dataUrlMonth").value+"?serial="+counterList[i].serial+"&end="+ts;
        $.getJSON(myUrl, function(result){
            for (x in result){ //only one iteration / x is the serial
                prodString+="<TR><TD>";
                prodString+= " "+ meterNames[x] + " </TD><TD> "+ Math.round(result[x]/1000)  +" kWh </TD></TR>";
            }
            nbProdDone++;
            if(nbProdDone==nbProd){
                console.log("Done prod");
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
    // let endDate=new Date($("#enddate").value);
        
    });

