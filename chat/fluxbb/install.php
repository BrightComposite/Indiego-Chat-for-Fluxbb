<?php
/**
 *  @package    IndieGo Chat Adaptation for FluxBB
 *  @file       install.php - Предоставляет функции установки чата в FluxBB
 *  
 *  @version    1.0
 *  @date       2015-06-09
 *  @author     IndieGo (indiego.mttt@gmail.com)
 *  @sponsor    Volkula (volkula@gmail.com)
 *  
 *  @copyright  Copyright (c) IndieGo, 2015
 */

function install()
{
	global $db;
	
	$chat_messages = array(
		'FIELDS' => array(
			'id'		=> array(
					'datatype'		=> 'BIGINT(20) UNSIGNED AUTO_INCREMENT',
					'allow_null'	=> false
			),
			'user_id'	=> array(
					'datatype'		=> 'INT(11)',
					'allow_null'	=> false
			),
			'date_time'	=> array(
					'datatype'		=> 'INT(11)',
					'allow_null'	=> false
			),
			'text'		=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> false
			)
		),
		'PRIMARY KEY' => array('id'),
	);
	
	$chat_sessions = array(
		'FIELDS' => array(
			'id'		=> array(
					'datatype'		=> 'BIGINT(20) UNSIGNED AUTO_INCREMENT',
					'allow_null'	=> false
			),
			'sid'		=> array(
					'datatype'		=> 'VARCHAR(128)',
					'allow_null'	=> false
			),
			'last_time'	=> array(
					'datatype'		=> 'INT(11)',
					'allow_null'	=> false
			)
		),
		'PRIMARY KEY' => array('id'),
	);
	
	$chat_commands = array(
		'FIELDS' => array(
			'id'		=> array(
					'datatype'		=> 'BIGINT(20) UNSIGNED AUTO_INCREMENT',
					'allow_null'	=> false
			),
			'session'=> array(
					'datatype'		=> 'VARCHAR(128)',
					'allow_null'	=> false
			),
			'command'	=> array(
					'datatype'		=> 'VARCHAR(5)',
					'allow_null'	=> false
			),
			'params'	=> array(
					'datatype'		=> 'TEXT',
					'allow_null'	=> false
			)
		),
		'PRIMARY KEY' => array('id'),
	);
	
	$db->create_table(IndieGoChat\Config::DB_MESSAGES_TABLE, $chat_messages) or error('Unable to create table "' . IndieGoChat\Config::DB_MESSAGES_TABLE . '"', __FILE__, __LINE__, $db->error());
	$db->create_table(IndieGoChat\Config::DB_SESSIONS_TABLE, $chat_sessions) or error('Unable to create table "' . IndieGoChat\Config::DB_SESSIONS_TABLE . '"', __FILE__, __LINE__, $db->error());
	$db->create_table(IndieGoChat\Config::DB_COMMANDS_TABLE, $chat_commands) or error('Unable to create table "' . IndieGoChat\Config::DB_COMMANDS_TABLE . '"', __FILE__, __LINE__, $db->error());
}

function restore()
{
	global $db;

	$db->drop_table(IndieGoChat\Config::DB_MESSAGES_TABLE) or error('Unable to drop table "' . IndieGoChat\Config::DB_MESSAGES_TABLE . '"', __FILE__, __LINE__, $db->error());
	$db->drop_table(IndieGoChat\Config::DB_SESSIONS_TABLE) or error('Unable to drop table "' . IndieGoChat\Config::DB_SESSIONS_TABLE . '"', __FILE__, __LINE__, $db->error());
	$db->drop_table(IndieGoChat\Config::DB_COMMANDS_TABLE) or error('Unable to drop table "' . IndieGoChat\Config::DB_COMMANDS_TABLE . '"', __FILE__, __LINE__, $db->error());
}

function reinstall()
{
	restore();
	install();
}

/***********************************************************************/

$style = (isset($pun_user)) ? $pun_user['style'] : $pun_config['o_default_style'];
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Installation of IndieGo Chat Adaptation for FluxBB</title>
<link rel="stylesheet" type="text/css" href="./style/<?php echo $style.'.css' ?>" />
</head>
<body>

<div id="punwrap">
<div id="puninstall" class="pun" style="margin: 10% 20% auto 20%">

<?php

if (isset($_POST['form_sent']))
{
	if (isset($_POST['install']))
	{
		install();

?>
<div class="block">
	<h2><span>Installation was successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully prepared for the IndieGo Chat Adaptation for FluxBB.</p>
		</div>
	</div>
</div>
<?php

	}
	else
	if (isset($_POST['reinstall']))
	{
		reinstall();

?>
<div class="block">
	<h2><span>Reinstallation was successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully refreshed for new version of the IndieGo Chat Adaptation for FluxBB.</p>
		</div>
	</div>
</div>
<?php

	}
	else
	{
		restore();

?>
<div class="block">
	<h2><span>Restore was successful</span></h2>
	<div class="box">
		<div class="inbox">
			<p>Your database has been successfully restored.</p>
		</div>
	</div>
</div>
<?php

	}
}
else
{

?>
<div class="blockform">
	<h2><span>Mod installation</span></h2>
	<div class="box">
		<form method="post" action="./install_chat.php">
			<div><input type="hidden" name="form_sent" value="1" /></div>
			<div class="inform">
				<p>This script will update your database to work with the following modification:</p>
				<p></p>
				<p><strong>IndieGo Chat Adaptation for FluxBB v1.0 (2015-06-12)</strong></p>
				<p><strong>Author: </strong> IndieGo (<a href="mailto:indiego.mttt@gmail.com">indiego.mttt@gmail.com</a>)</p>
				<p><strong>Sponsor:</strong> Volkula (<a href="mailto:volkula@gmail.com">volkula@gmail.com</a>)</p>
				<p></p>
				<p>If you just want to update this mod and haven't changed its configuration yet, you can click the Reinstall button to refresh its tables in your database.</p>
				<p>If you've previously installed this mod and would like to uninstall it, you can click the Restore button below to restore the database.</p>
				<p><strong>WARNING!</strong> If its the first time you are installing this mod, please, check its configuration and set your preferences. Configuration is in "chat/config.php"</p>
			</div>
			<p class="buttons">
				<input type="submit" name="install"   value="Install"   />
				<input type="submit" name="reinstall" value="Reinstall" />
				<input type="submit" name="restore"   value="Restore"   />
			</p>
		</form>
	</div>
</div>
<?php

}

?>

</div>
</div>

</body>
</html>