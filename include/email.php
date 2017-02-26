<?php

require_once 'include/rand_str.php';
require_once 'include/server.php';
require_once 'include/message.php';

define('EMAIL_OBJ_EVENT', 0);
define('EMAIL_OBJ_MESSAGE', 1);
define('EMAIL_OBJ_PHOTO', 2);
define('EMAIL_OBJ_SIGN_IN', 3);
define('EMAIL_OBJ_CREATE_CLUB', 4);
define('EMAIL_OBJ_CONFIRM_EVENT', 5);
define('EMAIL_OBJ_EVENT_NO_USER', 6);

function show_email_tags($event_tags)
{
	echo '<table class="transp" width="100%">';
	echo '<tr><td width="150"><b>'.get_label('Email tags').':</b></td><td align="right"><input type="submit" class="btn norm" name="'.get_label('preview').'" value="'.get_label('Preview email').'"></td></tr>';
	echo '<tr><td>'.get_label('Unsubscribe button').'</td><td><b>[unsub]</b>'.get_label('Button text').'<b>[/unsub]</b></td></tr>';
	if ($event_tags)
	{
		echo '<tr><td>'.get_label('Accept button').'</td><td><b>[accept]</b>'.get_label('Button text').'<b>[/accept]</b></td></tr>';
		echo '<tr><td>'.get_label('Decline button').'</td><td><b>[decline]</b>'.get_label('Button text').'<b>[/decline]</b></td></tr>';
		echo '<tr><td>'.get_label('Event name').'</td><td><b>[ename]</b></td></tr>';
		echo '<tr><td>'.get_label('Event id').'</td><td><b>[eid]</b></td></tr>';
		echo '<tr><td>'.get_label('Event date').'</td><td><b>[edate]</b></td></tr>';
		echo '<tr><td>'.get_label('Event time').'</td><td><b>[etime]</b></td></tr>';
		echo '<tr><td>'.get_label('Event address').'</td><td><b>[addr]</b></td></tr>';
		echo '<tr><td>'.get_label('Event address URL').'</td><td><b>[aurl]</b></td></tr>';
		echo '<tr><td>'.get_label('Event address id').'</td><td><b>[aid]</b></td></tr>';
		echo '<tr><td>'.get_label('Event address image').'</td><td><b>[aimage]</b></td></tr>';
		echo '<tr><td>'.get_label('Event notes').'</td><td><b>[notes]</b></td></tr>';
	}
	echo '<tr><td>'.get_label('User name').'</td><td><b>[uname]</b></td></tr>';
	echo '<tr><td>'.get_label('User id').'</td><td><b>[uid]</b></td></tr>';
	echo '<tr><td>'.get_label('User email').'</td><td><b>[email]</b></td></tr>';
	echo '<tr><td>'.get_label('Club name').'</td><td><b>[cname]</b></td></tr>';
	echo '<tr><td>'.get_label('Club id').'</td><td><b>[cid]</b></td></tr>';
	echo '<tr><td>'.get_label('Email code').'</td><td><b>[code]</b></td></tr>';
	echo '</table>';
}

function send_email($email, $body, $subject, $unsubs_url = NULL)
{
	if (is_production_server())
	{
		$headers =
			"From: godfather@mafiaratings.com\r\n" .
			"Reply-To: godfather@mafiaratings.com\r\n" .
			"Precedence: bulk\r\n" .
			"Return-Path: <godfather@mafiaratings.com>\r\n" .
			"Content-Type: text/html; charset=UTF-8\r\n" .
			"X-Mailer: PHP/" . phpversion() . "\r\n";

		if ($unsubs_url != NULL)
		{
			$headers .= "List-Unsubscribe: <" . $unsubs_url . ">\r\n";
		}
		if (!mail($email, $subject, $body, $headers))
		{
			throw new Exc(get_label('Failed to send email "[1]" to [0]', $email, $subject));
		}
	}
	else if (!defined('GATE'))
	{
		echo '<center><table class="bordered" width="100%"><tr><td>' . get_label('Email has been sent') . ':</td></tr><tr><td>To:' . $email . '</td></tr><tr><td>' . $body . '</td></tr></table></center>';
	}
}

function generate_email_code()
{
	return md5(rand_string(10));
}

class EmailCommiter extends DbCommiter
{
	private $email;
	private $body;
	private $subject;
	private $unsubs_url;
	
	public function __construct($email, $body, $subject, $unsubs_url)
	{
		$this->email = $email;
		$this->body = $body;
		$this->subject = $subject;
		$this->unsubs_url = $unsubs_url;
	}

	public function commit()
	{
		send_email($this->email, $this->body, $this->subject, $this->unsubs_url);
	}
}

function send_notification($email, $body, $subject, $user_id, $obj, $obj_id, $code)
{
	$email = trim($email);
	if ($email == '')
	{
		return false;
	}

	$url = 'http://' . get_server_url() . '/email_request.php';
	$unsubs_url = $url . '?uid=' . $user_id . '&code=' . $code . '&unsub=1';
	$body =
		'<body color="#303030" bgcolor="#cccccc">' .
		'<form method="get" action="' . $url . '">' . 
		'<input type="hidden" name="uid" value="' . $user_id . '">' .
		'<input type="hidden" name="code" value="' . $code . '">' . $body .
		'</form></body>';
		
	Db::exec(
		get_label('email'), 
		'INSERT INTO emails (user_id, code, send_time, obj, obj_id) VALUES (?, ?, ?, ?, ?)', 
		$user_id, $code, time(), $obj, $obj_id);
	
	Db::add_commiter(new EmailCommiter($email, $body, $subject, $unsubs_url));
}

?>