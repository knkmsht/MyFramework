<?php
/**
 * Image 處理器
 * <p>v1.0 2015-06-29</p>
 * @author lion
 */
namespace Core;
class Image {
	private $attr;
	private $exif;
	private $height;
	private $height_target;
	private $image;
	private $image_target;
	private $imagick = false;
	private $quality;
	private $quality_target;
	private $type;//1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
	private $type_correspond = [1=>'gif', 2=>'jpg', 3=>'png', 6=>'bmp'];
	private $type_target;
	private $width;
	private $width_target;
	
	function __construct() {
		if (class_exists('Imagick')) $this->imagick = true;
	}
	
	/**
	 * 取得圖檔屬性
	 * @return string
	 */
	function getAttr() {
		return $this->attr;
	}
	
	/**
	 * 取得圖檔高度
	 * @return integer
	 */
	function getHeight() {
		return $this->height;
	}
	
	/**
	 * 取得圖檔目標高度
	 * @return integer
	 */
	function getHeightTarget() {
		return $this->height_target;
	}
	
	/**
	 * 取得圖檔品質
	 * @return int
	 */
	function getQuality() {
		return $this->quality;
	}
	
	/**
	 * 取得原圖檔完整路徑
	 * @param string $file
	 * @return string
	 */
	function getSource($file) {
		$pathinfo = pathinfo($file);
		$tmp0 = explode('_', $pathinfo['filename']);
		if (isset($tmp0[1])) $file = $pathinfo['dirname'].DIRECTORY_SEPARATOR.preg_replace('/(_[0-9]+x[0-9]+)$/i', '', $pathinfo['filename']).'.'.$pathinfo['extension'];
		
		return $file;
	}
	
	/**
	 * 取得圖檔類型
	 * @return string
	 */
	function getType() {
		return $this->type;
	}
	
	/**
	 * 取得圖檔寬度
	 * @return integer
	 */
	function getWidth() {
		return $this->width;
	}
	
	/**
	 * 取得圖檔目標寬度
	 * @return integer
	 */
	function getWidthTarget() {
		return $this->width_target;
	}
	
