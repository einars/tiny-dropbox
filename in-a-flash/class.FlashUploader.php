<?php
	/*********************************************************************************************
	FUNCTION IAF_display_js
	Parameters: None
	Description: Displays the javascript which controls the resizing of the uploader
	Alias: IFU_display_js
	*********************************************************************************************/
	function IAF_display_js() {
		ob_start(); ?>
			<script type="text/javascript">
				function flashResize(ele, height, width) {
					var uploader = document.getElementById(ele);
					
					if(height != 0)
						uploader.style.height = height+"px";
					
					if(width != 0)
						uploader.style.width = width+"px";
				}
				
				function canResizeFlash(){
					var ua = navigator.userAgent.toLowerCase();
					var opera = ua.indexOf("opera");
					
					if( document.getElementById ) {
						if(opera == -1) return true;
						else if(parseInt(ua.substr(opera+6, 1)) >= 7) return true;
					}
					
					return false;
				}
				
				e = canResizeFlash();
			</script>
			<?php
			$js = ob_get_contents(); ob_end_clean();
			
			echo $js;
	}
	function IFU_display_js() { IAF_display_js(); }
	
	/*********************************************************************************************
	CLASS FlashUploader
	Parameters: None
	Description: Class that controls and displays the In-a-Flash Uploader
	*********************************************************************************************/
	class FlashUploader {
		var $element_id;
		var $swf_name;
		var $target;
		
		var $pass_vars;
		var $properties;

		
		/*********************************************************************************************
		FUNCTION FlashUploader (Constructor)
		Parameters:
			$element_id	- the name of the div containing the uploader
			$swf_name	- the name of the swf file (usually uploader/uploader.swf)
			$target		- the path to the PHP file that handles the upload (usually uploader/uploader.php)
		Description: Creates the FlashUploader object
		*********************************************************************************************/
		function FlashUploader($element_id, $swf_path, $target) {
			$this->element_id	= $element_id;
			$this->swf_name		= $swf_path;
			$this->target		= $target;
			
			$this->pass_vars	= array();
			$this->properties	= array(
									'bg_color'		=> '0xFFFFFF',
									'set_width'		=> 415,
									'set_height'	=> 52
									);
									
			//valid properties: max_file_size, max_files, callback, style, valid_extensions, click_text, uploading_text, complete_text, pending_text, max_text, auto_clear, allow_clear, allow_cancel, set_width, set_height, bg_color, bar_bg_color, divider_color, button_title_color, button_color, button_shadow, txt_title_color, txt_filename_color, txt_percent_color, txt_progress_color
		}
		
		
		/*********************************************************************************************
		FUNCTION set
		Parameters:
			$property	- the name of the property
			$value		- the desired value of the property
		Description: Creates the FlashUploader object
		*********************************************************************************************/
		function set($property, $value) {
			$this->properties[$property] = $value;
			
			if($property == 'valid_extensions')
				$this->properties['extensions_mod'] = implode(';', explode(',', $this->properties['valid_extensions']));
		}

		
		/*********************************************************************************************
		FUNCTION pass_var
		Parameters:
			$name	- the name of the variable to pass
			$value	- the desired value of the variable
		Description: Creates a variable to pass to the PHP upload file ($target) via GET
		*********************************************************************************************/
		function pass_var($name, $value) {
			$this->pass_vars[$name] = $value;
		}
		
		
		/*********************************************************************************************
		FUNCTION property_str
		Parameters: None
		Description: Generates the string of property values to be passed to the uploader
		*********************************************************************************************/
		function property_str() {
			$string = '&amp;';
			
			foreach($this->properties as $i=>$p)
				$string .= $i.'='.$p.'&amp;';
			
			return $string;
		}
		
		
		/*********************************************************************************************
		FUNCTION var_string
		Parameters: None
		Description: Generates the string of variables to be passed to the PHP upload file ($target) via GET
		*********************************************************************************************/
		function var_string() {
			$string = 'vars=';
			
			foreach($this->pass_vars as $index=>$pv)
				$string .= $index.'*!#'.$pv.'#!*';
			
			return $string;
		}
		
		
		/*********************************************************************************************
		FUNCTION display
		Parameters: None
		Description: Displays the FlashUploader
		*********************************************************************************************/
		function display() {
			ob_start(); ?>
			<div id="<?php echo $this->element_id?>" style="width: <?php echo $this->properties['set_width']?>px; height: <?php echo $this->properties['set_height']?>px;">
				<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" data="<?php echo $this->swf_name?>.swf" width="100%" height="100%" type="application/x-shockwave-flash"><param name="allowScriptAccess" value="sameDomain" />
					<param name="movie" value="<?php echo $this->swf_name?>.swf" />
					<param name="quality" value="high" />
					<param name="bgcolor" value="#<?php echo substr($this->properties['bg_color'], 2)?>" />
					<param name="FlashVars" value="allowResize='+e+'&amp;element_id=<?php echo $this->element_id; ?>&amp;target=<?php echo $this->target; ?><?php echo $this->property_str();?><?php echo $this->var_string();?>" />
					<embed src="<?php echo $this->swf_name?>.swf" FlashVars="allowResize='+e+'&amp;element_id=<?php echo $this->element_id; ?>&amp;target=<?php echo $this->target; ?><?php echo $this->property_str();?><?php echo $this->var_string();?>" quality="high" bgcolor="#<?php echo substr($this->properties['bg_color'], 2)?>" width="100%" height="100%" name="<? echo $this->element_id?>" align="top" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
				</object>
			</div>
<?php
			
			$display = ob_get_contents(); ob_end_clean();
			echo $display;
		}
	}
?>