<?php
/**
* @package Author
* @author Joseph P. Gibson
* @website www.joesboat.org
* @email joe@joesboat.org
* @copyright Copyright (C) 2018 Joseph Gibson - All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE.txt
**/

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('usps.dbUSPSSquadrons');
jimport('usps.tableD5VHQAB');
jimport('usps.dbUSPSd5WebSites');
jimport('usps.includes.routines');
jimport('usps.dbUSPSjoomla');
$GLOBALS['vhqab'] = $vhqab = JoeFactory::getLibrary("USPSd5tableVHQAB");
$addr = $vhqab->getD5AddressesObject();
$GLOBALS['WebSites'] = $WebSites = JoeFactory::getLibrary("USPSd5dbWebSites");
$jobs = $vhqab->getJobsObject();
$codes = $vhqab->getJobcodesObject();
$exc = $vhqab->getExcomObject();
$sqds = $vhqab->getSquadronObject();
$mbr = $vhqab->getD5MembersObject();
$org = '6243';
$year = $sqds->get_display_year($org);
$header = '';
define('EOL',"\r\n");

require_once(dirname(__FILE__).'/helper.php');


//*****************************************************
function show_data($member){
global $addr;
	$str = get_mbr_name_and_grade($member);
	$str .= " certificate: ".$member['certificate'];
	$address = $addr->get_record('address_id',$member['address_id']);
	if ($address)
		foreach ($address as $col=>$value){
			$str .= " $col: $value ";
		}
	else 
		$str .= "No address link";
	return $str;
}
//*****************************************************
function addToMailingList($address,$fh_mailing,$org){
$vhqab = $GLOBALS['vhqab'];
$addr = $vhqab->getD5AddressesObject();
$mbr = $vhqab->getD5MembersObject();
	$line = '';
	$address_id = $address['address_id'];
	$address['name'] = $mbr->get_member_names_for_newsletter_address($address_id, $org);
	if (trim($address['name']) == '') return;
	$address['address_1'] .= " ".$address['address_2'];
	foreach($addr->address_columns as $ckey=>$cname){
		if ($cname == 'address_1'){
			$address[$cname] .= " ".$address['address_2'];
		}
		if ($cname == 'address_2') return;
		$line .= str_replace(',' , ' ',$address[$cname]).',';	
	}
	$i = fwrite($fh_mailing, $line.EOL);	
}
//*****************************************************
function addToEmailList($mbrs,$fh_email){
	foreach ($mbrs as $mem){
		$line = get_person_name($mem).','.$mem['email'];
		$i = fwrite($fh_email,$line.EOL);
	}
}
//*****************************************************
function selectBestMember($mbrs){
	if (count($mbrs) == 1)
		return $mbrs[0];
	else{
		foreach ($mbrs as $mem){
			if ($mem['mbrstatus'] == 'AC15')
				return $mem;
		}
	}
	return $mbrs[0];
	// Determine which member's email address should be used
}
//*****************************************************
if (isset($org)){
	switch($org){
		case '6243':
			$mailing_file_name = "documents/".date("Ymd")." Mark 5 Mail List.csv";
			$email_file_name = "documents/".date("Ymd")." Mark 5 EMail List.csv";
			$newsletter_name = "Mark 5";
			break;
		default:
			$squadron = $sqds->get_record('squad_no',$org);
			$newsletter_name = $squadron['newsletter_name'];
			$mailing_file_name = "documents/".date("Ymd")." $newsletter_name List.csv";
			break;
	}
	if (! $fh_mailing = fopen($mailing_file_name,'w')){
		$error = "Unable to open a new file named '$mailing_file_name'";
		return;
	}
	if (! $fh_email = fopen($email_file_name,'w')){
		$error = "Unable to open a new file named '$email_file_name'";
		return;
	}	// $result = $addr->get_hardcopy_addresses();

	//require(JModuleHelper::getLayoutPath('mod_newsletter_mail_list','select'));
	require(JModuleHelper::getLayoutPath('mod_newsletter_mail_list','search'));

	fclose($fh_mailing);
	fclose($fh_email);
}
if (isset($_POST['command'])){
	if (strtolower($_POST['command'])=='cancel'){
		header("Location: member_control.php?".htmlspecialchars(SID));
		exit(0);
	}
}
$mailing_file_link = "<a href='/$mailing_file_name' target='_blank'>Download Postal Address File</a>";
$email_file_link = "<a href='/$email_file_name' target='_blank'>Download EMail Address File</a>";
showHeader("$newsletter_name Newsletter Distribution List",$_SERVER['REQUEST_URI']);
echo  "<p>A file ($mailing_file_name) is available listing the US Mail addresses for newsletter distribution.</p>";
echo  "<p>";
echo  "You may $mailing_file_link.";
echo  "</p>";
echo  "<p>A file ($email_file_name) is available listing the EMail addresses for newsletter distribution.</p>";
echo  "<p>";
echo  "You may $email_file_link.";
echo  "</p>";
showTrailer(); 
?>
