<?php
/**
 * QRcode 處理器
 * <p>v1.0 2015-11-26</p>
 * @author lion
 */
namespace lib;
class QRcode {
	private $file;
	private $file_target;
	private $frame;
	private $imagetype_target = 2;//2 = JPG，3 = PNG
	private $size_target = 3;
	private $text;
	private $level_target = 0;
	private $margin_target = 4;
	
	function __construct() {}
	
	/**
	 * 儲存檔案
	 * @param string $file_target: 檔案目標完整路徑
	 * @param boolean $overwrite: 是否覆寫檔案目標
	 * @throws exception
	 * @return string
	 */
	function save($file_target=null, $overwrite=false, $print=false) {
		if ($file_target != null) $this->file_target = $file_target;
		
		$this->frame = \QRcode::text($this->text, false, $this->level_target, $this->size_target, $this->margin_target);
			
		$w = strlen($this->frame[0]);
		$h = count($this->frame);
		
		$imgW = $w + 2 * $this->margin_target;
		$imgH = $h + 2 * $this->margin_target;
		
		$base_image = imagecreate($imgW, $imgH);
		switch ($this->imagetype_target) {
			case 2:
				$col[0] = imagecolorallocate($base_image, 255, 255, 255);
				break;
					
			case 3:
				imagesavealpha($base_image, true);
				$col[0] = imagecolorallocatealpha($base_image, 0, 0, 0, 127);
				break;
		}
		$col[1] = imagecolorallocate($base_image, 0, 0, 0);
		imagefill($base_image, 0, 0, $col[0]);
		
		for ($y = 0 ; $y < $h ; $y++) {
			for ($x = 0 ; $x < $w ; $x++) {
				if ($this->frame[$y][$x] == '1') {
					imagesetpixel($base_image, $x + $this->margin_target, $y + $this->margin_target, $col[1]);
				}
			}
		}
		
		$target_image = imagecreate($imgW * $this->size_target, $imgH * $this->size_target);
		imagecopyresized($target_image, $base_image, 0, 0, 0, 0, $imgW * $this->size_target, $imgH * $this->size_target, $imgW, $imgH);
		imagedestroy($base_image);
		
		if ($this->file_target != null && (!is_file($this->file_target) || $overwrite)) {
			switch ($this->imagetype_target) {
				case 2:
					imagejpeg($target_image, $this->file_target);
					break;
						
				case 3:
					imagepng($target_image, $this->file_target);
					break;
			}
		}
		
		if ($print) {
			switch ($this->imagetype_target) {
				case 2:
					header('Content-type: image/jpeg');
					imagepng($target_image);
					break;
			
				case 3:
					header('Content-type: image/png');
					imagejpeg($target_image);
					break;
			}
			imagedestroy($target_image);
			
			return;
		} else {
			imagedestroy($target_image);
			
			return $this->file_target;
		}
	}
	
	function setTextEmail($email, $subject=null, $body=null) {
		$tmp0 = [];
		if ($subject !== null) $tmp0['subject'] = urlencode($subject);
		if ($body !== null) $tmp0['body'] = urlencode($body);
		
		$this->text = 'mailto:'.$email.($tmp0)? null : '?'.http_build_query($tmp0);
		
		return $this;
	}
	
	function setTextPhone($phone) {
		$this->text = 'tel:'.$phone;
		
		return $this;
	}
	
	function setTextUrl($url) {
		$this->text = $url;
		
		return $this;
	}
	
	/**
	 * 設置檔案
	 * @param string $file: 檔案完整路徑
	 * @return \lib\QRcode
	 */
	function setFile($file) {
		if (!is_file($file)) throw new \Exception('Parameters error');
		
		$this->file_target = $this->file = $file;
		
		return $this;
	}
	
	function setImageType($imagetype) {
		switch ($imagetype) {
			case 'jpg':case 'jpeg':
				$imagetype_target = 2;
				break;
			
			case 'png':
				$imagetype_target = 3;
				break;
				
			default:
				throw new \Exception('Unknown case');
				break;
		}
		
		$this->imagetype_target = $imagetype_target;
		
		return $this;
	}
	
	/**
	 * 設置 size
	 * @param int $size
	 */
	function setSize($size) {
		$this->size_target = $size;
		
		return $this;
	}
	
	/**
	 * 設置 level
	 * @param unknown $level
	 * @throws \Exception
	 */
	function setLevel($level) {
		switch ($level.'') {
			case '0':case '1':case '2':case '3':
				$level_target = $level;
				break;
				
			case 'l':case 'L':
				$level_target = 0;
				break;
				
			case 'm':case 'M':
				$level_target = 1;
				break;
				
			case 'q':case 'Q':
				$level_target = 2;
				break;
				
			case 'h':case 'H':
				$level_target = 3;
				break;
				
			default:
				throw new \Exception('Unknown case');
				break;
		}
		
		$this->level_target = $level_target;
		
		return $this;
	}
	
	/**
	 * 設置 margin
	 * @param int $margin
	 */
	function setMargin($margin) {
		$this->margin_target = $margin;
		
		return $this;
	}
}