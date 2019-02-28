<?php
/**
* milur 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 10:01:31 [Jan 03, 2018])
*/
//
//
//ini_set('max_execution_time', '600');
class app_mag250 extends module {
/**
* milur
*
* Module class constructor
*
* @access private
*/
function app_mag250() {
  $this->name="app_mag250";
  $this->title="mag250";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['TAB']=$this->tab;



$cmd_rec = SQLSelectOne("SELECT VALUE FROM mag250_config where parametr='DEBUG'");
$debug=$cmd_rec['VALUE'];

$out['MSG_DEBUG']=$debug;



 $this->search_devices($out);


  $this->data=$out;
//  $this->checkSettings();

  
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {

 $this->getConfig();
// $this->search_devices($out);

  if ($this->view_mode=='' || $this->view_mode=='info') {
$this->search_devices($out);
  }



if ($this->view_mode=='scan') {

$this->scan_device();
//   $this->search_devices($out);
}  

if ($this->view_mode=='delete_devices') {
$this->delete_once($this->id);
}  

  if ($this->view_mode=='edit_devices') {
   $this->edit_devices($out, $this->id);
    }


  if (substr($this->view_mode,0,3)=='key') {
$msg=substr($this->view_mode,4);
$this->sendkey($this->id,$msg);
    }


}  


 function propertySetHandle($object, $property, $value) {
   $my_properties=SQLSelect("SELECT ID FROM mag250_devices WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($my_properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     $this->setProperty($my_properties[$i]['ID'], $value);
    }
   }  
 }

 
function edit_devices(&$out, $id) {
require(DIR_MODULES.$this->name . '/mag250_edit.inc.php');
}


 function search_devices(&$out) {

$mhdevices=SQLSelect("SELECT * FROM mag250_devices");
$total = count($mhdevices);
for ($i = 0; $i < $total; $i++)
{ 
$ip=$mhdevices[$i]['IP'];
$lastping=$mhdevices[$i]['LASTPING'];
//echo time()-$lastping;
if (time()-$lastping>300) {

$cmd='
$online=ping(processTitle("'.$ip.'"));
if ($online) 
{SQLexec("update mag250_devices set ONLINE=1, LASTPING='.time().' where IP=\''.$ip.'\'");} 
else 
{SQLexec("update mag250_devices set ONLINE=0, LASTPING='.time().' where IP=\''.$ip.'\'");}

';
 SetTimeOut('mag250_devicesping',$cmd, '1'); 


/*

$online=ping(processTitle($ip));
    if ($online) 
{SQLexec("update mag250_devices set ONLINE='1', LASTPING=".time()." where IP='$ip'");} 
else 
{SQLexec("update mag250_devices set ONLINE='0', LASTPING=".time()." where IP='$ip'");}
*/

}}


  $mhdevices=SQLSelect("SELECT * FROM mag250_devices");
  if ($mhdevices[0]['ID']) {
   $out['DEVICES']=$mhdevices;

    }

}   


 

	/**
	* processCommand
	*
	* ...
	*
	* @access private
	*/





	function processCommand($device_id, $command, $value, $params = 0) {

		$cmd_rec = SQLSelectOne("SELECT * FROM mag250_commands WHERE DEVICE_ID=".(int)$device_id." AND TITLE LIKE '".DBSafe($command)."'");

		if (!$cmd_rec['ID']) {
			$cmd_rec = array();
			$cmd_rec['TITLE'] = $command;
			$cmd_rec['DEVICE_ID'] = $device_id;
			$cmd_rec['ID'] = SQLInsert('mag250_commands', $cmd_rec);
		}

		$old_value = $cmd_rec['VALUE'];

		$cmd_rec['VALUE'] = $value;
		$cmd_rec['UPDATED'] = date('Y-m-d H:i:s');
		SQLUpdate('mag250_commands', $cmd_rec);

      // Если значение метрики не изменилось, то выходим.
      if ($old_value == $value) return;

      // Иначе обновляем привязанное свойство.
      if ($cmd_rec['LINKED_OBJECT'] && $cmd_rec['LINKED_PROPERTY']) {
         setGlobal($cmd_rec['LINKED_OBJECT'] . '.' . $cmd_rec['LINKED_PROPERTY'], $value, array($this->name => '0'));
      }

      // И вызываем привязанный метод.
      if ($cmd_rec['LINKED_OBJECT'] && $cmd_rec['LINKED_METHOD']) {
         if (!is_array($params)) {
            $params = array();
         }
         $params['VALUE'] = $value;
         $params['OLD_VALUE'] = $old_value;
         $params['NEW_VALUE'] = $value;

         callMethodSafe($cmd_rec['LINKED_OBJECT'] . '.' . $cmd_rec['LINKED_METHOD'], $params);
      }

	}
  
 

/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);

}
/**

*
* @access public
*/
 



