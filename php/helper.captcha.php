<?php
	
	/*
	 * Captcha helper
	 * 
	 */
	
	session_start();
	
    function helper_captcha() {
		
		$default_options = array(
			
			'type'				=> 'num',
			'name' 				=> 'captcha',
			'num' 				=> 6,
			'fontsize' 			=> 15,
			'imagewidth' 		=> 70,
			'imageheight' 		=> 40,
			'fontangle' 		=> 0,
			'font' 				=> '../fonts/kelson/Kelson Sans Regular.otf',
			'backgroundcolor' 	=> 'ffffff',
			'textcolor' 		=> '000000'
			
		);
		
		$options = $default_options;
		
		switch ($options['type']) {
			case 'string':
				
				$string = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'both':
				
				$string = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			default:
				
				$string = '0123456789';
		}
		
		$text = '';
		
		for ($i = 0; $i < $options['num']; $i++) {
			$text .= $string[rand(0, strlen($string)-1)];
		}
		
		### Save random number to session
		$_SESSION[$options['name']] = md5($text);
		
		### Apply image background
		//$im = imagecreatefrompng(Config()->ROOT_PATH . 'web/css/captcha/captcha.png');
		//imagecopy($final_img, $im, $dst_x, $dst_y, 0, 0, 75, 75);
		
		### Convert HTML backgound color to RGB
		if( preg_match( "/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i", $options['backgroundcolor'], $bgrgb ) )
		{$bgred = hexdec( $bgrgb[1] );   $bggreen = hexdec( $bgrgb[2] );   $bgblue = hexdec( $bgrgb[3] );}

		### Convert HTML text color to RGB
		if( preg_match( "/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i", $options['textcolor'], $textrgb ) )
		{$textred = hexdec( $textrgb[1] );   $textgreen = hexdec( $textrgb[2] );   $textblue = hexdec( $textrgb[3] );}

		### Create image
		$im = imagecreate( $options['imagewidth'], $options['imageheight'] );

		### Declare image's background color
		$bgcolor = imagecolorallocatealpha($im,255,255,255,127);

		### Declare image's text color
		$fontcolor = imagecolorallocate($im, $textred,$textgreen,$textblue);
		
		imagealphablending($im,true);

		### Get exact dimensions of text string
		$box = @imageTTFBbox($options['fontsize'],$options['fontangle'],$options['font'],$text);

		### Get width of text from dimensions
		$textwidth = abs($box[4] - $box[0]);

		### Get height of text from dimensions
		$textheight = abs($box[5] - $box[1]);

		### Get x-coordinate of centered text horizontally using length of the image and length of the text
		$xcord = ($options['imagewidth']/2)-($textwidth/2)-2;

		### Get y-coordinate of centered text vertically using height of the image and height of the text
		$ycord = ($options['imageheight']/2)+($textheight/2);

		### Declare completed image with colors, font, text, and text location
		imagettftext ( $im, $options['fontsize'], $options['fontangle'], $xcord, $ycord, $fontcolor, $options['font'], $text );

		### Display completed image as PNG
		imagealphablending($im,false);
		imagesavealpha($im,true);
		
		header('Content-Type: image/png');
		imagepng($im);
		
		### Close the image
		imagedestroy($im);
		
    }
	
	helper_captcha();
	
?>