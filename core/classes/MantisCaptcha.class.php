<?php
/**
 * MantisBT - A PHP based bugtracking system
 *
 * MantisBT is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MantisBT is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.
 */
 
/** The class below was derived from
 * http://www.phpclasses.org/browse/package/1163.html
 *
 * *** 3.0 Author
 * Pascal Rehfeldt
 * Pascal@Pascal-Rehfeldt.com
 *
 * http://www.phpclasses.org/browse.html/author/102754.html
 *
 *
 * *** 3.1 License
 * GNU General Public License (Version 2, June 1991)
 *
 * This program is free software; you can redistribute
 * it and/or modify it under the terms of the GNU
 * General Public License as published by the Free
 * Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 */
class MantisCaptcha
{
	/**
	 * TTF Font folder
	 */
	var $TTF_folder;

	/**
	 * Array of TTF Fonts to use in captcha
	 */
	var $TTF_RANGE  = array('ARIAL.TTF');

	/**
	 * Character count
	 */
	var $chars		= 5;

	/**
	 * Min Size
	 */
	var $minsize	= 15;

	/**
	 * Max Size
	 */
	var $maxsize	= 15;

	/**
	 * Max rotation
	 */
	var $maxrotation = 30;

	/**
	 * Noise
	 */
	var $noise		= FALSE;

	/**
	 * Web safe colors
	 */
	var $websafecolors = TRUE;

	/**
	 * Width
	 */
	var $lx;

	/** 
	 * Height
	 */
	var $ly;

	/**
	 * JPEG Image Quality
	 */
	var $jpegquality = 80;

	/**
	 * Noise factor - this will multiplyed with number of chars
	 */
	var $noisefactor = 9;
	
	/**
	 * number of background-noise-characters
	 */
	var $nb_noise;

	/**
	 * holds the current selected TrueTypeFont
	 */
	var $TTF_file;

	/**
	 * RGB Value
	 */
	var $r;

	/**
	 * RGB Value
	 */
	var $g;

	/**
	 * RGB Value
	 */
	var $b;

	/**
	 * Constructor
	 */
	function MantisCaptcha() {
		if( !extension_loaded('gd') ) {
			throw new MantisBT\Exception\Missing_GD_Extension();
		}
	}

	/**
	 * Init function
	 */
	function init() {
		// check vars for maxtry, secretposition and min-max-size
		if($this->minsize > $this->maxsize)
		{
			$temp = $this->minsize;
			$this->minsize = $this->maxsize;
			$this->maxsize = $temp;
		}

		// check TrueTypeFonts
		if(is_array($this->TTF_RANGE))
		{
			//Check given TrueType-Array! (".count($this->TTF_RANGE).")";
			$temp = array();
			foreach($this->TTF_RANGE as $k=>$v)
			{
				if(is_readable($this->TTF_folder.$v)) $temp[] = $v;
			}
			$this->TTF_RANGE = $temp;
			//Valid TrueType-files: (".count($this->TTF_RANGE).")";
			//if(count($this->TTF_RANGE) < 1) die('No Truetypefont available for the CaptchaClass.');
		}
		else
		{
			//Captcha-Debug: Check given TrueType-File! (".$this->TTF_RANGE.")";
			if(!is_readable($this->TTF_folder.$this->TTF_RANGE)) {
				throw new MantisBT\Exception\Missing_Font();
			}
		}

		// select first TrueTypeFont
		$this->change_TTF();
		//Set current TrueType-File: (".$this->TTF_file.")";

		// get number of noise-chars for background if is enabled
		$this->nb_noise = $this->noise ? ($this->chars * $this->noisefactor) : 0;
		//Set number of noise characters to: (".$this->nb_noise.")";

		// set dimension of image
		$this->lx = ($this->chars + 1) * (int)(($this->maxsize + $this->minsize) / 1.5);
		$this->ly = (int)(2.4 * $this->maxsize);
		//Set image dimension to: (".$this->lx." x ".$this->ly.")";
	}

