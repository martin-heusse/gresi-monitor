let nbMeterDone=0,nbMeters=0,nbMetersOK=0;
let myLabels=[];
let myData=[];
let zc={zoomChart:null}; // holds the pointer to the zoom chart object, to destroy it when needed

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

        for (i=0;i<result.length;i++){
            nbMeters=result.length;
            setTimeout(retrieveData, i*20,result[i],dataLoc,destCtx,null);  // setTimeout programs the calls to retrieveData once/second, in order to comply with rbeesolar policy
        }
    });
}
function retrieveData(serialInfo,dataLoc,destCtx,zc) {
    serialNum=serialInfo.serial;
//http://localhost/~heusse/Monitor/getIrrad.php?serial=216670215&start=1512814200&end=1512823800
    var ts = Math.round((new Date()).getTime() / 1000);
    myUrl=dataLoc+"?serial="+serialNum+"&start="+(ts-5*24*3600)+"&end="+ts;
    console.log(myUrl);
    $.getJSON(myUrl, function(result){
        // Process the data that was just fetched
        console.log(result);
        let ithMeterData=[];
        myLabels=[];
        result.forEach(function(item) {
            myLabels.push(item.ts); // overriding previous ones, but it's always the same thing
            ithMeterData.push(item.prod);
        });

        myData.push({label: result[0].serial, //using serialNum here gives funny results, since the variable can have a different value!!
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

