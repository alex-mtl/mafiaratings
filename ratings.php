<?php

require_once 'include/general_page_base.php';
require_once 'include/pages.php';
require_once 'include/image.php';
require_once 'include/club.php';
require_once 'include/user.php';

define("PAGE_SIZE",15);
define('ROLES_COUNT', 7);
	
class Page extends GeneralPageBase
{
	private $my_id;
	private $role;
	private $type_id;

	protected function prepare()
	{
		global $_profile;
		
		parent::prepare();
		
		$this->my_id = -1;
		if ($_profile != NULL)
		{
			$this->my_id = $_profile->user_id;
		}

		$this->role = RATING_ALL;
		if (isset($_REQUEST['role']))
		{
			$this->role = $_REQUEST['role'];
		}
		
		if (isset($_REQUEST['type']))
		{
			$this->type_id = $_REQUEST['type'];
		}
		else
		{
			list($this->type_id) = Db::record(get_label('rating'), 'SELECT id FROM rating_types ORDER BY def DESC, id LIMIT 1');
		}
	}
	
	protected function show_body()
	{
		global $_page, $_profile;
		
		$condition = new SQL(
			' FROM ratings r JOIN users u ON u.id = r.user_id LEFT OUTER JOIN clubs c ON u.club_id = c.id WHERE r.role = ? AND r.type_id = ?',
			$this->role, $this->type_id);
		$ccc_id = $this->ccc_filter->get_id();
		switch ($this->ccc_filter->get_type())
		{
		case CCCF_CLUB:
/*			if ($ccc_id > 0)
			{
				$condition->add(' AND u.id IN (SELECT user_id FROM user_clubs WHERE (flags & ' . UC_FLAG_BANNED . ') = 0 AND club_id = ?)', $ccc_id);
			}
			else if ($ccc_id == 0 && $_profile != NULL)
			{
				$condition->add(' AND u.id IN (SELECT user_id FROM user_clubs WHERE (flags & ' . UC_FLAG_BANNED . ') = 0 AND club_id IN (SELECT club_id FROM user_clubs WHERE (flags & ' . UC_FLAG_BANNED . ') = 0 AND user_id = ?))', $_profile->user_id);
			}*/
			if ($ccc_id > 0)
			{
				$condition->add(' AND u.club_id = ', $ccc_id);
			}
			else if ($ccc_id == 0 && $_profile != NULL)
			{
				$condition->add(' AND u.club_id IN (SELECT club_id FROM user_clubs WHERE (flags & ' . UC_FLAG_BANNED . ') = 0 AND user_id = ?)', $_profile->user_id);
			}
			break;
		case CCCF_CITY:
			$condition->add(' AND u.city_id IN (SELECT id FROM cities WHERE id = ? OR near_id = ?)', $ccc_id, $ccc_id);
			break;
		case CCCF_COUNTRY:
			$condition->add(' AND u.city_id IN (SELECT id FROM cities WHERE country_id = ?)', $ccc_id);
			break;
		}

		list ($count) = Db::record(get_label('rating'), 'SELECT count(*)', $condition);
		$query = new DbQuery('SELECT u.id, u.name, r.rating, r.games, r.games_won, u.flags, c.id, c.flags', $condition);
		$query->add(' ORDER BY r.rating DESC, r.games, r.games_won DESC, r.user_id LIMIT ' . ($_page * PAGE_SIZE) . ',' . PAGE_SIZE);
		
		show_pages_navigation(PAGE_SIZE, $count);
		
		echo '<table class="bordered light" width="100%">';
		echo '<tr class="th-long darker"><td width="40">&nbsp;</td>';
		echo '<td colspan="3">'.get_label('Player').'</td>';
		echo '<td width="80" align="center">'.get_label('Rating').'</td>';
		echo '<td width="80" align="center">'.get_label('Games played').'</td>';
		echo '<td width="80" align="center">'.get_label('Games won').'</td>';
		echo '<td width="80" align="center">'.get_label('Winning %').'</td>';
		echo '<td width="80" align="center">'.get_label('Rating per game').'</td>';
		echo '</tr>';

		$number = $_page * PAGE_SIZE;
		while ($row = $query->next())
		{
			++$number;
			list ($id, $name, $rating, $games_played, $games_won, $flags, $club_id, $club_flags) = $row;

			if ($id == $this->my_id)
			{
				echo '<tr class="light">';
			}
			else
			{
				echo '<tr>';
			}

			echo '<td align="center" class="dark">' . $number . '</td>';
			echo '<td width="60" align="center"><a href="user_info.php?id=' . $id . '&bck=1">';
			show_user_pic($id, $flags, ICONS_DIR, 50, 50);
			echo '</a></td>';
			echo '<td><a href="user_info.php?id=' . $id . '&bck=1">' . cut_long_name($name, 45) . '</a></td>';
			echo '<td width="50" align="center">';
			show_club_pic($club_id, $club_flags, ICONS_DIR, 40, 40);
			echo '</td>';
			echo '<td align="center" class="dark">' . $rating . '</td>';
			echo '<td align="center">' . $games_played . '</td>';
			echo '<td align="center">' . $games_won . '</td>';
			if ($games_played != 0)
			{
				echo '<td align="center">' . number_format(($games_won*100.0)/$games_played, 1) . '%</td>';
				echo '<td align="center">' . number_format($rating/$games_played, 2) . '</td>';
			}
			else
			{
				echo '<td align="center">&nbsp;</td><td width="60">&nbsp;</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
	}
	
	protected function show_filter_fields()
	{
		global $_lang_code;

		echo '<table class="transp" width="100%">';
		echo '<tr><td align="right"><select id="type" onChange="filter()">';
		$query = new DbQuery('SELECT id, name_' . $_lang_code . ' FROM rating_types ORDER BY id');
		while ($row = $query->next())
		{
			list ($tid, $tname) = $row;
			show_option($tid, $this->type_id, $tname);
		}
		echo '</select> ';
		
		echo '<select id="role" onChange = "filter()">';
		show_option(0, $this->role, get_label('All roles'));
		show_option(1, $this->role, get_label('Red players'));
		show_option(2, $this->role, get_label('Dark players'));
		show_option(3, $this->role, get_label('Civilians'));
		show_option(4, $this->role, get_label('Sheriffs'));
		show_option(5, $this->role, get_label('Mafiosy'));
		show_option(6, $this->role, get_label('Dons'));
		echo '</select>';
		echo '</td></tr></table>';
	}
	
	protected function get_filter_js()
	{
		return '+ "&type=" + $("#type option:selected").val() + "&role=" + $("#role option:selected").val()';
	}
}

$page = new Page();
$page->set_ccc(CCCS_ALL);
$page->run(get_label('Ratings'), PERM_ALL);

?>