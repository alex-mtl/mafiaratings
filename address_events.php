<?php

require_once 'include/page_base.php';
require_once 'include/player_stats.php';
require_once 'include/club.php';
require_once 'include/address.php';
require_once 'include/pages.php';
require_once 'include/event.php';

define("PAGE_SIZE",15);

class Page extends AddressPageBase
{
	protected function prepare()
	{
		parent::prepare();
		$this->_title = get_label('[0] ratings', $this->name);
	}

	protected function show_body()
	{
		global $_profile, $_page;
		
		$show_empty = isset($_REQUEST['emp']);
		
		echo '<form method="get" name="clubForm">';
		echo '<input type="hidden" name="id" value="' . $this->id . '">';
		echo '<table class="transp" width="100%"><tr><td align="right">';
		echo '<input type="checkbox" name="emp"';
		if ($show_empty)
		{
			echo ' checked';
		}
		echo ' onclick="document.clubForm.submit()"> ' . get_label('Show events with no games');
		echo '</td></tr></table></form>';
		
		$condition = new SQL(' FROM events e WHERE e.address_id = ? AND e.start_time < UNIX_TIMESTAMP() AND (e.flags & ' . EVENT_FLAG_CANCELED .  ') = 0', $this->id);
		if (!$show_empty)
		{
			$condition->add(' AND EXISTS (SELECT g.id FROM games g WHERE g.event_id = e.id)');
		}
		
		list ($count) = Db::record(get_label('event'), 'SELECT count(*)', $condition);
		show_pages_navigation(PAGE_SIZE, $count);

		$query = new DbQuery(
			'SELECT e.id, e.name, e.start_time, e.flags,' .
				' (SELECT count(*) FROM games WHERE event_id = e.id AND result IN (1, 2)) as games,' .
				' (SELECT count(*) FROM registrations WHERE event_id = e.id) as users',
			$condition);
		$query->add(' ORDER BY e.start_time DESC LIMIT ' . ($_page * PAGE_SIZE) . ',' . PAGE_SIZE);
			
		echo '<table class="bordered light" width="100%">';
		echo '<tr class="th-long darker">';
		echo '<td colspan="2">' . get_label('Event') . '</td>';
		echo '<td>' . get_label('Address') . '</td>';
		echo '<td width="60" align="center">' . get_label('Games played') . '</td>';
		echo '<td width="60" align="center">' . get_label('Players attended') . '</td></tr>';
		
		while ($row = $query->next())
		{
			list ($event_id, $event_name, $event_time, $event_flags, $games_count, $users_count) = $row;
			
			echo '<tr>';
			
			echo '<td width="50" class="dark"><a href="event_players.php?bck=1&id=' . $event_id . '">';
			show_event_pic($event_id, $event_flags, $this->id, $this->flags, ICONS_DIR, 50);
			echo '</a></td>';
			echo '<td width="180">' . $event_name . '<br><b>' . format_date('l, F d, Y', $event_time, $this->timezone) . '</b></td>';
			
			echo '<td>' . $this->address . '</td>';
			
			echo '<td align="center"><a href="event_games.php?bck=1&id=' . $event_id . '">' . $games_count . '</a></td>';
			echo '<td align="center"><a href="event_players.php?bck=1&id=' . $event_id . '">' . $users_count . '</a></td>';
			
			echo '</tr>';
		}
		echo '</table>';
	}
}

$page = new Page();
$page->run(get_label('Events'), PERM_ALL);

?>