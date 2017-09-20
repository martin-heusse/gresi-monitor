let nbMeterDone=0;
let myLabels=[];
let myData=[];

function retrieveMeters(listLoc,dataLoc) {
    // Get the list of serial numbers and plot the data
    $.getJSON(listLoc, function(result){
	    let i=0;
	    //remove this!!!
	    result.list.push(result.list[0]);
	    //end remove this
	    console.log(result.list);
	    for (i=0;i<result.list.length;i++){
		retrieveData(result.list[i],dataLoc,result.list.length) ;
	    }
	});
}
function retrieveData(serialNum,dataLoc,nbMeters) {
    $.getJSON(dataLoc+serialNum, function(result){
	    console.log(serialNum);
	    let ithMeterData=[];
	    myLabels=[];
	    result.records.forEach(function(item) {
		    myLabels.push(item.measureDate); // overriding previous ones, but it's always the same thing
		    ithMeterData.push(nbMeterDone==0?item.measure:item.measure/2); // remove ternary !!
		});  

	    myData.push({label: serialNum,
			//	backgroundColor: 'none',
			borderColor: `hsl(${(50*nbMeterDone)%360}, 100%,50%)`,
			data: ithMeterData
			});
	    nbMeterDone++;
	    console.log(nbMeterDone);
	    if(nbMeterDone==nbMeters){
		doPlot();
	    }
	});
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
		    yAxes: [{
			    scaleLabel: {
				display: true,
				labelString: 'Wh'
			    }
			}]
		}
	    }
	});

}

// This is the function that triggers everything
$( document ).ready(function() {
	retrieveMeters( document.getElementById("listUrl").value,document.getElementById("dataUrl").value);
    });


