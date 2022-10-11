<?php


setDir();
require_once 'include/security.php';
require_once 'include/scoring.php';

$_web = isset($_SERVER['HTTP_HOST']);
$_filename = 'complete_competitions.log';
$_file = NULL;
 
if ($_web)
{
	if (isset($_REQUEST['no_log']))
	{
		$_filename = NULL;
	}
	define('MAX_EXEC_TIME', 25);
}
else
{
	define('MAX_EXEC_TIME', 180); // 3 minutes
}

function writeLog($str)
{
	global $_web, $_file, $_filename;
	if ($_web)
	{
		echo $str . " <br>\n";
	}
	
	if ($_filename)
	{
		if (is_null($_file))
		{
			$_file = fopen($_filename, 'a');
			fwrite($_file, '------ ' . date('F d, Y H:i:s', time()) . "\n");
		}
		fwrite($_file, $str . "\n");
	}
}

function setDir()
{
	// Set the current working directory to the directory of the script.
	// This script is sometimes called from the other directories - for auto sending, so we need to change the directory
	$pos = strrpos(__FILE__, '/');
	if ($pos === false)
	{
		$pos = strrpos(__FILE__, '\\');
		if ($pos === false)
		{
			return;
		}
	}
	$dir = substr(__FILE__, 0, $pos);
	chdir($dir);
}

function complete_event()
{
	$result = false;
	Db::begin();
	$query = new DbQuery('SELECT e.id, sv.scoring, e.scoring_options FROM events e JOIN scoring_versions sv ON sv.scoring_id = e.scoring_id AND sv.version = e.scoring_version WHERE e.start_time + e.duration < UNIX_TIMESTAMP() AND (e.flags & ' . EVENT_FLAG_FINISHED . ') = 0 LIMIT 1');
	if ($row = $query->next())
	{
		list($event_id, $scoring, $scoring_options) = $row;
		$scoring = json_decode($scoring);
		$scoring_options = json_decode($scoring_options);
		
		Db::exec(get_label('event'), 'DELETE FROM event_places WHERE event_id = ?', $event_id);
		$players = event_scores($event_id, null, SCORING_LOD_PER_GROUP, $scoring, $scoring_options);
		$players_count = count($players);
		if ($players_count > 0)
		{
			$coeff = log10($players_count) / $players_count;
			for ($number = 0; $number < $players_count; ++$number)
			{
				$player = $players[$number];
				$importance = ($players_count - $number) * $coeff;
				if ($number == 0)
				{
					$importance *= 10;
				}
				else if ($number <= 3)
				{
					$importance *= 5;
				}
				else if ($number <= 10)
				{
					$importance *= 2;
				}
				Db::exec(get_label('player'), 'INSERT INTO event_places (event_id, user_id, place, importance) VALUES (?, ?, ?, ?)', $event_id, $player->id, $number + 1, $importance);
			}
		}
		Db::exec(get_label('event'), 'UPDATE events SET flags = flags | ' . EVENT_FLAG_FINISHED .  ' WHERE id = ?', $event_id);
		writeLog('Wrote ' . $players_count . ' players to event ' . $event_id . '. Event is finished.');
		$result = true;
	}
	Db::commit();
	return $result;
}

