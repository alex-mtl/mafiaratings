<?php

require_once 'include/user.php';
require_once 'include/player_stats_all.php';
require_once 'include/club.php';
require_once 'include/scoring.php';
require_once 'include/checkbox_filter.php';
require_once 'include/datetime.php';

define('FLAG_FILTER_TOURNAMENT', 0x0001);
define('FLAG_FILTER_NO_TOURNAMENT', 0x0002);
define('FLAG_FILTER_RATING', 0x0004);
define('FLAG_FILTER_NO_RATING', 0x0008);

define('FLAG_FILTER_DEFAULT', 0);

class Page extends UserPageBase
{
	protected function show_body()
	{
		$club_id = 0;
		if (isset($_REQUEST['club']))
		{
			$club_id = $_REQUEST['club'];
		}

		$roles = POINTS_ALL;
		if (isset($_REQUEST['roles']))
		{
			$roles = (int)$_REQUEST['roles'];
		}

		$year = 0;
		if (isset($_REQUEST['year']))
		{
			$year = (int)$_REQUEST['year'];
		}

		$filter = FLAG_FILTER_DEFAULT;
		if (isset($_REQUEST['filter']))
		{
			$filter = (int)$_REQUEST['filter'];
		}

		$min_time = $max_time = 0;
		$query = new DbQuery('SELECT MIN(game_end_time), MAX(game_end_time) FROM players WHERE user_id = ?', $this->id);
		if ($row = $query->next())
		{
			list($min_time, $max_time) = $row;
		}

		echo '<table class="transp" width="100%"><tr><td>';
		show_year_select($year, $min_time, $max_time, 'filterChanged()');

		echo ' ';
//		show_checkbox_filter(array(get_label('tournament games'), get_label('rating games')), $filter, 'filterChanged');
		echo '</td></tr></table>';

		$condition = get_year_condition($year);
//		if ($filter & FLAG_FILTER_TOURNAMENT)
//		{
			$condition->add(' AND g.tournament_id IS NOT NULL');
//		}
//		if ($filter & FLAG_FILTER_NO_TOURNAMENT)
//		{
//			$condition->add(' AND g.tournament_id IS NULL');
//		}
//		if ($filter & FLAG_FILTER_RATING)
//		{
//			$condition->add(' AND g.is_rating <> 0');
//		}
//		if ($filter & FLAG_FILTER_NO_RATING)
//		{
//			$condition->add(' AND g.is_rating <> 0');
//		}
		$stats = new PlayerStatsAll($this->id, $condition);

		$this->generateTable(
                $stats,
                'Playing',
                [
                    ['Games played', 'games_played'],
                    ['Wins', 'games_won', '*100/', ['games_played',1],'%'],

                    ['Rating', ['rating', 2], '/', ['games_played',3], ' per game'],
                    ['Best player', 'best_player', '*100/', ['games_played',1],'%'],
                    ['Best move', 'best_move', '*100/', ['games_played',1],'%'],
                    ['Auto-bonus removed', 'worst_move', '/', ['games_played',1],'%'],
                    ['Bonus points', ['bonus',2], '/', ['games_played',3],' per game'],
                    ['Killed first night', 'killed_first_night', '*100/', ['games_played',1],'%'],
                    [['Guessed [0] mafia', 3], 'guess3maf', '*100/', ['killed_first_night',1],'%'],
                    [['Guessed [0] mafia', 2], 'guess2maf', '*100/', ['killed_first_night',1],'%'],
                    [['Guessed [0] mafia', 1], 'guess1maf', '*100/', ['killed_first_night',1],'%'],
                    ['Mafia in legacy', 'mafs_in_legacy', '*100/', ['killed_first_night3',1],'%'],
                ]
        );

        $this->generateTable(
            $stats,
            'Voting',
            [
                ['Voted against civilians', 'voted_civil', '*100/', ['voted_count',1],'%'],
                ['Voted against mafia', 'voted_mafia', '*100/', ['voted_count',1],'%'],
                ['Voted against sheriff', 'voted_sheriff', '*100/', ['voted_count',1],'%'],
                ['Was voted by civilians', 'voted_by_civil', '*100/', ['voted_by_count',1],'%'],
                ['Was voted by mafia', 'voted_by_mafia', '*100/', ['voted_by_count',1],'%'],
                ['Was voted by sheriff', 'voted_by_sheriff', '*100/', ['voted_by_count',1],'%']
            ]
        );

        $this->generateTable(
            $stats,
            'Nominating',
            [
                ['Nominated civilians', 'nominated_civil', '*100/', ['nominated_count',1],'%'],
                ['Nominated mafia', 'nominated_mafia', '*100/', ['nominated_count',1],'%'],
                ['Nominated sheriff', 'nominated_sheriff', '*100/', ['nominated_count',1],'%'],
                ['Was nominated by civilians', 'nominated_by_civil', '*100/', ['nominated_by_count',1],'%'],
                ['Was nominated by mafia', 'nominated_by_mafia', '*100/', ['nominated_by_count',1],'%'],
                ['Was nominated by sheriff', 'nominated_by_sheriff', '*100/', ['nominated_by_count',1],'%']
            ]
        );



//
//			echo '<p><table class="bordered light" width="100%">';
//			echo '<tr class="th-short darker"><td colspan="2">' . get_label('Surviving') . '</td></tr>';
//			foreach ($stats->surviving as $surviving)
//			{
//				switch ($surviving->type)
//				{
//					case KILL_TYPE_SURVIVED:
//						echo '<tr><td class="dark" width="300">'.get_label('Survived').':</td><td>';
//						break;
//					case KILL_TYPE_DAY:
//						echo '<tr><td class="dark" width="300">'.get_label('Killed in day').' ' . $surviving->round . ':</td><td>';
//						break;
//					case KILL_TYPE_NIGHT:
//						echo '<tr><td class="dark" width="300">'.get_label('Killed in night').' ' . $surviving->round . ':</td><td>';
//						break;
//					case KILL_TYPE_WARNINGS:
//						echo '<tr><td class="dark" width="300">'.get_label('Killed by warnings in round').' ' . $surviving->round . ':</td><td>';
//						break;
//					case KILL_TYPE_GIVE_UP:
//						echo '<tr><td class="dark" width="300">'.get_label('Left the game in round').' ' . $surviving->round . ':</td><td>';
//						break;
//					case KILL_TYPE_KICK_OUT:
//						echo '<tr><td class="dark" width="300">'.get_label('Kicked out in round').' ' . $surviving->round . ':</td><td>';
//						break;
//					case KILL_TYPE_TEAM_KICK_OUT:
//						echo '<tr><td class="dark" width="300">'.get_label('Made the opposite team win').' ' . $surviving->round . ':</td><td>';
//						break;
//					default:
//						echo '<tr><td class="dark" width="300">'.get_label('Round').' ' . $surviving->round . ':</td><td>';
//						break;
//				}
//				echo $surviving->count . ' (' . number_format($surviving->count*100.0/$stats->games_played, 2) . '%)</td></tr>';
//			}
//			echo '</table></p>';
//
//			if ($roles == POINTS_DARK || $roles == POINTS_MAFIA || $roles == POINTS_DON)
//			{
//				$mafia_stats = new MafiaStats($this->id, $roles, $condition);
//				echo '<p><table class="bordered light" width="100%">';
//				echo '<tr class="th-short darker"><td colspan="2">' . get_label('Mafia shooting') . '</td></tr>';
//
//				$count = $mafia_stats->shots3_ok + $mafia_stats->shots3_miss;
//				if ($count > 0)
//				{
//					echo '<tr><td class="dark" width="300">'.get_label('3 mafia shooters').':</td><td>' . $count . ' '.get_label('nights').': ';
//					echo $mafia_stats->shots3_ok . ' '.get_label('success;').' ' . $mafia_stats->shots3_miss . ' '.get_label('fail.').' ';
//					echo number_format($mafia_stats->shots3_ok*100/$count, 1) . get_label('% success rate.');
//					if ($mafia_stats->shots3_fail > 0)
//					{
//						echo $mafia_stats->shots3_fail . ' '.get_label('times guilty in misses.');
//					}
//					echo '</td></tr>';
//				}
//
//				$count = $mafia_stats->shots2_ok + $mafia_stats->shots2_miss;
//				if ($count > 0)
//				{
//					echo '<tr><td class="dark" width="300">'.get_label('2 mafia shooters').':</td><td>' . $count . ' '.get_label('nights').': ';
//					echo $mafia_stats->shots2_ok . ' '.get_label('success;').' ' . $mafia_stats->shots2_miss . ' '.get_label('fail.').' ';
//					echo number_format($mafia_stats->shots2_ok*100/$count, 1) . get_label('% success rate.');
//					echo '</td></tr>';
//				}
//
//				$count = $mafia_stats->shots1_ok + $mafia_stats->shots1_miss;
//				if ($count > 0)
//				{
//					echo '<tr><td class="dark" width="300">'.get_label('Single shooter').':</td><td>' . $count . ' '.get_label('nights').': ';
//					echo $mafia_stats->shots1_ok . ' '.get_label('success;').' ' . $mafia_stats->shots1_miss . ' '.get_label('fail.').' ';
//					echo number_format($mafia_stats->shots1_ok*100/$count, 1) . get_label('% success rate.');
//					echo '</td></tr>';
//				}
//				echo '</table></p>';
//			}
//
//			if ($roles == POINTS_SHERIFF)
//			{
//				$sheriff_stats = new SheriffStats($this->id, $condition);
//				$count = $sheriff_stats->civil_found + $sheriff_stats->mafia_found;
//				if ($count > 0)
//				{
//					echo '<p><table class="bordered light" width="100%">';
//					echo '<tr class="th-short darker"><td colspan="2">' . get_label('Sheriff stats') . '</td></tr>';
//					echo '<tr><td class="dark" width="300">'.get_label('Red checks').':</td><td>' . $sheriff_stats->civil_found . ' (' . number_format($sheriff_stats->civil_found*100/$count, 1) . '%) - ' . number_format($sheriff_stats->civil_found/$sheriff_stats->games_played, 2) . ' '.get_label('per game').'</td></tr>';
//					echo '<tr><td class="dark" width="300">'.get_label('Black checks').':</td><td>' . $sheriff_stats->mafia_found . ' (' . number_format($sheriff_stats->mafia_found*100/$count, 1) . '%) - ' . number_format($sheriff_stats->mafia_found/$sheriff_stats->games_played, 2) . ' '.get_label('per game').'</td></tr>';
//					echo '</table></p>';
//				}
//			}
//
//			if ($roles == POINTS_DON)
//			{
//				$don_stats = new DonStats($this->id, $condition);
//				if ($don_stats->games_played > 0)
//				{
//					echo '<p><table class="bordered light" width="100%">';
//					echo '<tr class="th-short darker"><td colspan="2">' . get_label('Don stats') . '</td></tr>';
//					echo '<tr><td class="dark" width="300">'.get_label('Sheriff found').':</td><td>' . $don_stats->sheriff_found . ' ' . $don_stats->games_played . '(' . number_format($don_stats->sheriff_found*100/$don_stats->games_played, 1) . '%)</td></tr>';
//					echo '<tr><td class="dark" width="300">'.get_label('Sheriff arranged').':</td><td>' . $don_stats->sheriff_arranged . ' (' . number_format($don_stats->sheriff_arranged*100/$don_stats->games_played, 1) . '%)</td></tr>';
//					echo '<tr><td class="dark" width="300">'.get_label('Sheriff found first night').':</td><td>' . $stats->sheriff_found_first_night . ' (' . number_format($stats->sheriff_found_first_night*100/$don_stats->games_played, 1) . '%)</td></tr>';
//					echo '<tr><td class="dark" width="300">'.get_label('Sheriff killed first night').':</td><td>' . $stats->sheriff_killed_first_night . ' (' . number_format($stats->sheriff_killed_first_night*100/$don_stats->games_played, 1) . '%)</td></tr>';
//					echo '</table></p>';
//				}
//			}
//
//			echo '<p><table class="bordered light" width="100%">';
//			echo '<tr class="th-short darker"><td colspan="2">' . get_label('Miscellaneous') . '</td></tr>';
//			echo '<tr><td class="dark" width="300">'.get_label('Warnings').':</td><td>' . $stats->warnings . ' (' . number_format($stats->warnings/$stats->games_played, 2) . ' '.get_label('per game').')</td></tr>';
//			echo '<tr><td class="dark" width="300">'.get_label('Arranged by mafia').':</td><td>' . $stats->arranged . ' (' . number_format($stats->arranged/$stats->games_played, 2) . ' '.get_label('per game').')</td></tr>';
//			echo '<tr><td class="dark" width="300">'.get_label('Checked by don').':</td><td>' . $stats->checked_by_don . ' (' . number_format($stats->checked_by_don*100/$stats->games_played, 1) . '%)</td></tr>';
//			echo '<tr><td class="dark" width="300">'.get_label('Checked by sheriff').':</td><td>' . $stats->checked_by_sheriff . ' (' . number_format($stats->checked_by_sheriff*100/$stats->games_played, 1) . '%)</td></tr>';
//
//		echo '</table></p>';

	}
/*
 [

['Rating', get_label('[0] ([1] per game)', number_format($stats->rating, 2), number_format($stats->rating/$stats->games_played, 3))],

];
*/
    private function generateTable($stats, $title, $rows) {
        echo '<p><table class="bordered light" width="100%">';
		echo '<tr class="th-short darker"><td>' . get_label($title) . '</td>';
        foreach ( $stats::$role_condition as $role => $cond) {
            echo '<td>'.$role.'</td>';
        }
        echo '</tr>';
        foreach ($rows as $row) {

            if (is_array($row[0])) {
                echo '<tr><td class="dark">'.get_label($row[0][0], $row[0][1]).':</td>';
            } else {
                echo '<tr><td class="dark">'.get_label($row[0]).':</td>';
            }
            $unit = true;
            foreach ($stats::$role_condition as $role => $cond) {
                if (is_array($row[1])) {
                    $statVal = number_format($stats->{$role}->{$row[1][0]} ?? 0,$row[1][1]);
                } else {
                    $statVal = $stats->{$role}->{$row[1]};
                }

                if (!isset($row[2])) {
                    echo '<td>' . $statVal . '</td>';
                } else {

                    switch ($row[2]) {
                        case '*100/':
                            if ($stats->{$role}->{$row[3][0]} != 0) {
                                echo '<td>' .$statVal.' ('. number_format(($statVal * 100) / $stats->{$role}->{$row[3][0]},$row[3][1]).($unit?$row[4]:''). ')</td>';
                            } else {
                                echo '<td>' .$statVal.' (-)</td>';
                            }

                            break;
                        case '/':
                            if ($stats->{$role}->{$row[3][0]} != 0) {
                                echo '<td>' . $statVal . ' (' . number_format($statVal / $stats->{$role}->{$row[3][0]}, $row[3][1]) . ($unit?$row[4]:'') . ')</td>';
                            }else {
                                echo '<td>' . $statVal . ' (-)</td>';
                            }
                            break;
                        default:

                    }
                    if (strlen($row[4]) > 1 ) {
                        $unit = false;
                    }

                }
            }
            
            echo '</tr>';
        }
        echo '</table></p>';
    }
	
	protected function js()
	{
?>
		function filterChanged()
		{
			// goTo({filter: checkboxFilterFlags(), club: $('#club').val(), roles: $('#roles').val(), year: $('#year').val()});
			goTo( {year: $('#year').val()});
		}
<?php
	}
}

$page = new Page();
$page->run(get_label('General Stats'));

?>