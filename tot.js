let nbYears=10;

function computeSum(colNum){
  let sum=0;
  $(".prod"+colNum).each(function() {
    let value = $(this).text();
    // add only if the value is number
    if(!isNaN(value) && value.length != 0) {
          sum += parseFloat(value);
    }
  })
  return (Math.round(sum*10)/10).toFixed(1);
}

$(document).ready(function(){
$('.totProd').each(function() {
    this.innerHTML=computeSum(this.id);
});
});
