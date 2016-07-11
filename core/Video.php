<?php
/**
 * Video 處理器
 * <p>v1.0 2016-01-20</p>
 * @author lion
 */
namespace Core;
class Video {
	private $duration;
	private $filesize;
	private $framerate;
	private $framerate_target;
	private $height;
	private $height_target;
	private $file;
	private $file_target;
	private $rotate;
	private $screenshot_time = 0;
	private $screenshot_type = 'jpg';
	private $type;
	private $width;
	private $width_target;
	
	function __construct() {
		if (!trim(shell_exec('ffmpeg -version'))) throw new \Exception('Not found ffmpeg');
		
		if (!trim(shell_exec('ffprobe -version'))) throw new \Exception('Not found ffprobe');
	}
	
	/**
	 * 取得持續時間
	 * @return integer(秒)
	 */
	function getDuration() {
		return $this->duration;
	}
	
	/**
	 * 取得截圖時間
	 * @return integer(秒)/string
	 */
	function getScreenshotTime() {
		return $this->screenshot_time;
	}
	
	/**
	 * 取得截圖類型
	 * @return string
	 */
	function getScreenshotType() {
		return $this->screenshot_type;
	}
	
	/**
	 * 取得類型
	 * @return string
	 */
	function getType() {
		return $this->type;
	}
	
	/**
	 * 儲存視頻
	 * @param string $file_target: 目標檔案絕對路徑
	 * @param boolean $overwrite: 是否覆寫目標
	 * @param boolean $delete: 是否刪除原檔(包含所有尺寸)
	 * @param boolean $suffix: 是否添加後綴
	 * @return string
	 */
	function save($file_target=null, $overwrite=false, $delete=false, $suffix=true) {
		if ($file_target === null) {
			$pathinfo = pathinfo($this->file);
			
			$filename = ($suffix && ($this->width_target != $this->width || $this->height_target != $this->height))? $pathinfo['filename'] . '_' . $this->width_target . 'x' . $this->height_target : $pathinfo['filename'];
			
			$this->file_target = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $filename . '.' . $pathinfo['extension'];
		} else {
			$this->file_target = $file_target;
		}
		
		if (!is_file($this->file_target) || $overwrite) {
			shell_exec('ffmpeg -i '.escapeshellarg($this->file).' -vf '.escapeshellarg('scale='.$this->width_target.':'.$this->height_target).' '.escapeshellarg($this->file_target));
		}
		
		if ($delete && $this->getSource($this->file_target) != $this->getSource($this->file)) {
			$pathinfo = pathinfo($this->file);
			foreach (glob($pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '*.' . $pathinfo['extension']) as $v0) {
				unlink($v0);
			}
		}
		
		return $this->file_target;
	}
	
	/**
	 * 儲存視頻截圖
	 * @param string $file_target: 目標檔案絕對路徑
	 * @param boolean $overwrite: 是否覆寫目標
	 * @param boolean $suffix: 是否添加後綴
	 * @return string
	 */
	function saveScreenshot($file_target=null, $overwrite=false, $suffix=true) {
		if ($file_target === null) {
			$pathinfo = pathinfo($this->file);
				
			$filename = ($suffix && ($this->width_target != $this->width || $this->height_target != $this->height))? $pathinfo['filename'] . '_' . $this->width_target . 'x' . $this->height_target : $pathinfo['filename'];
				
			$file_target = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $filename . '.' . $this->getScreenshotType();
		}
		
		if (!is_image($file_target) || $overwrite) {
			if (is_file($file_target)) unlink($file_target);
			
			//處理翻轉
			switch ($this->rotate) {
				case 90:
					$vf = '-vf transpose=1';
					break;
					
				case 180:
					$vf = '-vf transpose=1,transpose=1';
					break;
					
				case 270:
					$vf = '-vf transpose=2';
					break;
					
				default:
					$vf = null;
					break;
			}
			
			shell_exec('ffmpeg -i '.escapeshellarg($this->file).' -an -s '.escapeshellarg($this->width_target.'x'.$this->height_target).' -ss '.escapeshellarg($this->getScreenshotTime()).' '.$vf.' -vframes 1 '.escapeshellarg($file_target));
		}
		
		return $file_target;
	}
	
	/**
	 * 設置視頻, 取得資訊指令參考 https://trac.ffmpeg.org/wiki/FFprobeTips
	 * @param string $file: 檔案絕對路徑
	 * @param boolean $source: 是否尋回原檔案進行處理
	 * @return \Core\Video
	 */
	function setFile($file, $source=true) {
		if ($source) {
			$pathinfo = pathinfo($file);
			$tmp0 = explode('_', $pathinfo['filename']);
			if (isset($tmp0[1])) {
				$file = $pathinfo['dirname'].DIRECTORY_SEPARATOR.preg_replace('/(_[0-9]+x[0-9]+)$/i', '', $pathinfo['filename']).'.'.$pathinfo['extension'];
			}
		}
		
		if (!is_video($file)) throw new \Exception('File\'s type is incorrect.');
		
		$this->file_target = $this->file = $file;
		
		$entries = json_decode(shell_exec('ffprobe -print_format json -select_streams v:0 -show_entries format=duration,size:stream=avg_frame_rate,codec_name,height,width:stream_tags=rotate '.escapeshellarg($file)), true);
		
		//stream
		$this->framerate = $entries['streams'][0]['avg_frame_rate'];
		$this->height = $entries['streams'][0]['height'];
		$this->rotate = isset($entries['streams'][0]['tags']['rotate'])? $entries['streams'][0]['tags']['rotate'] : null;
		$this->type = $entries['streams'][0]['codec_name'];
		$this->width = $entries['streams'][0]['width'];
		
		//format
		$this->duration = $entries['format']['duration'];
		$this->filesize = $entries['format']['size'];
		
		//處理翻轉
		switch ($this->rotate) {
			case 90:
			case 270:
				$tmp0 = $this->height;
				$this->height = $this->width;
				$this->width = $tmp0;
				break;
		}
		$this->height_target = $this->height;
		$this->width_target = $this->width;
		
		return $this;
	}
	
	/**
	 * 設置截圖時間
	 * @param integer(秒)/string $screenshot_time: 截圖時間
	 * @return \Core\Video
	 */
	function setScreenshotTime($screenshot_time) {
		$this->screenshot_time = $screenshot_time;
		
		return $this;
	}
	
	/**
	 * 設置截圖類型
	 * @param string $screenshot_type: 截圖類型
	 * @return \Core\Video
	 */
	function setScreenshotType($screenshot_type) {
		$this->screenshot_type = $screenshot_type;
	
		return $this;
	}
	
	/**
	 * 設置視頻寬、高
	 * @param number $width: 寬
	 * @param number $height: 高
	 * @param boolean $forced: 是否強制縮放為指定寬高
	 * @return \Core\Video
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
	 * 截視頻的圖
	 * @param String $file_source 來源
	 * @param String $file_target 存圖位置
	 * @param Number $ScreenShotTime 截圖時間點 (秒)
	 * @return boolean
	 */
	function setThumbnail($file_source, $file_target, $ScreenShotTime = 1) {
		if (!is_video($file_source)) throw new \Exception('File\'s type is incorrect.');
		
		$return = true;
		if (!exec('ffmpeg -y -i "'.$file_source.'" -ss "'.$ScreenShotTime.'" -frames 2 -f image2 "'.$file_target.'"')) $retrun = false;
		
		return $return;
	}
}