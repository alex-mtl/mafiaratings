<?php

class Brand
{
	public $titleprefix;
	public $metatitle;
	public $metaurl;
	public $metasitename;
	public $metafbadmins;
	public $cssfile;
	public $mailtourl;
	
	function __construct()
	{
		$this->titleprefix   = "Mafia Ratings:";
		$this->metatitle     = "Mafia Ratings";
		$this->metaurl       = "https://www.mafiaratings.com";
		$this->metasitename  = "Mafia Ratings";
		$this->metafbadmins  = "1339983926";
		$this->cssfile       = "";
		$this->mailtourl     = "godfather@mafiaratings.com";
	}
}


$brand = new Brand;

?>