function complete_tournament()
{
	$result = false;
	Db::begin();
	
	$query = new DbQuery(
		'SELECT t.id, t.flags, sv.scoring, t.scoring_options, nv.normalizer, (SELECT max(st.stars) FROM series_tournaments st WHERE st.tournament_id = t.id) as stars FROM tournaments t' . 
		' JOIN scoring_versions sv ON sv.scoring_id = t.scoring_id AND sv.version = t.scoring_version' .
		' LEFT OUTER JOIN normalizer_versions nv ON nv.normalizer_id = t.normalizer_id AND nv.version = t.normalizer_version' .
		' WHERE t.start_time + t.duration < UNIX_TIMESTAMP() AND (t.flags & ' . TOURNAMENT_FLAG_FINISHED . ') = 0 LIMIT 1');
	if ($row = $query->next())
	{
		list($tournament_id, $tournament_flags, $scoring, $scoring_options, $normalizer, $stars) = $row;
		
		// find out minimum player games to count tournament for a player
		$min_games = 0;
		$sum_games = 0;
		$player_count = 0;
		$player_games = array();
		$query1 = new DbQuery('SELECT p.user_id, count(g.id) FROM players p JOIN games g ON g.id = p.game_id JOIN events e ON e.id = g.event_id WHERE e.tournament_id = ? AND (e.flags & ' . EVENT_FLAG_WITH_SELECTION . ') = 0 GROUP BY p.user_id', $tournament_id);
		while ($row1 = $query1->next())
		{
			list($player_id, $games_played) = $row1;
			$sum_games += $games_played;
			++$player_count;
		}
		if ($player_count > 0)
		{
			// The tournament counts for a player only if they played more than a half of average games count. 
			// We do it in a separate query because we calculate average using only main rounds - excluding finals and semi-finals.
			$min_games = $sum_games / ($player_count * 2); 
		}
		
		// Write down the tournament places
		if (is_null($stars))
		{
			$stars = 1;
		}
		$scoring = json_decode($scoring);
		$scoring_options = json_decode($scoring_options);
		if (!is_null($normalizer))
		{
			$normalizer = json_decode($normalizer);
		}
		
		Db::exec(get_label('tournament'), 'DELETE FROM tournament_places WHERE tournament_id = ?', $tournament_id);
		$players = tournament_scores($tournament_id, $tournament_flags, null, SCORING_LOD_PER_GROUP, $scoring, $normalizer, $scoring_options);
		$players_count = count($players);
		
		$real_count = 0;
		if ($players_count > 0)
		{
			$coeff = log10($players_count) * $stars / $players_count;
			for ($number = 0; $number < $players_count; ++$number)
			{
				$player = $players[$number];
				if ($player->games_count <= $min_games)
				{
					continue;
				}
				
				$importance = ($players_count - $real_count) * $coeff;
				if ($real_count == 0)
				{
					$importance *= 10;
				}
				else if ($real_count <= 3)
				{
					$importance *= 5;
				}
				else if ($real_count <= 10)
				{
					$importance *= 2;
				}
				++$real_count;
				Db::exec(get_label('player'), 'INSERT INTO tournament_places (tournament_id, user_id, place, importance) VALUES (?, ?, ?, ?)', $tournament_id, $player->id, $real_count, $importance);
			}
		}
		Db::exec(get_label('tournament'), 'UPDATE tournaments SET flags = flags | ' . TOURNAMENT_FLAG_FINISHED .  ' WHERE id = ?', $tournament_id);
		writeLog('Wrote ' . $real_count . ' (out of ' . $players_count . ') players to tournament ' . $tournament_id . '. Minimum games requered for a player is ' . $min_games . ' Tournament is finished.');
		$result = true;
	}
	Db::commit();
	return $result;
}

function complete_series()
{
	return false;
}

try
{
	date_default_timezone_set('America/Vancouver');
	if ($_web)
	{
		initiate_session();
		check_permissions(PERMISSION_ADMIN);
	}
	
	$exec_start_time = time();
	$spent_time = 0;
	$count = 0;
	while ($spent_time < MAX_EXEC_TIME)
	{
		if (!complete_series() && !complete_tournament() && !complete_event())
		{
			break;
		}
		$spent_time = time() - $exec_start_time;
		++$count;
	}
	writeLog('It took ' . $spent_time . ' sec.');
	// if ($_web && $count > 0)
	// {
		// echo '<script>window.location.reload();</script>';
	// }
}
catch (Exception $e)
{
	Db::rollback();
	Exc::log($e, true);
	writeLog($e->getMessage());
}

if (!is_null($_file))
{
	fclose($_file);
}

?>