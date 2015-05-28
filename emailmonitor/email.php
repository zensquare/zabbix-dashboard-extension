<?php
require_once('config.php');

require_once("MimeMailParser.class.php");

if(empty($_GET['id'])){
    echo "Email ID Required";
    die;
}
$eid = $_GET['id'];

if(!empty($_GET['action'])){
    $action = $_GET['action'];
    
    require_once 'Zabbix.php';
    $zabbix = new Zabbix(array('host'=>$ZABBIX_HOST));
    
    $sql = "SELECT * FROM email_tracker.email_schedule WHERE id = " . mysql_real_escape_string($eid);
    $results = mysql_query($sql, $db);
    $row = mysql_fetch_assoc($results);
    
    
    if (empty($row)) {
        echo "Schedule Not Found!";
        die;
    }
    
    switch($action){
        case "ack":
            $zabbix->check('email.mon['.$eid.']','OK',$row['zabbix_host']);
            echo $row['name'] . " Has been flagged as ok";
            break;
    }
    
    die;
}



if(!file_exists($EMAIL_STORE.$eid)){
    echo "Email Not Found";
    die;
}


$Parser = new MimeMailParser();
$Parser->setPath($EMAIL_STORE.$eid);

$to = $Parser->getHeader('to');
$from = $Parser->getHeader('from');
$subject = $Parser->getHeader('subject');

$text = $Parser->getMessageBody('text');
$html = $Parser->getMessageBody('html');
$attachments = $Parser->getAttachments();

echo $html;