function checkSettings() {

}


//////////////////////////////////////////////
//////////////////////////////////////////////
//////////////////////////////////////////////
//////////////////////////////////////////////
//////////////////////////////////////////////
//////////////////////////////////////////////





function delete_once($id) {
  SQLExec("DELETE FROM mag250_devices WHERE id=".$id);
  $this->redirect("?");
 }



function sendkey($id, $msg) {
$rec=SQLSelectOne("select *  FROM mag250_devices WHERE id=".$id);
$ip=$rec['IP'];
$pwd=$rec['PASSWORD'];


if ($msg='keyboard')	$command = array("msgType" => "keyboardRequest" );
if ($msg='play')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 134217728,"keycode" => 82,"unicode" => 114,"action" => "press");
if ($msg='ffwd')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 134217728,"keycode" => 70,"unicode" => 102,"action" => "press" );
if ($msg='rew')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 134217728,"keycode" => 66,"unicode" => 98,"action" => "press");
if ($msg='guide')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 134217728,"keycode" => 67108864,"unicode" => 119,"action" => "press" );
if ($msg='tv')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777273,"unicode" => 121,"action" => "press");
if ($msg='app')	    	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777275,"unicode" => 123,"action" => "press" );
if ($msg='info')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 134217728,"keycode" => 89,"unicode" => 121,"action" => "press");
if ($msg='exit')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777216,"unicode" => 27,"action" => "press");
if ($msg='back')        $command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777219,"unicode" => 8,"action" => "press");
if ($msg='menu')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777274,"unicode" => 122,"action" => "press");
if ($msg='blue')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777267,"unicode" => 115,"action" => "press");
if ($msg='yellow')   	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777266,"unicode" => 114,"action" => "press" );
if ($msg='green')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777265,"unicode" => 113,"action" => "press");
if ($msg='red')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777264,"unicode" => 112,"action" => "press");
if ($msg='guide')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 2,"keycode" => 119,"unicode" => chr(119),"action" => "press");
if ($msg='setting')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777272,"unicode" => 120,"action" => "press" );
if ($msg='power')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 1,"keycode" => 117,"unicode" => chr(117),"action" => "press");
if ($msg='mute')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 1,"keycode" => 96, "unicode" => chr(96), "action" => "press");
if ($msg='reload')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 116,"unicode" => chr(116),"action" => "press");
if ($msg='1')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 49,"unicode" => 1,"action" => "press");
if ($msg='2')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 50,"unicode" => 2,"action" => "press");
if ($msg='3')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 51,"unicode" => 3,"action" => "press");
if ($msg='4')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 52,"unicode" => 4,"action" => "press");
if ($msg='5')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 53,"unicode" => 5,"action" => "press");
if ($msg='6')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 54,"unicode" => 6,"action" => "press");
if ($msg='7')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 55,"unicode" => 7,"action" => "press");
if ($msg='8')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 56,"unicode" => 8,"action" => "press");
if ($msg='9')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 57,"unicode" => 9,"action" => "press");
if ($msg='0')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 48,"unicode" => 0,"action" => "press");
if ($msg='up')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777235,"unicode" => 38,"action" => "press");
if ($msg='down')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777237,"unicode" => 40,"action" => "press");
if ($msg='left')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777234,"unicode" => 37,"action" => "press");
if ($msg='right')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777236,"unicode" => 39,"action" => "press");
if ($msg='audiomode')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 134217728,"keycode" => 71,"unicode" => 0,"action" => "press");
if ($msg='stop')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 134217728,"keycode" => 83,"unicode" => 115,"action" => "press");
if ($msg='size')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 67108864,"keycode" => 16777269,"unicode" => 11,"action" => "press");
if ($msg='reload')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777272,"unicode" => 116,"action" => "press" );
if ($msg='home')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777216,"unicode" => 27,"action" => "press");
if ($msg='ok')		$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 16777220,"unicode" => 13,"action" => "press");
if ($msg='volumedown')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 45,"unicode" => 45,"action" => "press");
if ($msg='volumeup')	$command = array("msgType" => "keyboardKey","action" => "press","metaState" => 0,"keycode" => 43,"unicode" => 43,"action" => "press");

