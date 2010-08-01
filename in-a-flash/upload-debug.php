<?
	/* NOTE: This file will print all passed variables (sent using pass_var) and all uploaded file
	information to a file called output.txt (you can change this below) in order to debug the utility.
	If nothing is printed to output.txt it is either a permission problem (make the folder this file
	is in writeable) or your path to the upload.php file (the 3rd paramater of the $uploader->create()
	function) is wrong. Make sure you use a full web path if you are having problems (such as
	http://www.inaflashuploader.com/uploader/upload.php)*/
	
	@extract($_GET);
	
	ob_start();
	
	$filename	= $_FILES['Filedata']['name'];
	$temp_name	= $_FILES['Filedata']['tmp_name'];
	$error		= $_FILES['Filedata']['error'];
	$size		= $_FILES['Filedata']['size'];
	
	print_r($_GET);
	echo "\n\n";
	print_r($_FILES);
	echo "\n\n";
	
	/* NOTE: Some server setups might need you to use an absolute path to your "dropbox" folder
	(as opposed to the relative one I've used below).  Check your server configuration to get
	the absolute path to your web directory*/
	if(!$error)
		copy($temp_name, '../dropbox/'.$filename);
		
	$output = ob_get_contents(); ob_end_clean();
	
	//write all output and variables to a file
	$f = fopen('output.txt', 'w+');
	fwrite($f, $output);
	fclose($f);
?>