<?php

require_once 'include/session.php';
require_once 'include/city.php';
require_once 'include/country.php';
require_once 'include/timezone.php';

initiate_session();

try
{
	if (!isset($_REQUEST['game']))
	{
		throw new FatalExc(get_label('Unknown [0]', get_label('game')));
	}
	$id = $_REQUEST['game'];
	
	list($video) = Db::record(get_label('game'), 'SELECT video FROM games WHERE id = ?', $id);
		
	dialog_title(get_label('Game [0] video', $id));
		
	$url = 'https://www.youtube.com/watch?v=' . $video;
	echo '<p><a href="' . $url . '" target="_blank">' . $url . '</a></p>';
	echo '<p><iframe title="YouTube video player" width="780" height="460" src="https://www.youtube.com/embed/' . $video . '" frameborder="0" allowfullscreen></iframe></p>';
	echo '<ok>';
}
catch (Exception $e)
{
	Exc::log($e);
	echo $e->getMessage();
}

?>