//echo $ip, $command, $pwd;
 $answer = $this->send_command($ip, $command, $pwd);
echo $answer;
sqlexec("update mag250_devices set STATE='".$answer."' where ID=".$id);

 return $answer;
 }


/**
*
* @access public
*/
 
/**
* milur_devices delete record
*
* @access public
*/
 
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
   parent::uninstall();
  SQLExec('DROP TABLE IF EXISTS mag250_devices');
  SQLExec('DROP TABLE IF EXISTS mag250_config');
  SQLExec('DROP TABLE IF EXISTS mag250_commands');


 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data = '') {

 $data = <<<EOD
 mag250_devices: ID int(10) unsigned NOT NULL auto_increment
 mag250_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 mag250_devices: IP varchar(100) NOT NULL DEFAULT ''
 mag250_devices: PORT varchar(100) NOT NULL DEFAULT ''
 mag250_devices: PASSWORD varchar(100) NOT NULL DEFAULT ''
 mag250_devices: MAC varchar(100) NOT NULL DEFAULT ''
 mag250_devices: ONLINE varchar(100) NOT NULL DEFAULT ''
 mag250_devices: LASTPING varchar(100) NOT NULL DEFAULT ''
 mag250_devices: FIND varchar(100) NOT NULL DEFAULT ''
 mag250_devices: MODEL varchar(100) NOT NULL DEFAULT ''
 mag250_devices: STATE varchar(100) NOT NULL DEFAULT ''
 mag250_devices: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 mag250_devices: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
EOD;
  parent::dbInstall($data);


 $data = <<<EOD
 mag250_commands: ID int(10) unsigned NOT NULL auto_increment
 mag250_commands: TITLE varchar(100) NOT NULL DEFAULT ''
 mag250_commands: VALUE varchar(255) NOT NULL DEFAULT ''
 mag250_commands: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 mag250_commands: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 mag250_commands: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 mag250_commands: LINKED_METHOD varchar(100) NOT NULL DEFAULT '' 
 mag250_commands: UPDATED datetime
EOD;
  parent::dbInstall($data);

 $data = <<<EOD
 mag250_config: parametr  varchar(300) 
 mag250_config: value varchar(10000)  
EOD;
  parent::dbInstall($data);

  $mhdevices=SQLSelect("SELECT *  FROM mag250_commands");
  if ($mhdevices[0]['ID']) 

{}else{

$par=array();		 
$par['TITLE'] = 'command';
$par['ID'] = "1";		 
SQLInsert('mag250_commands', $par);		 

$par['TITLE'] = 'color';
$par['ID'] = "2";		 
SQLInsert('mag250_commands', $par);		 
                	
$par['TITLE'] = 'level';
$par['ID'] = "3";		 
SQLInsert('mag250_commands', $par);		 

$par['TITLE'] = 'status';
$par['ID'] = "4";		 
SQLInsert('mag250_commands', $par);		 


$par2=array();		 
$par2['parametr'] = 'DEBUG';
$par2['value'] = "";		 
SQLInsert('mag250_config', $par2);		 
}


 }

