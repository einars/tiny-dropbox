<?
	@extract($_GET);
	
	$filename	= $_FILES['Filedata']['name'];
	$temp_name	= $_FILES['Filedata']['tmp_name'];
	$error		= $_FILES['Filedata']['error'];
	$size		= $_FILES['Filedata']['size'];
	
	/* NOTE: Some server setups might need you to use an absolute path to your "dropbox" folder
	(as opposed to the relative one I've used below).  Check your server configuration to get
	the absolute path to your web directory*/
	if(!$error)
		copy($temp_name, '../dropbox/'.$filename);
?>