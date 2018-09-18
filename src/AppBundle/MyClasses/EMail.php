<?php

namespace AppBundle\MyClasses;

class EMail
{
  const newline = "\r\n";

  private
    $Server, $Port, $Localhost,
    $skt;

  public
    $Username, $Password, $ConnectTimeout, $ResponseTimeout,
    $Headers, $ContentType, $From, $To, $Cc, $Subject, $Message,
    $Log;

  function __construct()
  {
    $this->Server = "syfflsas3.lerot.org";
    $this->Port = 465;
    $this->Localhost = "localhost";
    $this->ConnectTimeout = 60;
    $this->ResponseTimeout = 20;
    $this->From = array();
    $this->To = array();
    $this->Cc = array();
    $this->Log = array();
    $this->Headers['MIME-Version'] = "1.0";
    $this->Headers['Content-type'] = "text/plain; charset=iso-8859-1";
  }

  private function GetResponse()
  {
    stream_set_timeout($this->skt, $this->ResponseTimeout);
    $response = '';
    while (($line = fgets($this->skt, 515)) != false)
    {
	$response .= trim($line) . "\n";
	if (substr($line,3,1)==' ') break;
    }
    return trim($response);
  }

  private function SendCMD($CMD)
  {
    fputs($this->skt, $CMD.self::newline);

    return $this->GetResponse();
  }

  private function FmtAddr(&$addr)
  {
    if ($addr[1] == "") return $addr[0]; else return "\"{$addr[1]}\" <{$addr[0]}>";
  }

  private function FmtAddrList(&$addrs)
  {
    $list = "";
    foreach ($addrs as $addr)
    {
      if ($list) $list .= ", ".self::newline."\t";
      $list .= $this->FmtAddr($addr);
    }
    return $list;
  }

  function AddTo($addr,$name = "")
  {
    $this->To[] = array($addr,$name);
  }

  function AddCc($addr,$name = "")
  {
    $this->Cc[] = array($addr,$name);
  }

  function SetFrom($addr,$name = "")
  {
    $this->From = array($addr,$name);
  }

  function Send()
  {
    $newLine = self::newline;

    //Connect to the host on the specified port
    $this->skt = fsockopen($this->Server, $this->Port, $errno, $errstr, $this->ConnectTimeout);

    if (empty($this->skt))
      return false;

    $this->Log['connection'] = $this->GetResponse();

    //Say Hello to SMTP
    $this->Log['helo']     = $this->SendCMD("EHLO {$this->Localhost}");

    //Request Auth Login
    $this->Log['auth']     = $this->SendCMD("AUTH LOGIN");
    $this->Log['username'] = $this->SendCMD(base64_encode($this->Username));
    $this->Log['password'] = $this->SendCMD(base64_encode($this->Password));

    //Email From
    $this->Log['mailfrom'] = $this->SendCMD("MAIL FROM:<{$this->From[0]}>");

    //Email To
    $i = 1;
    foreach (array_merge($this->To,$this->Cc) as $addr)
      $this->Log['rcptto'.$i++] = $this->SendCMD("RCPT TO:<{$addr[0]}>");

    //The Email
    $this->Log['data1'] = $this->SendCMD("DATA");

    //Construct Headers
    if (!empty($this->ContentType))
      $this->Headers['Content-type'] = $this->ContentType;
    $this->Headers['From'] = $this->FmtAddr($this->From);
    $this->Headers['To'] = $this->FmtAddrList($this->To);
    if (!empty($this->Cc))
      $this->Headers['Cc'] = $this->FmtAddrList($this->Cc);
    $this->Headers['Subject'] = $this->Subject;
    $this->Headers['Date'] = date('r');

    $headers = '';
    foreach ($this->Headers as $key => $val)
      $headers .= $key . ': ' . $val . self::newline;

    $this->Log['data2'] = $this->SendCMD("{$headers}{$newLine}{$this->Message}{$newLine}.");

    // Say Bye to SMTP
    $this->Log['quit']  = $this->SendCMD("QUIT");

    fclose($this->skt);

    return substr($this->Log['data2'],0,3) == "250";
  }


function testmail()
{



$mail = new EMail;
$mail->Username = 'admin@syfflsas3.lerot.org';
$mail->Password = 'savannah#1944';

$mail->SetFrom("admin@syfflsas3.lerot.org","admin fflsas");		// Name is optional
$mail->AddTo("paul.a.golder@lerot.org","paullerot");	// Name is optional
#$mail->AddTo("someother2@address.com");
$mail->Subject = "Some subject or other";
$mail->Message = "Some html message";

//Optional stuff
#$mail->AddCc("someother3@address.com","name 3"); 	// Set a CC if needed, name optional
$mail->ContentType = "text/html";        		// Defaults to "text/plain; charset=iso-8859-1"
$mail->Headers['X-SomeHeader'] = 'abcde';		// Set some extra headers if required
$mail->ConnectTimeout = 30;		// Socket connect timeout (sec)
$mail->ResponseTimeout = 8;		// CMD response timeout (sec)

$success = $mail->Send();
return $success;
}

}

?>