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
	 * 儲存視頻
	 * @param string $file_target: 目標檔案絕對路徑
	 * @param boolean $overwrite: 是否覆寫目標
	 * @throws Exception
	 * @return string
	 */
	function save($file_target=null, $overwrite=false) {
		if ($file_target != null) $this->file_target = $file_target;
		
		if (!is_file($this->file_target) || $overwrite) {
			exec('ffmpeg -i '.$this->file.' -vf scale='.$this->width_target.':'.$this->height_target.' '.$this->file_target);
		}
		
		return $this->file_target;
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
		
		if (!is_video($file)) throw new \Exception("[".__METHOD__."] Parameters error");
		
		$this->file_target = $this->file = $file;
		
		unset($entries);//exec 會在 array 末端追加內容, 保全起見先 unset
		exec('ffprobe -of default=noprint_wrappers=1:nokey=1 -select_streams v:0 -show_entries format=duration,size:stream=avg_frame_rate,height,width -v error '.$file, $entries);
		list($this->width, $this->height, $this->framerate, $this->duration, $this->filesize) = $entries;//注意丟出來的資訊有順序, 不隨指令變動
		
		$this->width_target = $this->width;
		$this->height_target = $this->height;
		
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
			if (!$forced && $this->width != $width && $this->height != $height) {
				$w_rate = $this->width / $width;
				$h_rate = $this->height / $height;
					
				//rate 皆為大於 1 或 小於 1, 否則當作 $forced = true 處理
				if (($w_rate > 1 && $h_rate > 1) || ($w_rate < 1 && $h_rate < 1)) {
					$rate = (abs($w_rate - 1) > abs($h_rate - 1))? $w_rate : $h_rate;
					$width = round($this->width / $rate);
					$height = round($this->height / $rate);
				}
			}
				
			$pathinfo = pathinfo($this->file_target);
				
			$this->file_target = $pathinfo['dirname'].DIRECTORY_SEPARATOR.$pathinfo['filename'].'_'.$width.'x'.$height.'.'.$pathinfo['extension'];
			$this->width_target = $width;
			$this->height_target = $height;
		}
		
		return $this;
	}
}