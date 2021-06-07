<?php
// *************************************************************************
// *** certcheck.php 
// ***
// *** - Checks certificates for expiration
// ***
// *** when executed from shell/command line:
// *** - Sends alert mails if a certificate is going to expire soon
// *** - Sends the overview of all certificates per mail
// *** when called in a web browser:
// *** - Displays overview in a HTML table
// *** - Adds a reminder to your calendar (via icalendar .ics file)
// ***
// *** - Tested with Windows 10&2016, Linux Slackware current and PHP 7.4 & 7.2
// *** - Supports https, imaps, smtps (tls) , pop3s, ldaps (tls), ftps
// *************************************************************************
// *** This program is free software: you can redistribute it and/or modify
// *** it under the terms of the GNU General Public License as published by
// *** the Free Software Foundation, either version 3 of the License, or
// *** (at your option) any later version.
// *** 
// *** This program is distributed in the hope that it will be useful,
// *** but WITHOUT ANY WARRANTY; without even the implied warranty of
// *** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// *** GNU General Public License for more details.
// *** 
// *** You should have received a copy of the GNU General Public License
// *** along with this program.  If not, see <https://www.gnu.org/licenses/>.
// *************************************************************************
// *** (c) 2020- Jan W - certcheck@kreator.org
// *************************************************************************

// -------------------------------------------------------------------------
// --- Start of configuration
// -------------------------------------------------------------------------

// urls of the certificates to be checked
$urls2check=array(
  "smtps://smtp.gmail.com",
  "https://www.yahoo.com",
  "ldaps://db.debian.org",
  "pop3s://pop.gmail.com",
  "imaps://imap.gmail.com",
  "ftps://test.rebex.net",
  "https://www.example.org"
);

// people that will get mails alerting them about certs to expire
$alertmails=array();
// example: $alertmails=array("operations@example.com","admin1@example.com");

// 32 days before a cert will expire alertmails will be sent
$warnbeforeexpiration=32; 

// people that will get overview mails everytime that this script will run from command line 
$notifymails=array(); 
// example: $notifymails=array("admin3@example.com","admin4@example.com"); 

// reminder alarm in minutes, used in icalendar ics file
$reminderbeforeexpiration=1440; // reminder one day before expiration 

// mails are sent from this address and name
$mailfrom="certcheck@example.com";
$mailfromname="Certificate check";
// on windows you will also need to set the SMTP variable in the [mail function] section in php.ini

// url to this script,is used in the html table, mail and calendar entry
$certcheckurl="http://www.example.com/certcheck/";

// remove this line when configured
echo "not configured, edit this file first"; exit(-1);

// -------------------------------------------------------------------------
// --- End of configuration
// -------------------------------------------------------------------------

// creates url to calendar ics file for html table in web browser and mail
function CreateUrl($url,$org,$issuer,$validfrom,$validto,$subject,$daysleft,$port )
    {
    global $certcheckurl;

    $url=$certcheckurl."?url=".urlencode($url);
    $url.="&org=".urlencode($org);
    $url.="&issuer=".urlencode($issuer);
    $url.="&validto=".urlencode($validto);
    $url.="&validfrom=".urlencode($validfrom);
    $url.="&subject=".urlencode($subject);
    $url.="&daysleft=".urlencode($daysleft);
    $url.="&port=".urlencode($port);
    return($url);
    }
// function to get 
function GetParam($param)
  {
  if ( isset($_GET[$param]) )
    return( $_GET[$param] );
    else
    return("");
  }

// called with url parameter creates the ics calendar file
if ( ($url=GetParam("url"))!="" )
  {
  $org=GetParam("org");
  $issuer=GetParam("issuer");
  $validfrom=GetParam("validfrom");
  $validto=GetParam("validto");
  $subject=GetParam("subject");
  $daysleft=GetParam("daysleft");
  $port=GetParam("port");
  
  header("Content-Type: text/calendar;");
  header("Content-Disposition: attachment; filename=certcheck.ics");  
  echo "BEGIN:VCALENDAR\r\n";
  echo "VERSION:2.0\r\n";
    
  echo "BEGIN:VEVENT\r\n";
        echo "UID:".$mailfrom."\r\n";
        echo "DTSTAMP:".ConvertSimpledate(time())."\r\n";
        echo "ORGANIZER:mailto:".$mailfrom."\r\n";
        echo "DTSTART:".CreateReminderDate($validto,"100000",1)."\r\n";
        echo "DTEND:".CreateReminderDate($validto,"110000",1)."\r\n";
        echo "SUMMARY:Certificate of ".$url." (TCP port ".$port.") issued by ".$issuer." expires on ".ConvertDate($validto)."\r\n";
        echo "DESCRIPTION:".$certcheckurl."\r\n";
  echo "BEGIN:VALARM\r\n";
    echo "TRIGGER:-PT".$reminderbeforeexpiration."M\r\n";
    echo "ACTION:DISPLAY\r\n";
    echo "DESCRIPTION:Reminder\r\n";
  echo "END:VALARM\r\n";

  echo "END:VEVENT\r\n";

  echo "END:VCALENDAR\r\n";

  exit(0);  
  }
  
