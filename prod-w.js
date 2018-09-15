const prodURL="http://gresi21.fr/monitor/getWidgetSummary.php";

const starttable="<TABLE style='border: 2px double black; border-collapse: collapse;font-family: Sans-Serif;'><TR>";
const endtable="</TR></TABLE>";

const timespan={month:"du mois en cours",year:"depuis un an",total:"totale"};

function addCommas(nStr) {
    nStr += '';
    const rgx = /(\d+)(\d{3})/;
    while (rgx.test(nStr)) {
        nStr = nStr.replace(rgx, '$1' + '.' + '$2');
    }
    return nStr;
}

function printProd(res){
  let content="<SPAN style='font-family: Sans-Serif'>Production Gr&eacute;si21</SPAN><BR>"+starttable;
  console.log(res);
  for (let p in res){
    let prod=Math.round(res[p]);
    content+="<TD style='border: 1px solid black; padding:0.5em;'> "+timespan[p]+":<BR> <SPAN style='float:right'><B>"+addCommas(prod.toFixed(0))+"</B> kWh</TD>";
  }
  content+=endtable;
  console.log(content);  
  document.getElementById("prodwidget").innerHTML=content; 
}

fetch(prodURL)
.then(data=>{return data.json()})
.then(res=>{printProd(res)});

