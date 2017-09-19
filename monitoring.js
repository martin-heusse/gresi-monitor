function retrieveCounters(listLoc,dataLoc) {
    // Get the list of serial numbers and plot the data
    $.getJSON(listLoc, function(result){
		    retrieveData(result.list[0],dataLoc) ;
	});
}
function retrieveData(serialNum,dataLoc) {
    $.getJSON(dataLoc+serialNum, function(result){
	    let myLabels=[];
	    let myData=[];
	    console.log(serialNum);
	    result.records.forEach(function(item) {
		    myLabels.push(item.measureDate);
		    myData.push(item.measure);
		});  
	    let ctx = document.getElementById('myChart').getContext('2d');
	    let chart = new Chart(ctx, {
		    // The type of chart we want to create
		    type: 'line',
		    
		    // The data for our dataset
		    data: {
			labels:myLabels,
			datasets: [{
				label: serialNum,
				backgroundColor: 'yellow',
				borderColor: 'black',
				data: myData
			    }]
		    },
		    
		    // Configuration options go here
		    options: {}
		});
	});
}


$( document ).ready(function() {
	retrieveCounters( document.getElementById("listUrl").value,document.getElementById("dataUrl").value);
    });


