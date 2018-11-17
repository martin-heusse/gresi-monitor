const prodURL="http://gresi21.fr/monitor/getWidgetSummary.php";

const starttable="<SPAN style='display:inline-table'><TABLE style='width:22em; border: 2px double black; border-collapse: collapse;font-family: Sans-Serif;'><TR>";
const endtable="</TR></TABLE></SPAN>";

const timespan={month:"ce mois ci",year:"cette année",total:"totale"};

let allcontent;
console.log(document.getElementById("serialNumber"));
if(document.getElementById("serialNumber")!=null){
  allcontent="<SPAN style='font-family: Sans-Serif'>Production de cette station et de Gr&eacute;si21</SPAN><BR>";
}
else{
  allcontent="<SPAN style='font-family: Sans-Serif'>Production de Gr&eacute;si21</SPAN><BR>";
}

function addCommas(nStr) {
    nStr += '';
    const rgx = /(\d+)(\d{3})/;
    while (rgx.test(nStr)) {
        nStr = nStr.replace(rgx, '$1' + ' ' + '$2');
    }
    return nStr;
}

function printProd(res){
  let content=starttable;
  console.log(res);
  for (let p in res){
    let prod=Math.round(res[p]);
    content+="<TD style='border: 1px solid black; padding:0.5em;'> "+timespan[p]+":<BR> <SPAN style='float:right'><B>"+addCommas(prod.toFixed(0))+"</B> kWh</TD>";
  }
  content+=endtable;
  console.log(content);
  allcontent+=content;
  document.getElementById("prodwidget").innerHTML=allcontent; 
}

console.log(prodURL);
fetch(prodURL)
.then(data=>{return data.json()})
.then(res=>{printProd(res)});

//S'il y a un élément d'id serialNumber, alors on donne la prod pour ce serial
if(document.getElementById("serialNumber")){
  let prodURLs=prodURL+"?serial="+document.getElementById("serialNumber").value;
  console.log(prodURLs);
  fetch(prodURLs)
  .then(data1=>{return data1.json()})
  .then(res1=>{printProd(res1)});
}

