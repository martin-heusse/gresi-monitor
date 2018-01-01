<?php
function pageHeader($pageDesc){
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<meta http-equiv="Content-Language" content="fr" />
		<title>'
	. $pageDesc .'</title>

      <STYLE type="text/css">
      DIV.sc {font-family: Sans-Serif;}
      DIV.ib {display: inline;}
      DIV.plot{ margin-left: auto;
                margin-right: auto;}
      DIV.chartClass{border-style: solid;
                width:95vw;
                height:60vh;
                margin-bottom:6px}
      TABLE.dm{width:40%;}
     </STYLE>


	</head>

	<body>';
}

function pageFoot(){
	echo "</body></html>\r";
}

function header_form($filename){
    echo "<form method=\"post\" action=\"$filename\">";
}


function connect_to_db(){
    $db_url="mysql:host=".db_host.";dbname=".db_name.";charset=utf8";
    try{return new PDO($db_url,db_username,db_pwd);}
    catch (PDOException $err){echo "<p><b>Echec de connexion a la BD !!!</b></p>";}
}

function pace(){
  time_nanosleep(0, 700000000);
}

function date_to_str($time){ // Reciprocal of builtin strtotime()
  return date("Y-m-d\TH:i:s",$time);
}

?>