<?php

require_once 'include/page_base.php';
require_once 'include/player_stats.php';
require_once 'include/club.php';
require_once 'include/languages.php';
require_once 'include/address.php';

define('ADDR_COLUMN_COUNT', 5);
define('ADDR_COLUMN_WIDTH', (100 / ADDR_COLUMN_COUNT));


class Page extends AddressPageBase
{
	protected function prepare()
	{
		parent::prepare();
		$this->_title = $this->name;
	}
	
	protected function show_body()
	{
		$playing_count = 0;
		$civils_win_count = 0;
		$mafia_win_count = 0;
		$terminated_count = 0;
		$query = new DbQuery('SELECT g.result, count(*) FROM games g JOIN events e ON g.event_id = e.id WHERE e.address_id = ? GROUP BY g.result', $this->id);
		while ($row = $query->next())
		{
			switch ($row[0])
			{
				case 0:
					$playing_count = $row[1];
					break;
				case 1:
					$civils_win_count = $row[1];
					break;
				case 2:
					$mafia_win_count = $row[1];
					break;
				case 3:
					$terminated_count = $row[1];
					break;
			}
		}
		$games_count = $civils_win_count + $mafia_win_count + $playing_count + $terminated_count;
		
		list($events_count) = Db::record(get_label('event'), 'SELECT count(*) FROM events e WHERE (e.flags & ' . EVENT_FLAG_CANCELED . ') = 0 AND start_time < UNIX_TIMESTAMP() AND e.address_id = ?', $this->id);
	
		if ($games_count > 0)
		{
			// stats
			echo '<p><table class="bordered light" width="100%">';
			echo '<tr class="th-short darker"><td colspan="2">' . get_label('Stats') . '</td></tr>';
			echo '<tr><td class="dark" width="200">'.get_label('Events held').':</td><td>' . $events_count . '</td></tr>';
			echo '<tr><td class="dark">'.get_label('Games played').':</td><td>' . ($civils_win_count + $mafia_win_count) . '</td></tr>';
			if ($civils_win_count + $mafia_win_count > 0)
			{
				echo '<tr><td class="dark">'.get_label('Mafia won in').':</td><td>' . $mafia_win_count . ' (' . number_format($mafia_win_count*100.0/($civils_win_count + $mafia_win_count), 1) . '%)</td></tr>';
				echo '<tr><td class="dark">'.get_label('Civilians won in').':</td><td>' . $civils_win_count . ' (' . number_format($civils_win_count*100.0/($civils_win_count + $mafia_win_count), 1) . '%)</td></tr>';
			}
			if ($terminated_count > 0)
			{
				echo '<tr><td class="dark">'.get_label('Games terminated').':</td><td>' . $terminated_count . ' (' . number_format($terminated_count*100.0/($terminated_count + $civils_win_count + $mafia_win_count), 1) . '%)</td></tr>';
			}
			if ($playing_count > 0)
			{
				echo '<tr><td class="dark">'.get_label('Still playing').':</td><td>' . $playing_count . '</td></tr>';
			}
			
			if ($civils_win_count + $mafia_win_count > 0)
			{
				list ($counter) = Db::record(get_label('game'), 'SELECT COUNT(DISTINCT p.user_id) FROM players p JOIN games g ON p.game_id = g.id JOIN events e ON g.event_id = e.id WHERE e.address_id = ?', $this->id);
				echo '<tr><td class="dark">'.get_label('People played').':</td><td>' . $counter . '</td></tr>';
				
				list ($counter) = Db::record(get_label('game'), 'SELECT COUNT(DISTINCT g.moderator_id) FROM games g JOIN events e ON g.event_id = e.id WHERE e.address_id = ?', $this->id);
				echo '<tr><td class="dark">'.get_label('People moderated').':</td><td>' . $counter . '</td></tr>';
				
				list ($a_game, $s_game, $l_game) = Db::record(get_label('game'), 'SELECT AVG(g.end_time - g.start_time), MIN(g.end_time - g.start_time), MAX(g.end_time - g.start_time) FROM games g JOIN events e ON g.event_id = e.id WHERE g.result > 0 AND g.result < 3 AND e.address_id = ?', $this->id);
				echo '<tr><td class="dark">'.get_label('Average game duration').':</td><td>' . format_time($a_game) . '</td></tr>';
				echo '<tr><td class="dark">'.get_label('Shortest game').':</td><td>' . format_time($s_game) . '</td></tr>';
				echo '<tr><td class="dark">'.get_label('Longest game').':</td><td>' . format_time($l_game) . '</td></tr>';
			}
			echo '</table></p>';
		}
	}
}

$page = new Page();
$page->run(get_label('Address'), PERM_ALL);

?>