	/**
	 * 儲存圖檔
	 * @param string $image_target: 目標圖檔完整路徑
	 * @param string $overwrite: 是否覆寫目標圖檔
	 * @param string $delete: 是否刪除原圖檔(包含所有尺寸)
	 * @param boolean $suffix: 是否添加後綴
	 * @throws \Exception
	 * @return string
	 */
	function save($image_target=null, $overwrite=false, $delete=false, $suffix=true) {
		if ($image_target === null) {
			$pathinfo = pathinfo($this->image);
			
			$filename = ($suffix && ($this->width_target != $this->width || $this->height_target != $this->height))? $pathinfo['filename'] . '_' . $this->width_target . 'x' . $this->height_target : $pathinfo['filename'];
			
			$this->image_target = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $filename . '.' . $this->type_correspond[$this->type_target];
		} else {
			$this->image_target = $image_target;
		}
		
		if (!is_file($this->image_target) || $overwrite) {
			if ($this->imagick) {
				$imagick = new \Imagick();
				$imagick->setResolution(108, 108);//max 300, 300; must be called before loading or creating an image.
				$imagick->readimage($this->image);
				$imagick->setImageFormat($this->type_correspond[$this->type_target]);
				
				if ($this->type == 2 && isset($this->exif['Orientation'])) {
					switch ($this->exif['Orientation']) {
						case 0: // undefined?
						case 1: // nothing
							break;
								
						case 2: // horizontal flip
							$imagick->flopImage();
							break;
								
						case 3: // 180 rotate left
							$imagick->rotateImage(new \ImagickPixel(), 180);
							break;
								
						case 4: // vertical flip
							$imagick->flipImage();
							break;
								
						case 5: // vertical flip + 90 rotate right
							$imagick->flipImage();
							$imagick->rotateImage(new \ImagickPixel(), 90);
							break;
								
						case 6: // 90 rotate right
							$imagick->rotateImage(new \ImagickPixel(), 90);
							break;
								
						case 7: // horizontal flip + 90 rotate right
							$imagick->flopImage();
							$imagick->rotateImage(new \ImagickPixel(), 90);
							break;
								
						case 8: // 90 rotate left
							$imagick->rotateImage(new \ImagickPixel(), -90);
							break;
								
						default:
							throw new \Exception('Unknown case');
							break;
					}
					
					$imagick->setImageOrientation($imagick::ORIENTATION_TOPLEFT);
				}
				
				if ($this->type_target == 2) {
					$imagick->setImageCompression($imagick::COMPRESSION_JPEG);
					$imagick->setImageCompressionQuality($this->quality_target);
					$imagick->setInterlaceScheme($imagick::INTERLACE_PLANE);//參考 http://stackoverflow.com/questions/7261855/recommendation-for-compressing-jpg-files-with-imagemagick
				}
				
				$imagick->resizeImage($this->width_target, $this->height_target, $imagick::FILTER_CATROM, 1);
				$imagick->writeImage($this->image_target);
				$imagick->clear();
			} else {
				switch ($this->type) {
					case 1:
						$im_source = imagecreatefromgif($this->image);
						break;
				
					case 2:
						$im_source = imagecreatefromjpeg($this->image);
							
						if (isset($this->exif['Orientation'])) {
							//翻轉來源圖像
							switch ($this->exif['Orientation']) {
								case 0: // undefined?
								case 1: // nothing
									break;
										
								case 2: // horizontal flip
									imageflip($im_source, 1);
									break;
										
								case 3: // 180 rotate left
									$im_source = imagerotate($im_source, 180, 0);
									break;
										
								case 4: // vertical flip
									imageflip($im_source, 2);
									break;
										
								case 5: // vertical flip + 90 rotate right
									imageflip($im_source, 2);
									$im_source = imagerotate($im_source, -90, 0);
									break;
										
								case 6: // 90 rotate right
									$im_source = imagerotate($im_source, -90, 0);
									break;
										
								case 7: // horizontal flip + 90 rotate right
									imageflip($im_source, 1);
									$im_source = imagerotate($im_source, -90, 0);
									break;
										
								case 8: // 90 rotate left
									$im_source = imagerotate($im_source, 90, 0);
									break;
										
								default:
									throw new \Exception('Unknown case');
									break;
							}
						}
						break;
				
					case 3:
						$im_source = imagecreatefrompng($this->image);
						break;
							
					case 6:
						$im_source = imagecreatefromwbmp($this->image);
						break;
							
					default:
						//^ 用 imagecreatefromstring ?
						throw new \Exception('Unknown case');
						break;
				}
					
				/**
				 * 創建圖像標識符
				 * 2014-08-01: 應該不是判斷圖檔類型來使用 imagecreate 或 imagecreatetruecolor, 而是要判斷圖色構成(方法尋找中..)
				 */
				$im_new = imagecreatetruecolor($this->width_target, $this->height_target);
				switch ($this->type_target) {
					case 1:
					case 3:
						imagecolortransparent($im_new, imagecolorallocatealpha($im_new, 0, 0, 0, 127));
						imagealphablending($im_new, false);
						imagesavealpha($im_new, true);
						break;
							
					case 2:
						break;
							
					default:
						throw new \Exception('Unknown case');
						break;
				}
				imagecopyresampled($im_new, $im_source, 0, 0, 0, 0, $this->width_target, $this->height_target, $this->width, $this->height);
					
				//輸出目標圖像
				switch ($this->type_target) {
					case 1:
						imagegif($im_new, $this->image_target);
						break;
				
					case 2:
						imagejpeg($im_new, $this->image_target, $this->quality_target);
						imageinterlace($im_new, 1);
						break;
				
					case 3:
						imagepng($im_new, $this->image_target);
						break;
				
					case 6:
						imagewbmp($im_new, $this->image_target);
						break;
							
					default:
						throw new \Exception('Unknown case');
						break;
				}
					
				//釋放圖像內存
				imagedestroy($im_source);
				imagedestroy($im_new);
			}
		}
		
		if ($delete && $this->getSource($this->image_target) != $this->getSource($this->image)) {
			$pathinfo = pathinfo($this->image);
			foreach (glob($pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '*.' . $pathinfo['extension']) as $v0) {
				unlink($v0);
			}
		}
		
		return $this->image_target;
	}
	
