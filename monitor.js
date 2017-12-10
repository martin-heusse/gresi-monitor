let nbMeterDone=0,nbMeters=0,nbMetersOK=0;
let myLabels=[];
let myData=[];
let zc={zoomChart:null}; // holds the pointer to the zoom chart object, to destroy it when needed
let myMeters=[];

function convertTS(ts){
    // Create a new JavaScript Date object based on the timestamp
    // multiplied by 1000 so that the argument is in milliseconds, not seconds.
    let date = new Date(ts*1000);
    let day = "" +date.getDate();
    let month = ""+date.getMonth();
    let year = ""+date.getFullYear();
    // Hours part from the timestamp
    let hours = date.getHours();
    // Minutes part from the timestamp
    let minutes = "0" + date.getMinutes();
    // Seconds part from the timestamp
    let seconds = "0" + date.getSeconds();

    // Will display time in 10:30:23 format
    return day +"/" + month + "/"+year+ " "+ hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
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
        prepareZoom(result);

        document.getElementById('progress').innerHTML="Récupération des données";
        document.getElementById('progressEnd').innerHTML=">";

        let destCtx=document.getElementById("globalChart").getContext('2d');
        nbMeters=result.length;

        for (i=0;i<result.length;i++){
            myMeters[result[i].serial]=result[i].name;
            setTimeout(retrieveData, i*20,result[i],dataLoc,destCtx,null);  // setTimeout programs the calls to retrieveData once/second, in order to comply with rbeesolar policy
        }
    });
}
function retrieveData(serialInfo,dataLoc,destCtx,zc) {
    serialNum=serialInfo.serial;
//http://localhost/~heusse/Monitor/getIrrad.php?serial=216670215&start=1512814200&end=1512823800
    let ts = Math.round((new Date()).getTime() / 1000);
    myUrl=dataLoc+"?serial="+serialNum+"&start="+(ts-5*24*3600)+"&end="+ts;
    console.log(myUrl);
    $.getJSON(myUrl, function(result){
        // Process the data that was just fetched
        let ithMeterData=[];
        myLabels=[];
        result.forEach(function(item) {
            myLabels.push(convertTS(item.ts)); // overriding previous ones, but it's always the same thing
            ithMeterData.push(item.prod);
        });

        myData.push({label: ""+result[0].serial+" "+myMeters[result[0].serial], //using serialNum here gives funny results, since the variable can have a different value!!
            //  backgroundColor: 'none',
            borderColor: `hsl(${(50*nbMeterDone)%360}, 100%,50%)`,
            data: ithMeterData
            });
        nbMetersOK++;
        dataRetrieved("+",destCtx,zc);
    })
    .fail(function() {
        console.log( "error" + serialNum);
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
        zc.zoomChart=doPlot(destCtx);
    }

}

function doPlot(destCtx){
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
            scales: {
            // Add label on Y axes
            yAxes: [{
                scaleLabel: {
                display: true,
                labelString: 'Wh'
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
}

// This is the function that triggers everything
$( document ).ready(function() {
    // The URLs are in 2 hidden elements in the HTML
    retrieveMeters( document.getElementById("listUrl").value,document.getElementById("dataUrl1h").value);
    $("#zoomSelect").change(zoomSelected);
    });

