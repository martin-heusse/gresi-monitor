let nbMeterDone=0,nbMeters=0,nbMetersOK=0;
let myLabels=[];
let myData=[];

function retrieveMeters(listLoc,dataLoc) {
    // Get the list of serial numbers and call retrieveData() to eventually plot the data
    $.getJSON(listLoc, function(result){
        let i=0;
            console.log(result.list);
        for (i=0;i<result.list.length;i++){
            nbMeters=result.list.length;
            setTimeout(retrieveData, i*1000,result.list[i],dataLoc);  // setTimeout programs the calls to retrieveData once/second, in order to comply with rbeesolar policy
        }
    });
}
function retrieveData(serialNum,dataLoc) {
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
        dataRetrieved("+");
    })
    .fail(function() {
        console.log( "error" + serialNum);
        dataRetrieved("x");
    });
}

function dataRetrieved(statusChar){
    nbMeterDone++;
    console.log(nbMeterDone);
    document.getElementById('progress').innerHTML+=statusChar;
    // Once all the data is in myData array, plot it
    if(nbMeterDone==nbMeters){
        document.getElementById('status').innerHTML=nbMetersOK+" compteurs récupérés !";
        doPlot();
    }

}

function doPlot(){
    let ctx = document.getElementById('myChart').getContext('2d');
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
    retrieveMeters( document.getElementById("listUrl").value,document.getElementById("dataUrl").value);
    });


