<?php
/*
* @version 0.1 (wizard)
*/

echo $this->owner->name;
echo $this->mode;
echo $this->tab;

  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='mag250_devices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='info') {
  //updating '<%LANG_TITLE%>' (varchar, required)
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }

   global $password;
   $rec['PASSWORD']=$password;
   if ($rec['PASSWORD']=='') {
    $out['ERR_PASSWORD']=1;
    $ok=0;
   }


}}
//echo "ok:".$ok."   rec[id]:".$rec['ID'];
	if (($ok==0) and  ($rec['ID'])) {
//echo "runapdate";
	SQLUpdate($table_name, $rec);
	$out['OK'] = 1;
		} 


		


  // step: data
  //UPDATING RECORD
  // step: default

  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);

