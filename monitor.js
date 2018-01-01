let nbMeterDone=0,nbMeters=0,nbMetersOK=0;
let myLabels=[];
let myData=[];
let zc={zoomChart:null}; // holds the pointer to the zoom chart object, to destroy it when needed
let mc={mainChart:null}; // holds the pointer to the main chart object
let meterNames=[]; // serial -> name
let peakPower=[]; // serial -> peak_power
let endDate=null;
let nbProdDone=0;nbProd=0;
let prodString="";

function convertTS(ts){
    // Create a new JavaScript Date object based on the timestamp
    // multiplied by 1000 so that the argument is in milliseconds, not seconds.
    let date = new Date(ts*1000);
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
        console.log(element.serial);
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
            console.log(result[i]);
            meterNames[result[i].serial]=result[i].name;
            peakPower[result[i].serial]=result[i].peak_power;
            setTimeout(retrieveData, i*20,result[i],dataLoc,destCtx,null);  // setTimeout programs the calls to retrieveData once/second, in order to comply with rbeesolar policy
        }
    });
}

function tsfromEndDate(endDate){
    let ts = Math.round((new Date()).getTime() / 1000);
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

function retrieveData(serialInfo,dataLoc,destCtx,zc) {
    console.log("zc: "+zc);
    serialNum=serialInfo.serial;
    whToW=1; // This is for Wh to W conversion when step is 1h...
//http://localhost/~heusse/Monitor/get1h.php?serial=216670215&start=1512814200&end=1512823800

    let ts = tsfromEndDate(endDate);

    if(zc){ //zc==null means main graph
        nbDays=3;
        whToW=6;
    }
    else{
        nbDays=7;
    }
    let myUrl=dataLoc+"?serial="+serialNum+"&start="+(ts-nbDays*24*3600)+"&end="+ts;
    console.log(myUrl);
    $.getJSON(myUrl, function(result){
        if(result.length>0){
            // Process the data that was just fetched
            let ithMeterData=[];
            myLabels=[];
            result.forEach(function(item) {
                myLabels.push(convertTS(item.ts)); // overriding previous ones, but it's always the same thing, as ensured by the php call
                wToWc=(!zc)?1/peakPower[result[0].serial]:1; // W / Wc in main  graph, kW in the other
                ithMeterData.push(Math.round(item.prod*whToW*wToWc)/1000);
                });

            myData.push({label: meterNames[result[0].serial], // ""+result[0].serial+" "+ 
                //  backgroundColor: 'none',
                borderColor: `hsl(${Math.round((nbMeterDone)/(nbMeters)*360)+45}, 100%,50%)`,
//                 pointBorderWidth: peakPower[result[0].serial]/12,
//                 borderWidth:1,
                pointBorderWidth: 1,
                borderWidth:peakPower[result[0].serial]/12,
                data: ithMeterData
                });
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
    console.log(nbMeterDone);
    document.getElementById('progress').innerHTML+=statusChar;
    // Once all the data is in myData array, plot it
    if(nbMeterDone==nbMeters){
        document.getElementById('progress').innerHTML=nbMetersOK+" compteur(s) récupéré(s) !";
        document.getElementById('progressEnd').innerHTML="";
        $("#zoomSelect").prop('disabled', false);
        if(zc==null)
            mc.mainChart=doPlot(destCtx,1); 
        else
            zc.zoomChart=doPlot(destCtx,0);
    }

}

function doPlot(destCtx,isMainPlot){
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
        gcd.style.height="35vh";
        updateMainChart(8);
        window.scrollTo(0,Math.round($("#zoomChart").offset().top));
    }
    else{
        gcd.style.height="";
        updateMainChart(12);
        window.scrollTo(0,0);
    }
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
                console.log(x + " " + result[x]);
                prodString+="<TR><TD>";
                prodString+= " "+ meterNames[x] + " </TD><TD> "+ Math.round(result[x]/1000)  +" kWh </TD></TR>";
            }
            nbProdDone++;
            if(nbProdDone==nbProd){
                console.log("Done prod");
                prodString+="</TABLE>";
                $("#MonthlyProd")[0].innerHTML+=prodString;
                console.log( prodString ) ;
            }
        })
    }
}

// This is the function that triggers everything
$( document ).ready(function() {
    endDate=new Date($("#enddate")[0].value);
//     console.log(endDate.getTime());

    // The URLs are in 2 hidden elements in the HTML
    retrieveMeters( document.getElementById("listUrl").value,document.getElementById("dataUrl1h").value);
    $("#zoomSelect").change(zoomSelected);
    // let endDate=new Date($("#enddate").value);
        
    });

