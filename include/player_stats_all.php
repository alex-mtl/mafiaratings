<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scoring.php';

define("AVERAGE_PLAYER", -1);

class Surviving
{
	public $round;
	public $type;
	public $count;
	
	function __construct($round, $type, $count)
	{
		$this->round = $round;
		$this->type = $type;
		$this->count = $count;
	}
}

class PlayerStatsAll
{
    public $all;
    public $red;
    public $dark;
    public $civil;
    public $sheriff;
    public $mafia;
    public $don;


    public $surviving;

    public static $role_condition = [
        'all' => '',
        'red' => ' AND p.role < 2',
        'civil' => ' AND p.role = 0',
        'sheriff' => ' AND p.role = 1',
        'dark' => ' AND p.role > 1',
        'mafia' => ' AND p.role = 2',
        'don' => ' AND p.role = 3'
    ];

    private $sql = '
        SELECT count(*) games_played,
            SUM(p.won) as games_won,
            SUM(p.rating_earned) as rating, 
            SUM(p.voted_civil) as voted_civil,
            SUM(p.voted_mafia) as voted_mafia,
            SUM(p.voted_sheriff) as voted_sheriff,
            SUM(p.voted_sheriff+p.voted_mafia+p.voted_civil) as voted_count,
            SUM(p.voted_by_civil) as voted_by_civil,
            SUM(p.voted_by_mafia) as voted_by_mafia,
            SUM(p.voted_by_sheriff) as voted_by_sheriff,
            SUM(voted_by_civil+p.voted_by_mafia+p.voted_by_sheriff) as voted_by_count,
            
            SUM(p.nominated_civil) as nominated_civil,
            SUM(p.nominated_mafia) as nominated_mafia,
            SUM(p.nominated_sheriff) as nominated_sheriff,
            SUM(p.nominated_civil + p.nominated_mafia + p.nominated_sheriff) as nominated_count,
            
            SUM(p.nominated_by_civil) as nominated_by_civil,
            SUM(p.nominated_by_mafia) as nominated_by_mafia,
            SUM(p.nominated_by_sheriff) as nominated_by_sheriff,
            SUM(p.nominated_by_civil + p.nominated_by_mafia + p.nominated_by_sheriff) as nominated_by_count,
            
            SUM(p.warns) as warnings,
            SUM(IF(p.was_arranged >= 0, 1, 0)) as arranged, 
            SUM(IF(p.checked_by_don >= 0, 1, 0)) as checked_by_don,
            SUM(IF(p.checked_by_sheriff >= 0, 1, 0)) as checked_by_sheriff, 
            SUM(IF((p.flags & ' . SCORING_FLAG_BEST_PLAYER . ') <> 0, 1, 0)) as best_player, 
            SUM(IF((p.flags & ' . SCORING_FLAG_BEST_MOVE . ') <> 0, 1, 0)) as best_move, 
            SUM(IF((p.flags & ' . SCORING_FLAG_WORST_MOVE . ') <> 0, 1, 0)) as worst_move,
            SUM(IF((p.flags & ' . SCORING_FLAG_FIRST_LEGACY_3 . ') <> 0, 1, 0)) as guess3maf,
            SUM(IF((p.flags & ' . SCORING_FLAG_FIRST_LEGACY_2 . ') <> 0, 1, 0)) as guess2maf,
            SUM(IF((p.flags & ' . SCORING_FLAG_FIRST_LEGACY_1 . ') <> 0, 1, 0)) as guess1maf,
            SUM(IF((p.flags & ' . SCORING_FLAG_FIRST_LEGACY_3 . ') <> 0, 3, 0)
                +IF((p.flags & ' . SCORING_FLAG_FIRST_LEGACY_2 . ') <> 0, 2, 0)
                +IF((p.flags & ' . SCORING_FLAG_FIRST_LEGACY_1 . ') <> 0, 1, 0)) as mafs_in_legacy,
            SUM(IF((p.flags & ' . SCORING_FLAG_KILLED_FIRST_NIGHT . ') <> 0, 1, 0)) as killed_first_night,
            SUM(IF((p.flags & ' . SCORING_FLAG_KILLED_FIRST_NIGHT . ') <> 0, 1, 0))*3 as killed_first_night3,
            SUM(IF((p.flags & ' . SCORING_FLAG_SHERIFF_FOUND_FIRST_NIGHT . ') <> 0, 1, 0)) as sheriff_found_first_night,
            SUM(IF((p.flags & ' . SCORING_FLAG_SHERIFF_KILLED_FIRST_NIGHT . ') <> 0, 1, 0)) as sheriff_killed_first_night,
            SUM(p.extra_points) as bonus
            FROM players p JOIN games g ON g.id = p.game_id 
            WHERE 1
    ';

	// if $user_id <= 0: gives stats of an average player
	function __construct($user_id, $condition = NULL)
	{
		if ($condition == NULL) {
			$condition = new SQL();
		} else {
			$condition = clone $condition;
		}

		$count = 1; 

		$condition->add(' AND p.user_id = ?', $user_id);


        foreach ( $this::$role_condition as $role => $cond) {
            $c = clone $condition;
            if (!empty($cond)) {
                $c->add($cond);
            }
            $query = new DbQuery(
                $this->sql,
                $c);
//            echo $query->get_parsed_sql();
            $row = $query->record(get_label('player'), MYSQLI_ASSOC);
            $this->{$role} = $row;
        }

		$query = new DbQuery('SELECT p.kill_round, p.kill_type, count(*) FROM players p JOIN games g ON g.id = p.game_id WHERE TRUE', $condition);
		$query->add(' GROUP BY p.kill_type, p.kill_round ORDER BY p.kill_round, p.kill_type');
		while ($row = $query->next())
		{
			$this->surviving[] = new Surviving($row[0], $row[1], $row[2] / $count);
		}
	}
}
