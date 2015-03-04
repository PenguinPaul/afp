<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("member_profile_end", "activatefromprofile");
$plugins->add_hook("member_activate_start", "do_activatefromprofile");



function activatefromprofile_info()
{
	return array(
		"name"			=> "Activate from Profile",
		"description"	=> "Lets you activate users directly from their profile.",
		"website"		=> "http://paulhedman.com",
		"author"		=> "Paul H.",
		"authorsite"	=> "http://paulhedman.com",
		"version"		=> "1.0",
		"codename" 		=> "afp",
		"compatibility" => "*"
	);
}

function activatefromprofile_activate()
{
	global $db;
 $act_group = array(
		'gid'			=> 'NULL',
		'name'			=> 'afp',
		'title'			=> 'Activate from Profile Settings',
		'description'	=> 'Settings for the Activate from Profile plugin.',
		'disporder'		=> "1",
		'isdefault'		=> 'no',
	);

	$db->insert_query('settinggroups', $act_group);
	$gid = $db->insert_id();

	$modset = array(
		'name'			=> 'afp_moda',
		'title'			=> 'Can moderators activate users?',
		'description'	=> 'If no is selected only admins can do so.',
		'optionscode'	=> 'yesno',
		'value'			=> '0',
		'disporder'		=> 1,
		'gid'			=> intval($gid),
	);
	$db->insert_query('settings', $modset);

	rebuild_settings();
}

function activatefromprofile_deactivate()
{
	global $db;
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('afp_moda')");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='afp'");
	rebuild_settings(); 
}

function activatefromprofile()
{
	global $memprofile,$adminoptions,$modoptions,$mybb;
	if($mybb->usergroup['cancp']) {
		if($memprofile['usergroup'] == "5") {
			$posthash = generate_post_check();
			$adminoptions = str_replace("</ul>", "<li><a href=\"member.php?action=activate&amethod=profile&uid={$memprofile['uid']}&my_post_key={$posthash}\">Activate this user</a></li></ul>", $adminoptions);
		
		
		}
	}

	if($mybb->settings['afp_moda'] && $mybb->usergroup['canmodcp']) {
		if($memprofile['usergroup'] == "5") {
			$posthash = generate_post_check();
			$modoptions = str_replace("</ul>", "<li><a href=\"member.php?action=activate&amethod=profile&uid={$memprofile['uid']}&my_post_key={$posthash}\">Activate this user</a></li></ul>", $modoptions);
		
		
		}
	}
	
}

function do_activatefromprofile()
{
	global $mybb,$plugins,$db;
	if(isset($mybb->input['amethod']) && isset($mybb->input['uid']) && $mybb->input['amethod'] == "profile" && can_activate_from_profile() && verify_post_check($mybb->input['my_post_key']))
	{
		$activateduser = get_user($mybb->input['uid']);
		$db->delete_query("awaitingactivation", "uid='".$activateduser['uid']."'");
		$db->update_query("users", array("usergroup" => 2), "uid='".$activateduser['uid']."'");
		$plugins->run_hooks("member_activate_accountactivated");
		redirect(get_profile_link($activateduser['uid']),"Member activated successfully.");
	}

}


function can_activate_from_profile()
{
	global $mybb;
	$return = false;
	if ($mybb->usergroup['cancp']) {$return = TRUE;}
	if($mybb->settings['afp_moda'] && $mybb->usergroup['canmodcp']) {$return = TRUE;}
	return $return;
}
?>
