<?php

require_once 'include/db.php';
require_once 'include/names.php';

function create_template($name, $subj, $body, $club_id)
{
	global $_profile;
	
	Db::begin();
	check_email_template_name($name, $club_id);

	Db::exec(
		get_label('email template'), 
		'INSERT INTO email_templates (club_id, name, subject, body) VALUES (?, ?, ?, ?)',
		$club_id, $name, $subj, $body);
	list ($id) = Db::record(get_label('email template'), 'SELECT LAST_INSERT_ID()');
	$log_details =
		'name=' . $name .
		"<br>subj=" . $subj .
		"<br>body=<br>" . $body;
	db_log('email_template', 'Created', $log_details, $id, $club_id);
	Db::commit();
}

function update_template($id, $name, $subj, $body)
{
	global $_profile;
	
	list ($club_id) = Db::record(get_label('email template'), 'SELECT club_id FROM email_templates WHERE id = ?', $id);
	if ($_profile == NULL || !$_profile->is_manager($club_id))
	{
		throw new FatalExc(get_label('No permissions'));
	}
	
	check_email_template_name($name, $club_id, $id);

	Db::begin();
	Db::exec(get_label('email template'), 
		'UPDATE email_templates SET name = ?, subject = ?, body = ? WHERE id = ?',
		$name, $subj, $body, $id);
	if (Db::affected_rows() > 0)
	{
		$log_details =
			'name=' . $name .
			"<br>subj=" . $subj .
			"<br>body=<br>" . $body;
		db_log('email_template', 'Changed', $log_details, $id, $club_id);
	}
	Db::commit();
}

?>