function csum($str)
{
$ar=str_split ($str,2);

 $csum=0;
 for ($j = 0; $j <count ($ar); $j++) {
 $csum=$csum+hexdec($ar[$j]);
 }
return substr(dechex($csum),-2);
}





function scan_device()
    {
    $arr = array(
        'protocol' => 'remote_stb_1.0',
        'port' => 6777
    );
    $post_data = json_encode($arr);

    // create socket
    $sock = socket_create(AF_INET, SOCK_DGRAM, 0);
    socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
    socket_bind($sock, 0, 6777);
    socket_sendto($sock, $post_data, strlen($post_data) , 0, '255.255.255.255', 6000);
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array(
        "sec" => 1,
        "usec" => 10
    ));
$ret='';
    $response = array();
    do
        {
        $buf = null;
        @socket_recvfrom($sock, $buf, 2048, 0, $host, $sport);
        if (!is_null($buf))
            {
            $response[] = $buf;
//   foreach($response as $pars) {

//$par=json_decode($pars, true);
$par=json_decode($response[0], true);
$par=json_decode($buf, true);

//print_r( $response);
//echo "1".$par."<br>";
//echo "1".$response[0]['type'];

$model=$par['type'];
$name=$par['name'];
$ip=$par['type'];
$sn=$par['serialNumber'];
$sh=$par['screenHeight'];
$sw=$par['screenWidth'];
$port=$par['port'];
$prot=$par['protocolVersion'];
$modes=$par['modes'];
$ip=$host;


  $mhdevices=SQLSelect("SELECT * FROM mag250_devices where MAC='".$sn."' and IP='$ip'");
 if ($mhdevices[0]['ID']) {} else 

{ 
$mac=$par[1];
$par1=array();

$par1['TITLE'] = $model.":".$name;
$par1['MODEL'] = $model;
$par1['IP'] = $ip;
$par1['PORT'] = $port;
//$par1['MODEL'] = 'RGB DIMMER';
$par1['MAC'] = $sn;
$par1['FIND'] = date('m/d/Y H:i:s',time());		
SQLInsert('mag250_devices', $par1);		 
}



//}

//            echo "<br />Messagge : < $buf > ,  $host : $sport <br />";
            }
        }

    while (!is_null($buf));

}

// 1 - shift - 134217728
// 2 - ctrl - 67108864
// metastate in center


// send command to device and wait answer from device 1-ok 0-false
// command must be array
function send_command($ip, $command, $password)
    {

    // create socket
    $sock = socket_create(AF_INET, SOCK_DGRAM, 0);
    socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
    socket_bind($sock, 0, 6777);

    // convert array to json
    $command = json_encode($command);

    // coded to aes-256-cbc
   $post_data = $this->encrypt_answer($command, $password);

    // send command
    socket_sendto($sock, $post_data, strlen($post_data) , 0, $ip, 7666);
    socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array(
        "sec" => 1,
        "usec" => 10
    ));

    // recive command
    do
        {
        $buf = null;
        @socket_recvfrom($sock, $buf, 1024, 0, $host, $ports);
        if (!is_null($buf))
            {
            $plaintext = $buf;
            }
        }
    while (!is_null($buf));
    socket_close($sock);
    return $this->decrypt_answer($plaintext, $password);
    }


// decription text
function decrypt_answer($text, $password)
    {
    $iv = 'erghnlhbnmbnkghy';
    $result = openssl_decrypt($text, 'AES-256-CBC', $password, OPENSSL_RAW_DATA, $iv);
    return $result;
    }


// encription text
function encrypt_answer($text, $password)
    {
    $iv = 'erghnlhbnmbnkghy';
    $result = openssl_encrypt($text, 'AES-256-CBC', $password, OPENSSL_RAW_DATA, $iv);
    return $result;
    }


}
// --------------------------------------------------------------------
	


/*
*
* TW9kdWxlIGNyZWF0ZWQgSmFuIDAzLCAyMDE4IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/




// sudo tcpdump  ip dst 192.168.1.82 and  ip src 192.168.1.39 -w dump.cap

