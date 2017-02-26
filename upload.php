<?php

if (isset($_POST['PHPSESSID']))
{
	session_id($_POST['PHPSESSID']);
}

require_once 'include/session.php';
require_once 'include/image.php';
require_once 'include/photo_album.php';

try
{
	initiate_session();
	if ($_profile == NULL)
	{
		throw new FatalExc(get_label('No permissions'));
	}
		
	if (!isset($_REQUEST['id']))
	{
		throw new FatalExc(get_label('Unknown [0]', get_label('object')));
	}
	$id = $_REQUEST['id'];
	
	if (!isset($_REQUEST['code']))
	{
		throw new FatalExc(get_label('Unknown [0]', get_label('object type')));
	}
	$code = $_REQUEST['code'];
	
	Db::begin();
	switch ($code)
	{
	case ADDR_PIC_CODE:
		list ($club_id, $flags) = Db::record(get_label('address'), 'SELECT club_id, flags FROM addresses WHERE id = ?', $id);
		if (!$_profile->is_manager($club_id))
		{
			throw new FatalExc(get_label('No permissions'));
		}
		
		upload_pic('Filedata', ADDRESS_PICS_DIR, $id);
		$icon_version = (($flags & ADDR_ICON_MASK) >> ADDR_ICON_MASK_OFFSET) + 1;
		if ($icon_version > ADDR_ICON_MAX_VERSION)
		{
			$icon_version = 1;
		}
		$flags = ($flags & ~ADDR_ICON_MASK) + ($icon_version << ADDR_ICON_MASK_OFFSET);
		$flags &= ~ADDR_FLAG_GENERATED;
		
		Db::exec(get_label('address'), 'UPDATE addresses SET flags = ? WHERE id = ?', $flags, $id);
		if (Db::affected_rows() > 0)
		{
			list($club_id, $flags) = Db::record(get_label('address'), 'SELECT club_id, flags FROM addresses WHERE id = ?', $id);
			db_log('address', 'Photo uploaded', 'flags=' . $flags, $id, $club_id);
		}
		break;
		
	case USER_PIC_CODE:
		list ($club_id, $flags) = Db::record(get_label('user'), 'SELECT club_id, flags FROM users WHERE id = ?', $id);
		if ($_profile->user_id != $id && !$_profile->is_manager($club_id))
		{
			throw new FatalExc(get_label('No permissions'));
		}
	
		upload_pic('Filedata', USER_PICS_DIR, $id);
		$icon_version = (($flags & U_ICON_MASK) >> U_ICON_MASK_OFFSET) + 1;
		if ($icon_version > U_ICON_MAX_VERSION)
		{
			$icon_version = 1;
		}
		$flags = ($flags & ~U_ICON_MASK) + ($icon_version << U_ICON_MASK_OFFSET);
		Db::exec(get_label('user'), 'UPDATE users SET flags = ? WHERE id = ?', $flags, $id);
		if ($_profile->user_id == $id)
		{
			$_profile->user_flags = $flags;
		}
		if (Db::affected_rows() > 0)
		{
			db_log('user', 'Avatar uploaded', 'flags=' . $flags, $id);
		}
		break;
	
	case CLUB_PIC_CODE:
		if (!$_profile->is_manager($id))
		{
			throw new FatalExc(get_label('No permissions'));
		}
		
		upload_pic('Filedata', CLUB_PICS_DIR, $id);
		
		list ($flags) = Db::record(get_label('club'), 'SELECT flags FROM clubs WHERE id = ?', $id);
		$icon_version = (($flags & CLUB_ICON_MASK) >> CLUB_ICON_MASK_OFFSET) + 1;
		if ($icon_version > CLUB_ICON_MAX_VERSION)
		{
			$icon_version = 1;
		}
		$flags = ($flags & ~CLUB_ICON_MASK) + ($icon_version << CLUB_ICON_MASK_OFFSET);
		
		Db::exec(get_label('club'), 'UPDATE clubs SET flags = ? WHERE id = ?', $flags, $id);
		if (Db::affected_rows() > 0)
		{
			db_log('club', 'Logo uploaded', 'flags=' . $flags, $id, $id);
		}
		break;
	
	case ALBUM_PIC_CODE:
		list ($owner_id, $club_id, $flags) = Db::record(get_label('photo album'),'SELECT user_id, club_id, flags FROM photo_albums WHERE id = ?', $id);
		if ($owner_id != $_profile->user_id && !$_profile->is_manager($club_id))
		{
			throw new FatalExc(get_label('No permissions'));
		}
	
		upload_pic('Filedata', ALBUM_PICS_DIR, $id);
		
		$icon_version = (($flags & ALBUM_ICON_MASK) >> ALBUM_ICON_MASK_OFFSET) + 1;
		if ($icon_version > ALBUM_ICON_MAX_VERSION)
		{
			$icon_version = 1;
		}
		$flags = ($flags & ~ALBUM_ICON_MASK) + ($icon_version << ALBUM_ICON_MASK_OFFSET);
		
		Db::exec(get_label('photo album'), 'UPDATE photo_albums SET flags = ? WHERE id = ?', $flags, $id);
		if (Db::affected_rows() > 0)
		{
			db_log('album', 'Icon uploaded', 'flags=' . $flags, $id, $club_id);
		}
		break;
		
	case EVENT_PIC_CODE:
		list ($club_id, $flags) = Db::record(get_label('event'), 'SELECT club_id, flags FROM events WHERE id = ?', $id);
		if (!$_profile->is_manager($club_id))
		{
			throw new FatalExc(get_label('No permissions'));
		}
		
		upload_pic('Filedata', EVENT_PICS_DIR, $id);
		
		$icon_version = (($flags & EVENT_ICON_MASK) >> EVENT_ICON_MASK_OFFSET) + 1;
		if ($icon_version > EVENT_ICON_MAX_VERSION)
		{
			$icon_version = 1;
		}
		$flags = ($flags & ~EVENT_ICON_MASK) + ($icon_version << EVENT_ICON_MASK_OFFSET);
		
		Db::exec(get_label('event'), 'UPDATE events SET flags = ? WHERE id = ?', $flags, $id);
		if (Db::affected_rows() > 0)
		{
			db_log('event', 'Logo uploaded', 'flags=' . $flags, $id, $club_id);
		}
		break;
		
	case PHOTO_CODE:
		$album = new PhotoAlbum($id);
		if (!$album->can_add())
		{
			throw new FatalExc(get_label('No permissions'));
		}
		
		Db::exec(
			get_label('photo'), 
			'INSERT INTO photos (user_id, viewers, album_id) VALUES (?, ?, ?)', 
			$_profile->user_id, $album->viewers, $album->id);

		list ($id) = Db::record(get_label('photo'), 'SELECT LAST_INSERT_ID()');
		upload_photo('Filedata', $id);
		break;
		
	default:
		throw new FatalExc(get_label('Unknown [0]', get_label('object type')));
	}
	Db::commit();
	echo 'ok';
}
catch (Exception $e)
{
	Db::rollback();
	Exc::log($e, true);
	echo $e->getMessage();
}

?>