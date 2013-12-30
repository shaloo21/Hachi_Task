<!DOCTYPE html 
  PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <title>Hachi Task</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="http://yui.yahooapis.com/pure/0.3.0/base-min.css">
    </head>
  <body>
<?php

require_once "google-api-php-client/src/Google_Client.php";
require_once 'google-api-php-client/src/contrib/Google_CalendarService.php';
require_once 'conn_param.php';

session_start();

$client = new Google_Client();

$client->setApplicationName('Hachi');

$client->setScopes("http://www.google.com/m8/feeds/ https://www.googleapis.com/auth/calendar");

$client->setClientId($Client_Id);
$client->setClientSecret($Client_Secret);
$client->setRedirectUri($Redirect_Uri);
$client->setDeveloperKey($Developer_Key);
$client->setUseObjects(true); 
$cal = new Google_CalendarService($client);
if (isset($_GET['logout'])) {
  unset($_SESSION['token']);
}

if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

if ($client->getAccessToken()) {
  print "<div align='right' class='button'><a class='login' href='?logout=1'>Logout</a></div>";
  $max_results=2000;
  $optparams="";
  if(isset($_SESSION['opt'])&&$_SESSION['opt']==2)
  {
    
    $events = $cal->events->listEvents('primary');
 
$results=array();

while(true) {
  foreach ($events->getItems() as $event) {
    $attendees= $event->getAttendees();
    foreach ($attendees as $attendee)
    {
      if(array_key_exists($attendee->email,$results))
      {
        $results[$attendee->email]->freq++;
      }
      else
      {
        $obj = new stdClass;
        $obj->name=(string)$attendee->displayName;
        $obj->freq=1;
        $obj->email=$attendee->email;
        $results[$attendee->email]=$obj;
      }
    }
  }
  
  $pageToken = $events->getNextPageToken();
  if ($pageToken) {
    
    $optParams = array('pageToken' => $pageToken);

    $events = $cal->events->listEvents('primary');
  } else {
    break;
  }
}
usort($results, function($a, $b)
{
    return ($b->freq>$a->freq);
});
echo "<h2>Total ".count($results)." Contacts Retrieved. Below is the list sorted in decreasing order of frequency</h2></br>";
}
  else if(isset($_SESSION['opt'])&&$_SESSION['opt']==1)
  {
      $optparams="&orderby=lastmodified&sortorder=descending";
      $max_results=15;
  }
  if($_SESSION['opt']==0||$_SESSION['opt']==1)
  {
    $req = new Google_HttpRequest("https://www.google.com/m8/feeds/contacts/default/full?max-results=".$max_results.$optparams);
  $val = $client->getIo()->authenticatedRequest($req);
  $xmlresponse =$val->getResponseBody();
  $doc = new DOMDocument;
$doc->recover = true;
$doc->loadXML($xmlresponse);

$xpath = new DOMXPath($doc);
$xpath->registerNamespace('gd', 'http://schemas.google.com/g/2005');

$emails = $xpath->query('//gd:email');
$numbers= $xpath->query('//gd:phoneNumber');

$results=array();
foreach ( $emails as $email )
{
  $obj = new stdClass;
  $obj->name = (string) $email->parentNode->getElementsByTagName('title')->item(0)->textContent;
  $obj->email = (string) $email->getAttribute('address'); 
  $obj->number = ""; 
  $id=urlencode($email->parentNode->getElementsByTagName('id')->item(0)->textContent);
  $results[$id]=$obj;
  
}
foreach ( $numbers as $number )
{
  $id=urlencode($number->parentNode->getElementsByTagName('id')->item(0)->textContent);
  if(array_key_exists($id, $results))
  {
    $results[$id]->number = $number->textContent; 
  }
   else
   {
      $obj = new stdClass;
      $obj->name = (string) $number->parentNode->getElementsByTagName('title')->item(0)->textContent;
      $obj->email = ""; 
      $obj->number = (string)$number->textContent; 
      $results[$id]=$obj; 
   }
}
echo "<h2>Total ".count($results)." Contacts Retrieved</h2></br>";

  }
  

?>
  <table cellspacing='0'> 
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th><?php echo ($_SESSION['opt']==2)? "Frequency" : "Phone Number" ;?></th>
    </tr>
  </thead>
  <tbody>
  
  <?php
  $alt=0;
  foreach ($results as $r)
  {
    if($alt)
      echo "<tr class='even'>";
    else
      echo "<tr>";
  ?>
      
      <td><?php echo (!empty($r->name)) ? $r->name : "<div class='error'>Name not available</div>"; ?></td>
      <td><?php echo (!empty($r->email)) ? $r->email : "<div class='error'>Email not available</div>"; ?></td>
      <?php 
      if($_SESSION['opt']==2)
      {
        ?>
        <td><?php echo (!empty($r->freq)) ? $r->freq : ""; ?></td>
        <?php
      }
      else
      {
        ?>
        <td><?php echo (!empty($r->number)) ? $r->number : "<div class='error'>Phone Number not available</div>"; ?></td>
        <?php
      }
    echo "</tr>";
  
  $alt=!$alt;
}
?>
</tbody>
</table>
<?php
$_SESSION['token'] = $client->getAccessToken();
} 
else if(isset($_GET['opt'])&&$_GET['opt']==1)
{
  $_SESSION['opt']=1;
  $authUrl = $client->createAuthUrl();
  header('Location:'.$authUrl);
}
else if(isset($_GET['opt'])&&$_GET['opt']==2)
{
  $_SESSION['opt']=2;
  $authUrl = $client->createAuthUrl();
  header('Location:'.$authUrl);
}
else if(isset($_GET['opt'])&&$_GET['opt']==0)
{
  $_SESSION['opt']=0;
  $authUrl = $client->createAuthUrl();
  header('Location:'.$authUrl);
}
else { 
  print "<h1 align='center'>HACHI TASK</h1></br></br>";
  $authUrl = $client->createAuthUrl();
  print "<div align='center' class='button'><a  href='?opt=0'>Fetch Contacts</a></div></br></br>";
  print "<div align='center' class='button'><a  href='?opt=1'>Fetch Recently Communicated Contacts</a></div></br></br>";
  print "<div align='center' class='button'><a  href='?opt=2'>Fetch Most Frequently Connected People from User Calendar</a></div></br></br>";
} ?>
 </body>
</html>