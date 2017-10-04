let nbMeterDone=0,nbMeters=0,nbMetersOK=0;
let myLabels=[];
let myData=[];

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
            nbMeters=result.list.length;
            setTimeout(retrieveData, i*1000,result.list[i],dataLoc,"globalChart");  // setTimeout programs the calls to retrieveData once/second, in order to comply with rbeesolar policy
        }
    });
}
function retrieveData(serialNum,dataLoc,destCanvas) {
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
        dataRetrieved("+",destCanvas);
    })
    .fail(function() {
        console.log( "error" + serialNum);
        dataRetrieved("x",destCanvas);
    });
}

function dataRetrieved(statusChar,destCanvas){
    nbMeterDone++;
    console.log(nbMeterDone);
    document.getElementById('progress').innerHTML+=statusChar;
    // Once all the data is in myData array, plot it
    if(nbMeterDone==nbMeters){
        document.getElementById('progress').innerHTML=nbMetersOK+" compteur(s) récupéré(s) !";
        document.getElementById('progressEnd').innerHTML="";
        doPlot(destCanvas);
    }

}

function doPlot(theChart){
    let ctx = document.getElementById(theChart).getContext('2d');
    let chart = new Chart(ctx, {
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

}

// This is the function that triggers everything
$( document ).ready(function() {
    // The URLs are in 2 hidden elements in the HTML
    retrieveMeters( document.getElementById("listUrl").value,document.getElementById("dataUrl1h").value);
    });

function zoomSelected(){
    let serialNum=0;
    console.log(serialNum=document.getElementById("zoomSelect").value);
    nbMeters=1;nbMeterDone=0;nbMetersOK=0
    myLabels=[];
    myData=[];
    document.getElementById('progress').innerHTML="Récupération des données";
    retrieveData(serialNum,document.getElementById("dataUrl10mn").value,"zoomChart");
}
