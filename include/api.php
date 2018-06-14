<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/api_help.php';

define('API_PERM_FLAG_EVERYONE', 0x0001);
define('API_PERM_FLAG_USER', 0x0002);
define('API_PERM_FLAG_OWNER', 0x0004);
define('API_PERM_FLAG_MEMBER', 0x0008);
define('API_PERM_FLAG_OFFICIAL', 0x0010);
define('API_PERM_FLAG_PLAYER', 0x0020);
define('API_PERM_FLAG_MODERATOR', 0x0040);
define('API_PERM_FLAG_MANAGER', 0x0080);
define('API_PERM_FLAG_ADMIN', 0x0100);

function api_permission_name($flag)
{
	switch($flag)
	{
		case API_PERM_FLAG_EVERYONE:
			return 'everyone';
		case API_PERM_FLAG_USER:
			return 'user';
		case API_PERM_FLAG_OWNER:
			return 'object-owner';
		case API_PERM_FLAG_MEMBER:
			return 'club-member';
		case API_PERM_FLAG_OFFICIAL:
			return 'club-official';
		case API_PERM_FLAG_PLAYER:
			return 'club-player';
		case API_PERM_FLAG_MODERATOR:
			return 'club-moderator';
		case API_PERM_FLAG_MANAGER:
			return 'club-manager';
		case API_PERM_FLAG_ADMIN:
			return 'admin';
	}
	return '?';
}

class ApiPageBase
{
	protected $version;
	protected $latest_version;
	protected $response;
	protected $title;
	
	function __construct()
	{
		initiate_session();
	}
	
	protected function _run($title, $version, $permissions)
	{
		global $_profile;
		
		$this->title = $title;
		$this->latest_version = $this->version = (int)$version;
		if ($this->version >= 0 && isset($_REQUEST['version']))
		{
			$this->version = (int)$_REQUEST['version'];
		}
		
		if (isset($_REQUEST['help']))
		{
			echo '<!DOCTYPE HTML>';
			echo '<html>';
			echo '<head>';
			echo '<title>' . PRODUCT_NAME . ' ' . $title . ' API</title>';
			echo '<META content="text/html; charset=utf-8" http-equiv=Content-Type>';
			echo '<link rel="stylesheet" href="../api.css" type="text/css" media="screen" />';
			echo '</head><body>';
			try
			{
				if ($this->version > $version)
				{
					throw new FatalExc('Version ' . $this->version . ' is not supported by ' . $title . ' API. Current version is ' . $version . '.');
				}
				$this->show_help();
			}
			catch (RedirectExc $e)
			{
				$url = $e->get_url();
				header('location: ' . $url);
				echo '<p>Redirecting to ' . $url . '</p>';
			}
			catch (Exception $e)
			{
				Exc::log($e, true);
				echo '<p>Error: ' . $e->getMessage() . '</p>';
			}
			echo '</body></html>';
		}
		else
		{
			ob_start();
			try
			{
				check_permissions($permissions);
				
				// Admins should able to make requests during the maintanence. 
				// Because they are the ones who is doing the maintanence.
				if ($_profile == NULL || !$_profile->is_admin())
				{					
					check_maintenance();
				}
				
				if ($this->version > $version)
				{
					// No localization because this is an assert. The calling code must fix it.
					throw new FatalExc('Version ' . $this->version . ' is not supported by ' . $title . ' API. Current version is ' . $version . '.');
				}
				
				$this->prepare_response();
			}
			catch (LoginExc $e)
			{
				$this->response['login'] = $e->get_user_name();
			}
			catch (RedirectExc $e)
			{
				Db::rollback();
				$url = $e->get_url();
				header('location: ' . $url);
				$this->response['redirect'] = $url;
			}
			catch (Exception $e)
			{
				Db::rollback();
				Exc::log($e, true);
				$this->response['error'] = $e->getMessage();
			}
			
			if ($this->version >= 0)
			{
				$this->response['version'] = $this->version;
			}
			
			$message = ob_get_contents();
			ob_end_clean();
			if ($message != '')
			{
				if (isset($this->response['message']))
				{
					$message = $this->response['message'] . '<hr>' . $message;
				}
				$this->response['message'] = $message;
			}
			echo json_encode($this->response);
		}
	}
	
	protected function prepare_response()
	{
	}
	