// checks if this php script is run from command line
// dictates if mails are sent or if a html overview is shown instead
function IsCommandLine()
    {
    return( php_sapi_name()=="cli" );
    }
    
// sends mail
function MailSend($to,$subject,$message,$mailfrom,$mailfromname)
    {
    $headers="MIME-Version: 1.0\r\n";
    $headers.="Content-type: text/html; charset=iso-8859-1\r\n";
    $headers.="From: ".$mailfromname." <".$mailfrom.">\r\n";
    mail($to,$subject,wordwrap($message,70,"\r\n"),$headers);
    }
   
// checks that all required protocols are available
$transports=stream_get_transports();
$sslfound=false;
$tlsfound=false;
for($n=0;$n<count($transports);$n++)
    {
    if ( strtoupper($transports[$n])=="SSL" )
        $sslfound=true;
    if ( strtoupper($transports[$n])=="TLS" )
        $tlsfound=true;
    }
if ( !$sslfound )
  {
  echo "SSL not supported in PHP stream functions".PHP_EOL;
  exit(-1);
  }
if ( !$tlsfound )
  {
  echo "TLS not supported in PHP stream functions".PHP_EOL;
  exit(-1);
  }
  
// convert an unix timestamp to a simpledate used in ics icalendar
function ConvertSimpledate($timestamp)
    {
    return( date("Ymd\THis",$timestamp) );
    }

// creates a reminder simpledate for ics calendar
function CreateReminderDate($certenddate,$timeofday="100000",$daysbefore=1) // $timeofday is HHMMSS
    {
    return( date("Ymd\T".$timeofday,ConvertTimestamp($certenddate)-($daysbefore*60*60*24) ) );
    }

// convert the cert date format to a standard datetime  
function ConvertDate($datetime)
    {
    // YYMMDDhhmmssZ -> 20YY-MM-DD hh:mm:ss
    return("20".substr($datetime,0,2)."-".substr($datetime,2,2)."-".substr($datetime,4,2)." ".substr($datetime,6,2).":".substr($datetime,8,2).":".substr($datetime,10,2));
    }    

// convert the cert date format to a unix timestamp
function ConvertTimestamp($datetime)
    {
    return( mktime(substr($datetime,6,2),substr($datetime,8,2),substr($datetime,10,2),substr($datetime,2,2),substr($datetime,4,2),(int) ("20".substr($datetime,0,2))) );
    }

// options for streams
$contextoptions=array(
    "ssl"=>array(
        "capture_peer_cert"=>true,
        "verify_peer"=>false,
        "verify_peer_name"=>false,
        "allow_self_signed"=>true
        ) 
    );

// known protocols in url
$protos=array(
    "https","ssl",443,
    "smtps","tls",465,
    "imaps","ssl",993,
    "pop3s","ssl",995,
    "ldaps","tls",636,
    "ftps","ssl",990
    );
    
// HTML
$htmlheader="<html><head><style>";
$htmlheader.="body {   margin: 0px;  }";
$htmlheader.="mark { background-color: yellow; color: black; }";
$htmlheader.="#tableview  {  width: 1024px; font-family: Arial, Helvetica, sans-serif; font-size: 18px; border-collapse: collapse; overflow-x: auto; overflow-y: auto; }";
$htmlheader.="#tableview th { border: 1px solid #e4e4e4; text-align:  center; padding: 2px; background-color: #f7f7f7; color: black; font-weight: bold; font-size: 12px; position: sticky; top: 0; }";
$htmlheader.="#tableview td { text-align: center; border: 1px solid #e4e4e4; font-size: 12px; padding: 2px; }";
$htmlheader.="#tableview tr:hover { background-color: #f1f1f1; }  ";
$htmlheader.="@page { margin-top: 0px; margin-bottom: 0px; margin-left: 0px; margin-right: 0px; }";
$htmlheader.="tr:hover {background-color:gray;}";
$htmlheader.="a { color: darkgray; } a:link { text-decoration: none; } a:visited { text-decoration: none; } a:hover { text-decoration: underline; }";
$htmlheader.="</style></head><body><table id='tableview'>";

