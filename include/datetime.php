<?php
require_once __DIR__ . '/session.php';

define('DEF_DATETIME_FORMAT', 'Y-m-d\TH:i');
define('DEF_DATETIME_FORMAT_NO_TIME', 'Y-m-d');

function timestamp_to_string($timestamp, $timezone, $include_time = true)
{
	date_default_timezone_set($timezone);
	if ($include_time)
	{
		return date(DEF_DATETIME_FORMAT, $timestamp);
	}
	return date(DEF_DATETIME_FORMAT_NO_TIME, $timestamp);
}

function get_datetime($str, $timezone = NULL)
{
	if ($timezone == NULL)
	{
		$timezone = new DateTimeZone(get_timezone());
	}
	else if (is_string($timezone))
	{
		$timezone = new DateTimeZone($timezone);
	}
	if (is_numeric($str))
	{
		date_default_timezone_set($timezone->getName());
		$str = timestamp_to_string((int)$str, $timezone->getName());
	}
	return new DateTime($str, $timezone);
}

function datetime_to_string($datetime, $include_time = true)
{
	date_default_timezone_set($datetime->getTimezone()->getName());
	if ($include_time)
	{
		return $datetime->format(DEF_DATETIME_FORMAT);
	}
	return $datetime->format(DEF_DATETIME_FORMAT_NO_TIME);
}

function show_year_select($year, $start_time, $end_time, $on_change, $timezone = NULL)
{
	echo '<select name="year" id="year" onChange="' . $on_change . '">';
	show_option(0, $year, get_label('All time'));
	if ($start_time > 0)
	{
		$timezone = ($timezone == NULL ? get_timezone() : $timezone);
		date_default_timezone_set($_profile->timezone);
		$min_year = date('Y', $start_time);
		$max_year = date('Y', $end_time);
		while ($min_year <= $max_year)
		{
			show_option($min_year, $year, $min_year);
			++$min_year;
		}
	}
	echo '</select>';
}

function get_year_condition($year, $timezone = NULL)
{
	$timezone = ($timezone == NULL ? get_timezone() : $timezone);
	$year_condition = new SQL();
	if ($year > 0)
	{
		$timezone = ($timezone == NULL ? get_timezone() : $timezone);
		$date_time = new DateTime($year . '-01-01', new DateTimeZone($timezone));
		$year_start = $date_time->getTimestamp();
		$date_time->setDate($year + 1, 1, 1);
		$year_end = $date_time->getTimestamp();
		$year_condition->add(' AND p.game_end_time >= ? AND p.game_end_time < ?', $year_start, $year_end);
	}
	return $year_condition;
}

?>