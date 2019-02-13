<?php

require_once 'include/page_base.php';

class Page extends PageBase
{
	protected function show_body()
	{
		global $_lang_code;
		
?>
		<script type="text/javascript" src="js/seating.js"></script>
		<script type="text/javascript" src="js/seating-<?php echo $_lang_code; ?>.js"></script>
		<script type="text/javascript" src="js/seating-ui.js"></script>
		<div id="seating"></div>
<?php
	}
	
	protected function js_on_load()
	{
		parent::js_on_load();
?>
		seatingUi.show();
<?php
	}
		
	protected function js()
	{
		parent::js();
	}
}

$page = new Page();
$page->run(get_label('Seating'));

?>