$htmltable="<tr><th>URL</th><th>Cert subject</th><th>Cert org</th><th>Cert issuer</th><th>Cert valid from</th>";
$htmltable.="<th>Cert valid to</th><th>Cert days left</th><th>TCP port</th>";
$htmltable.="<th>Add to calendar</th>";
$htmltable.="</tr>";

$htmlfooter="</table></body></html>";

$sp="&nbsp;&nbsp;";

// main routine
for ($n=0;$n<count($urls2check);$n++)
    {
    // protocol (ssl or tls) and tcp port to use
    $urlinfos=parse_url($urls2check[$n]);
    if ( array_key_exists("port",$urlinfos) )
        $port=$urlinfos["port"];
        else
        $port=-1;
    for($i=0;$i<count($protos)/3;$i++)
        {
        if ( strstr($urls2check[$n],"://",true)==$protos[$i*3] )
            {
            $proto=$protos[$i*3+1];
            if ( $port==-1 )
              $port=$protos[$i*3+2];
            continue;
            }    
        }
    if ( ($proto=="") || ($port==-1) )
        {
        echo "Unknown protocol given in url ".$urls2check[$n].PHP_EOL;
        exit(-1);
        }

    // open url and retrieve certificate
    $urlinfo=parse_url($urls2check[$n],PHP_URL_HOST);
    $stream=stream_context_create($contextoptions);    
    if ( ($handle=stream_socket_client($proto."://".$urlinfo.":".$port,$errno,$errmsg,30,STREAM_CLIENT_CONNECT,$stream))===false )
        echo "error opening url ".$urls2check[$n].PHP_EOL;
        else
        {
        $cert=stream_context_get_params($handle);
        $certinfo=openssl_x509_parse($cert["options"]["ssl"]["peer_certificate"]);
        $htmltable.="<tr><td><b>".$sp.$urls2check[$n].$sp."</b></td>";
        $htmltable.="<td>".$sp.$certinfo["subject"]["CN"].$sp."</td>";
        if ( array_key_exists("O", $certinfo["subject"]) )
          $org=$certinfo["subject"]["O"];
          else
          $org="";
        $htmltable.="<td>".$sp.$org.$sp."</td>";
        $htmltable.="<td>".$sp.$certinfo["issuer"]["CN"].$sp."</td>";
        $htmltable.="<td>".$sp.ConvertDate($certinfo["validFrom"]).$sp."</td>";
        $htmltable.="<td>".$sp.ConvertDate($certinfo["validTo"]).$sp."</td>";
        $daysleft=floor((ConvertTimestamp($certinfo["validTo"])-time())/86400.0);
        if ( $daysleft<=$warnbeforeexpiration )
            {
            if ( IsCommandLine() )
              {
              // sends certificate expired emails
              $msg="Certificate of ".$urls2check[$n]." (TCP port ".$port.")  issued by ".$certinfo["issuer"]["CN"]." expires on ".ConvertDate(ConvertDate($certinfo["validTo"]));
              for($i=0;$i<count($alertmails);$i++)
                MailSend($alertmails[$i],$msg,$msg,$mailfrom,$mailfromname);
              }
            $htmltable.="<td><b><mark>".$sp.$daysleft.$sp."</mark></b></td>";
            }
            else
            $htmltable.="<td><b>".$sp.$daysleft.$sp."</b></td>";
        $htmltable.="<td>".$sp.$port.$sp."</td>";

        $htmltable.="<td>".$sp;        
        $htmltable.="<a href='".CreateUrl($urls2check[$n],$org,$certinfo["issuer"]["CN"],$certinfo["validFrom"],$certinfo["validTo"],$certinfo["subject"]["CN"],$daysleft,$port)."'>Add</a>";      
        $htmltable.=$sp."</td>";
        
        $htmltable.="</tr>";
        }
    }
if ( IsCommandLine() )
  {
  // used from command line: send overview per mail
  for($n=0;$n<count($notifymails);$n++)
    MailSend($notifymails[$n],"Certificate check",$htmlheader.$htmltable.$htmlfooter,$mailfrom,$mailfromname);
  } 
  else
  {
  // used in web browser: display overview
  echo $htmlheader;
  echo $htmltable;
  echo $htmlfooter;
  }
?>
