let nbMeterDone=0,nbMeters=0,nbMetersOK=0;
let myLabels=[];
let myData=[];
let zc={zoomChart:null}; // holds the pointer to the zoom chart object, to destroy it when needed

function prepareZoom(listCounters){
    console.log(listCounters);
    myOptions="";
    listCounters.forEach(function(element) {
        myOptions+="<option>"+element+"</option>";
        });
    document.getElementById('zoomSelect').innerHTML=myOptions;
}

function retrieveMeters(listLoc,dataLoc) {
    // Get the list of serial numbers and call retrieveData() to eventually plot the data
    $.getJSON(listLoc, function(result){
        let i=0;
        prepareZoom(result.list);

        document.getElementById('progress').innerHTML="Récupération des données";
        document.getElementById('progressEnd').innerHTML=">";

        for (i=0;i<result.list.length;i++){
            let destCtx=document.getElementById("globalChart").getContext('2d');
            nbMeters=result.list.length;
            setTimeout(retrieveData, i*1000,result.list[i],dataLoc,destCtx,null);  // setTimeout programs the calls to retrieveData once/second, in order to comply with rbeesolar policy
        }
    });
}
function retrieveData(serialNum,dataLoc,destCtx,zc) {
    $.getJSON(dataLoc+serialNum, function(result){
        // Process the data that was just fetched
        console.log(serialNum);
        let ithMeterData=[];
        myLabels=[];
        result.records.forEach(function(item) {
            myLabels.push(item.measureDate); // overriding previous ones, but it's always the same thing
            ithMeterData.push(item.measure);
        });

        myData.push({label: serialNum,
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
    nbMeters=1;nbMeterDone=0;nbMetersOK=0
    myLabels=[];
    myData=[];
    if(zc.zoomChart){
        zc.zoomChart.destroy();
    }
    document.getElementById('progress').innerHTML="Récupération des données";
    retrieveData(serialNum,document.getElementById("dataUrl10mn").value,destCtx,zc);
}

// This is the function that triggers everything
$( document ).ready(function() {
    // The URLs are in 2 hidden elements in the HTML
    retrieveMeters( document.getElementById("listUrl").value,document.getElementById("dataUrl1h").value);
    $("#zoomSelect").change(zoomSelected);
    });