	/**
	 * 設置圖檔
	 * @param string $image: 圖檔完整路徑
	 * @param boolean $source: 是否尋回原圖檔進行處理
	 * @return \Core\Image
	 */
	function setImage($image, $source=true) {
		if ($source) $image = $this->getSource($image);
		
		if (!is_image($image)) throw new \Exception('Param error');
		
		$this->image_target = $this->image = $image;
		
		list($this->width, $this->height, $this->type, $this->attr) = getimagesize($this->image);
		
		/**
		 * 2014-07-29:
		 *     有些圖像會有無法解讀的 exif, 因此用 @ 屏蔽
		 * 2014-05-01:
		 *     處理數位圖像翻轉的情況，其中正負 90 度(以及其倍數)翻轉的圖像，用 getimagesize 取得的 $width 和 $height 會和實際相反，因此對換；
		 *     另外 exif_read_data 僅能支持 JPEG、TIFF
		 */
		if (in_array($this->type, array(2, 8))) {
			$this->exif = @exif_read_data($this->image);
		}
		
		if (isset($this->exif['Orientation'])) {
			//width, height 互換
			switch ($this->exif['Orientation']) {
				case 5:
				case 6:
				case 7:
				case 8:
					$tmp0 = $this->height;
					$this->height = $this->width;
					$this->width = $tmp0;
					break;
			}
		}
		
		$this->width_target = $this->width;
		$this->height_target = $this->height;
		$this->type_target = $this->type;
		$this->quality_target = $this->quality = shell_exec('identify -format %Q '.$this->image);
		
		return $this;
	}
	
	/**
	 * 設置圖檔品質(目前僅支持 jpg)
	 * @param int $quality
	 * @throws \Exception
	 * @return \Core\Image
	 */
	function setQuality($quality) {
		$this->quality_target = $quality;
		
		return $this;
	}
	
	/**
	 * 設置圖檔寬、高
	 * @param number $width: 寬
	 * @param number $height: 高
	 * @param boolean $forced: 是否強制縮放為指定寬高
	 * @return \Core\Image
	 */
	function setSize($width=100, $height=100, $forced=false) {
		if ($this->width != $width || $this->height != $height) {
			if (!$forced) {
				$w_rate = $this->width / $width;
				$h_rate = $this->height / $height;
				
				$rate = ($w_rate > $h_rate)? $w_rate : $h_rate;
				
				$width = round($this->width / $rate);
				$height = round($this->height / $rate);
			}
			
			$this->width_target = $width;
			$this->height_target = $height;
		}
		
		return $this;
	}
	
	/**
	 * 設置圖檔類型
	 * @param string $type
	 * @throws Exception
	 * @return \Core\Image
	 */
	function setType($type) {
		//如果 type 有更動，留意 imagick->setImageFormat 的部分也要處理
		switch ($s_type = strtolower($type)) {
			case 'gif':
				$type = 1;
				break;
				
			case 'jpg':
			case 'jpeg':
				$type = 2;
				break;
				
			case 'png':
				$type = 3;
				break;
				
			case 'bmp':
				$type = 6;
				break;
				
			default:
				throw new \Exception('Unknown case');
				break;
		}
		
		$this->type_target = $type;
		
		return $this;
	}
}