	protected function add_default_help_params($help)
	{
		if ($this->version >= 0)
		{
			$descr = 'Requiered ' . $this->title . ' API version. It is recommended to set it. It guarantees that the format of a data you receive is never changed. 
				Note that <q>version</q> is the only parameter that can be used together with <q>help</q>. Current version is ' . $this->latest_version . '.';
			if ($this->latest_version != $this->version)
			{
				$descr .= ' This help shows data format for version ' . $this->version . '.';
			}
			
			$help->request_param('version', $descr, 'latest version is used'); 
		}
		$help->request_param('help', 'Shows this screen.', '-');
		
		$help->response_param('error', 
			'Error message. Successful requests never have this field. If a caller wants to check if the request is successful, 
			it is enough to check if "error" is missing.
			<p>Normally error messages are using account default language or language specified by "lang" parameter. However 
			some of them are just in English. For example, a missing required parameter generates an English message because 
			this is rather an assert. It is caused by a bug in a calling code. Users never see it if the caller code is correct.</p>');
		$help->response_param('version', $this->title . ' API version used for proceeding the request.');
		
		return $help;
	}
	
	protected function show_help()
	{
		echo '<h1>' . $this->title . ' API</h1>';
		echo '<p><a href="index.php">Back to the service list.</a></p>';
		
		$help = $this->add_default_help_params($this->get_help());
		
		echo '<p>' . $help->text . '</p>';
		echo '<h2>Request Parameters:</h2><dl>';
		foreach ($help->request as $param)
		{
			$param->show();
		}
		echo '</dl>';
		
		echo '<h2>Response Parameters:</h2><dl>';
		foreach ($help->response as $param)
		{
			$param->show();
		}
		echo '</dl>';
	}
	
	protected function get_help()
	{
		throw new Exc('Help is not available for ' . $this->title);
	}
}

class GetApiPageBase extends ApiPageBase
{
	function __construct()
	{
		if (isset($_REQUEST['lang']))
		{
			initiate_session($_REQUEST['lang']);
		}
		else
		{
			initiate_session();
		}
	}
	
	final function run($title, $version, $permissions = PERM_ALL)
	{
		$this->_run($title, $version, $permissions);
	}
	
	protected function show_help_request_params_head()
	{
		parent::show_help_request_params_head();
?>
		<dt>lang</dt>
			<dd>What is the preferable language for returning results. Currently two languages are supported: Russian and English. This parameter should be either "ru" or "en" respectively. Profile language is used when this param is not set.</dd>
<?php
	}
}

class ControlApiPageBase extends ApiPageBase
{
	final function run($title, $version = -1, $permissions = PERM_ALL)
	{
		$this->_run($title, $version, $permissions);
	}
}

class OpsApiPageBase extends ApiPageBase
{
	final function run($title, $version, $permissions = PERM_USER)
	{
		$this->_run($title, $version, $permissions);
	}
	
	private function get_permissions($op)
	{
		$permission_func = $op . '_op_permissions';
		if (method_exists($this, $permission_func))
		{
			return $this->$permission_func();
		}
		return API_PERM_FLAG_USER;
	}
	
	protected function is_allowed($op, $club_id = 0, $owner_id = 0)
	{
		global $_profile;
		$perm = $this->get_permissions($op);
		if (($perm & API_PERM_FLAG_EVERYONE) != 0)
		{
			return true;
		}
		
		if ($_profile == NULL)
		{
			return false;
		}
		
		if ($_profile->is_admin())
		{
			return true;
		}
		
		while ($perm)
		{
			$next_perm = ($perm & ($perm - 1));
			switch ($perm - $next_perm)
			{
				case API_PERM_FLAG_USER:
					return true;
					
				case API_PERM_FLAG_OWNER:
					if ($owner_id == $_profile->user_id)
					{
						return true;
					}
					break;
					
				case API_PERM_FLAG_MEMBER:
					if (isset($_profile->clubs[$club_id]))
					{
						return true;
					}
					break;
					
				case API_PERM_FLAG_OFFICIAL:
					if ($_profile->user_club_id == $club_id)
					{
						return true;
					}
					break;
					
				case API_PERM_FLAG_PLAYER:
					if ($_profile->is_player($club_id))
					{
						return true;
					}
					break;
					
				case API_PERM_FLAG_MODERATOR:
					if ($_profile->is_moder($club_id))
					{
						return true;
					}
					break;
					
				case API_PERM_FLAG_MANAGER:
					if ($_profile->is_manager($club_id))
					{
						return true;
					}
					break;
			}
			$perm = $next_perm;
		}
		return false;
	}
	
