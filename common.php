<?php
function pageHeader($pageDesc){
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<meta http-equiv="Content-Language" content="fr" />
		<meta http-equiv="refresh" content="600">
		<title>'
	. $pageDesc .'</title>

      <STYLE type="text/css">
      DIV.sc {font-family: Sans-Serif;}
      DIV.ib {display: inline;}
      DIV.plot{ margin-left: auto;
                margin-right: auto;}
      DIV.chartClass{border-style: solid;
                width:95vw;
                height:40vh;
                margin-bottom:6px}
      TABLE.dm{border-collapse: collapse;}
      TABLE.prod{border: 2px solid black; border-collapse: collapse;}
      TD{border: 1px solid black; padding-left: 5px; padding-right: 3px; padding-top: 3px; padding-bottom: 2px}
      TD.totProd{font-weight: bold;}
      H1{font-family: Sans-Serif; text-align:center; font-size: 1.5em; margin: .75em 0}
     </STYLE>


	</head>

	<body>
	<H1>'.$pageDesc.'</H1>';
}

function nameAppli(){
  return "TablOWatt";
}

function pageFoot(){
	echo "</body></html>\r";
}

function header_form($filename){
    echo "<form method=\"post\" action=\"$filename\" id=\"fid\">";
}


function connect_to_db(){
    $db_url="mysql:host=".db_host.";dbname=".db_name.";charset=utf8";
    try{return new PDO($db_url,db_username,db_pwd);}
    catch (PDOException $err){echo "<p><b>Echec de connexion a la BD !!!</b></p>";}
}

function prepare_header(){
    return "MIME-Version: 1.0\r\n".
    'Content-Type: text/plain; charset=utf-8'. "\r\n".
    'X-Mailer: PHP/' . phpversion(). "\r\n";
}


function pace(){
  time_nanosleep(0, 700000000);
}

function date_to_str($time){ // Reciprocal of builtin strtotime()
  return date("Y-m-d\TH:i:s",$time);
}

function get_meter_list($db)
{
  $qr="select * from ".tp."meters order by name";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute();
  return $select_messages->fetchAll();
}
function get_meter_list_orig($db)
{
  $qr="select * from ".tp."meters where serial not in (select replacedby from ".tp."disabled) order by name";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute();
  return $select_messages->fetchAll();
}
function get_meter_list_check($db)
{
  $qr="select * from ".tp."meters where serial not in (select serial from  ".tp."disabled) order by name";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute();
  return $select_messages->fetchAll();
}

?>