	/**
	 * Generate captcha
	 * @param string private key
	 */
	function make_captcha( $private_key )
	{
		self::init();
		// create Image and set the apropriate function depending on GD-Version & websafecolor-value
		if(!$this->websafecolors)
		{
			$func1 = 'imagecreatetruecolor';
			$func2 = 'imagecolorallocate';
		}
		else
		{
			$func1 = 'imageCreate';
			$func2 = 'imagecolorclosest';
		}
		$image = $func1($this->lx,$this->ly);
		//Generate ImageStream with: ($func1())";
		//For colordefinitions we use: ($func2())";

		// Set Backgroundcolor
		$this->random_color(224, 255);
		$back =  @imagecolorallocate($image, $this->r, $this->g, $this->b);
		@ImageFilledRectangle($image,0,0,$this->lx,$this->ly,$back);
		//We allocate one color for Background: (".$this->r."-".$this->g."-".$this->b.")";

		// allocates the 216 websafe color palette to the image
		if($this->websafecolors) $this->makeWebsafeColors($image);

		// fill with noise or grid
		if($this->nb_noise > 0)
		{
			// random characters in background with random position, angle, color
			//Fill background with noise: (".$this->nb_noise.")";
			for($i=0; $i < $this->nb_noise; $i++)
			{
				srand((double)microtime()*1000000);
				$size	= intval(rand((int)($this->minsize / 2.3), (int)($this->maxsize / 1.7)));
				srand((double)microtime()*1000000);
				$angle	= intval(rand(0, 360));
				srand((double)microtime()*1000000);
				$x		= intval(rand(0, $this->lx));
				srand((double)microtime()*1000000);
				$y		= intval(rand(0, (int)($this->ly - ($size / 5))));
				$this->random_color(160, 224);
				$color	= $func2($image, $this->r, $this->g, $this->b);
				srand((double)microtime()*1000000);
				$text	= chr(intval(rand(45,250)));
				if(count ($this->TTF_RANGE)>0){
					@ImageTTFText($image, $size, $angle, $x, $y, $color, $this->change_TTF(), $text);
				} else {
					imagestring($image,5,$x,$y,$text,$color);
				}
			}
		}
		else
		{
			// generate grid
			// Fill background with x-gridlines: (".(int)($this->lx / (int)($this->minsize / 1.5)).")";
			for($i=0; $i < $this->lx; $i += (int)($this->minsize / 1.5))
			{
				$this->random_color(160, 224);
				$color	= $func2($image, $this->r, $this->g, $this->b);
				@imageline($image, $i, 0, $i, $this->ly, $color);
			}
			//Fill background with y-gridlines: (".(int)($this->ly / (int)(($this->minsize / 1.8))).")";
			for($i=0 ; $i < $this->ly; $i += (int)($this->minsize / 1.8))
			{
				$this->random_color(160, 224);
				$color	= $func2($image, $this->r, $this->g, $this->b);
				@imageline($image, 0, $i, $this->lx, $i, $color);
			}
		}

		// generate Text
		// Fill forground with chars and shadows: (".$this->chars.")";
		for($i=0, $x = intval(rand($this->minsize,$this->maxsize)); $i < $this->chars; $i++)
		{
			$text	= utf8_strtoupper(substr($private_key, $i, 1));
			srand((double)microtime()*1000000);
			$angle	= intval(rand(($this->maxrotation * -1), $this->maxrotation));
			srand((double)microtime()*1000000);
			$size	= intval(rand($this->minsize, $this->maxsize));
			srand((double)microtime()*1000000);
			$y		= intval(rand((int)($size * 1.5), (int)($this->ly - ($size / 7))));
			$this->random_color(0, 127);
			$color	=  $func2($image, $this->r, $this->g, $this->b);
			$this->random_color(0, 127);
			$shadow = $func2($image, $this->r + 127, $this->g + 127, $this->b + 127);
			if(count($this->TTF_RANGE) > 0){
				@ImageTTFText($image, $size, $angle, $x + (int)($size / 15), $y, $shadow, $this->change_TTF(), $text);
				@ImageTTFText($image, $size, $angle, $x, $y - (int)($size / 15), $color, $this->TTF_file, $text);
			} else {
				$t_font = rand(3,5);
				imagestring($image,$t_font,$x + (int)($size / 15),$y-20,$text,$color);
				imagestring($image,$t_font,$x,$y - (int)($size / 15)-20,$text,$color);
			}
			$x += (int)($size + ($this->minsize / 5));
		}
		header('Content-type: image/jpeg');
		@ImageJPEG($image, '', $this->jpegquality);
		@ImageDestroy($image);
		//Destroy Imagestream.";
	}

	/**
	 * Generate web safe colors
	 * @param resource image resource
	 */
	private function makeWebsafeColors(&$image)
	{
		for($r = 0; $r <= 255; $r += 51)
		{
			for($g = 0; $g <= 255; $g += 51)
			{
				for($b = 0; $b <= 255; $b += 51)
				{
					$color = imagecolorallocate($image, $r, $g, $b);
					//$a[$color] = array('r'=>$r,'g'=>$g,'b'=>$b);
				}
			}
		}
		// Allocate 216 websafe colors to image: (".imagecolorstotal($image).")";
	}

	/**
	 * Generate random RGB value - min,max between 0 and 255
	 * @param int value between 0 and 255
	 * @param int value between 0 and 255
	 */
	function random_color($min,$max)
	{
		srand((double)microtime() * 1000000);
		$this->r = intval(rand($min,$max));
		srand((double)microtime() * 1000000);
		$this->g = intval(rand($min,$max));
		srand((double)microtime() * 1000000);
		$this->b = intval(rand($min,$max));
	}

	/**
	 * Change true type font
	 */
	function change_TTF()
	{
		if(count($this->TTF_RANGE) > 0){
			if(is_array($this->TTF_RANGE))
			{
				srand((float)microtime() * 10000000);
				$key = array_rand($this->TTF_RANGE);
				$this->TTF_file = $this->TTF_folder.$this->TTF_RANGE[$key];
			}
			else
			{
				$this->TTF_file = $this->TTF_folder.$this->TTF_RANGE;
			}
			return $this->TTF_file;
		}
	}
}