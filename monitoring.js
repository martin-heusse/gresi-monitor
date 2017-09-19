

function retrieveData(dataLoc) {
    $.getJSON(dataLoc, function(result){
	    let myLabels=[];
	    let myData=[];
	    result.records.forEach(function(item) {
		    myLabels.push(item.measureDate);
		    myData.push(item.measure);
		});  
	    console.log(result);
	    var ctx = document.getElementById('myChart').getContext('2d');
	    var chart = new Chart(ctx, {
		    // The type of chart we want to create
		    type: 'line',
		    
		    // The data for our dataset
		    data: {
			labels:myLabels,
			datasets: [{
				label: "First counter",
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
	retrieveData( document.getElementById("dataUrl").value);
    });


