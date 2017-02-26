<?php

require_once 'include/session.php';
require_once 'include/languages.php';
require_once 'include/city.php';
require_once 'include/country.php';
require_once 'include/user.php';
require_once 'include/image.php';

initiate_session();

try
{
	dialog_title(get_label('New user account'));
	if ($_profile == NULL)
	{
		throw new FatalExc('No permissions');
	}
	
	echo '<table class="bordered" width="100%">';
	echo '<tr class="light"><td class="light" colspan="3">';
	echo get_label('Your account is activated. Please answer a few questions about yourself');
	echo ':</td></tr>';
		
	echo '<tr><td width="120" valign="top">' . get_label('Gender') . ':</td><td>';
	echo '<input type="radio" name="form-is_male" id="form-male" value="1" onClick="maleClick(true)"';
	if ($_profile->user_flags & U_FLAG_MALE)
	{
		echo ' checked';
	}
	echo '/>'.get_label('male').'<br>';
		
	echo '<input type="radio" name="form-is_male" id="form-female" value="0" onClick="maleClick(false)"';
	if (($_profile->user_flags & U_FLAG_MALE) == 0)
	{
		echo ' checked';
	}
	echo '/>'.get_label('female');
	
	echo '</td>';
	
	echo '<td width="' . ICON_WIDTH . '" align="center" valign="top" rowspan="8">';
	show_user_pic($_profile->user_id, $_profile->user_flags, ICONS_DIR);
	echo '<p>';
	show_upload_button();
	echo '</p></td></tr>';

	$club_id = $_profile->user_club_id;
	if ($club_id == NULL)
	{
		$club_id = 0;
	}
	echo '<tr><td valign="top">'.get_label('Club').':</td><td>' . get_label('Please enter your favourite club. The club you want to represent on championships.') . '<br>';
	echo '<select id="form-club">';
	show_option(0, $club_id, '');
	$query = new DbQuery('SELECT id, name FROM clubs ORDER BY name');
	while ($row = $query->next())
	{
		list ($cid, $cname) = $row;
		show_option($cid, $club_id, $cname);
	}
	echo '</td></tr>';
	
	echo '<tr><td valign="top">'.get_label('Languages').':</td><td>';
	langs_checkboxes($_profile->user_langs);
	echo '</td></tr>';
	
	echo '<tr><td>' . get_label('Country') . ':</td><td>';
	show_country_input('form-country', COUNTRY_DETECT, 'form-city');
	echo '</td></tr>';
	
	echo '<tr><td>' . get_label('City') . ':</td><td>';
	show_city_input('form-city', CITY_DETECT, 'form-country');
	echo '</td></tr>';
	
	echo '<tr><td>' . get_label('Phone') . ':</td><td>';
	echo get_label('Phone is optional. You can give us your phone if you do not mind us calling you.') . '<br><input id="form-phone" value="' . $_profile->user_phone . '"></td></tr>';
		
	echo '<tr><td>'.get_label('Password').':</td><td><input type="password" id="form-pwd"></td></tr>';
	echo '<tr><td>'.get_label('Confirm password').':</td><td><input type="password" id="form-confirm"></td></tr>';
		
	echo '</table>';
		
	show_upload_script(USER_PIC_CODE, $_profile->user_id);
	
?>	
	<script>
	function maleClick(male)
	{
		var id = "<?php echo $_profile->user_id; ?>";
		var mIcon = "images/icons/male.png";
		var fIcon = "images/icons/female.png";
		var src = $("#" + id).attr("src");
		if (male)
		{
			if (src == fIcon)
			{
				$("img").each(function() { if ($(this).attr('code') == id) $(this).attr("src", mIcon); });
			}
		}
		else if (src == mIcon)
		{
			$("img").each(function() { if ($(this).attr('code') == id) $(this).attr("src", fIcon); });
		}
	}
	
	function commit(onSuccess)
	{
		var languages = mr.getLangs();
		var isMale = $("#form-male").attr("checked") ? 1 : 0;
		var club = parseInt($("#form-club").val());
		var params =
		{
			pwd: $("#form-pwd").val(),
			confirm: $("#form-confirm").val(),
			country: $("#form-country").val(),
			city: $("#form-city").val(),
			phone: $("#form-phone").val(),
			langs: languages,
			male: isMale,
			init: ""
		};
		
		if (club > 0)
		{
			params["club"] = club;
		}
		
		json.post("profile_ops.php", params, onSuccess);
	}
	</script>
<?php
	echo '<ok>';
}
catch (Exception $e)
{
	Exc::log($e);
	echo $e->getMessage();
}

?>