	protected function check_permissions($club_id = 0, $owner_id = 0)
	{
		global $_profile;
		
		if (!$this->is_allowed($_REQUEST['op'], $club_id, $owner_id))
		{
			if ($_profile == NULL)
			{
				throw new LoginExc();
			}
			throw new FatalExc(get_label('No permissions'));
		}
	}
	
	protected function prepare_response()
	{
		if (!isset($_REQUEST['op']))
		{
			// No localization because this is an assert. The calling code must fix it.
			throw new Exc('Operation is not specified for the ' . $this->title . ' web request.');
		}
		$op = $_REQUEST['op'];
		
		$func = $op . '_op';
		if (!method_exists($this, $func))
		{
			// No localization because this is an assert. The calling code must fix it.
			throw new Exc('Unknown operation "' . $op . '" in the ' . $this->title . ' web request.');
		}
		$this->$func();
	}
	
	protected function show_help()
	{
		echo '<h1>' . $this->title . ' API</h1>';
		echo '<p><a href="index.php">Back to the service list.</a></p>';
		
		$current_op = NULL;
		if (isset($_REQUEST['op']))
		{
			$current_op = $_REQUEST['op'];
			if ($current_op == 'show')
			{
				$current_op = NULL;
			}
		}
		
		$methods = get_class_methods(get_class($this));
		// echo '<pre>';
		// print_r($methods);
		// echo '</pre>';
		
		echo '<form name="op_form" method="get" action="' . $_SERVER['SCRIPT_NAME'] . '"><input type="hidden" name="help" value="">' . $this->title . ': ';
		echo '<select name="op"  onchange="document.op_form.submit()">';
		foreach ($methods as $method)
		{
			if (substr($method, -8) == '_op_help')
			{
				$op = substr($method, 0, -8);
				if ($op == 'show')
				{
					continue;
				}
				if ($current_op == NULL)
				{
					$current_op = $op;
				}
				show_option($op, $current_op, $op);
			}
		}
		echo '</select></form>';
		
		if ($current_op == NULL)
		{
			throw new Exc('No help availible for ' . $this->title);
		}
		$help_func = $current_op . '_op_help';
		if (!method_exists($this, $help_func))
		{
			throw new Exc('No help availible for operation "' . $current_op . '" in ' . $this->title);
		}
		$help = $this->add_default_help_params($this->$help_func());
		
		
		echo '<h1>Operation: ' . $current_op . '</h1>';
		echo '<p>' . $help->text . '</p>';
		
		echo '<p><strong>Required permissions:</strong> ';
		$perm = $this->get_permissions($current_op);
		$next_perm = ($perm & ($perm - 1));
		if ($perm != $next_perm)
		{
			echo '<em>' . api_permission_name($perm - $next_perm) . '</em>';
			$perm = $next_perm;
			while ($perm != 0)
			{
				$next_perm = ($perm & ($perm - 1));
				echo ', or <em>' . api_permission_name($perm - $next_perm) . '</em>';
				$perm = $next_perm;
			}
		}
		echo '.</p>';
		
		echo '<h2>Request Parameters:</h2><dl>';
		foreach ($help->request as $param)
		{
			$when_missing = NULL;
			if (isset($param->when_missing))
			{
				$when_missing = $param->when_missing;
			}
			echo '<dt>' . $param->name;
			if ($when_missing == NULL)
			{
				echo ' <small>(required)</small>';
			}
			echo '</dt><dd>' . $param->description;
			if ($when_missing != NULL && $when_missing != '-')
			{
				echo '<p><dfn>When missing:</dfn> ' . $when_missing . '</p>';
			}
			echo '</dd>';
		}
		echo '</dl>';
		
		echo '<h2>Response Parameters:</h2><dl>';
		foreach ($help->response as $param)
		{
			echo '<dt>' . $param->name . '</dt><dd>' . $param->description . '</dd>';
		}
		echo '</dl>';
	}
}

function get_required_param($param)
{
	if (!isset($_REQUEST[$param]))
	{
		// No localization because this is an assert. The calling code must fix it.
		throw new Exc('"' . $param . '" must be set in ' . $_SERVER['REQUEST_URI']);
	}
	return $_REQUEST[$param];
}

function get_optional_param($param, $def_value = '')
{
	if (!isset($_REQUEST[$param]))
	{
		return $def_value;
	}
	return $_REQUEST[$param];
}

?>