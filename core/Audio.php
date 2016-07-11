<?php
/**
 * Audio 處理器
 * <p>v1.0 2016-01-25</p>
 * @author lion
 */
namespace Core;
class Audio {
	private $bitrate;
	private $duration;
	private $file;
	private $file_target;
	private $filesize;
	private $type;
	
	function __construct() {
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
	 * 取得類型
	 * @return string
	 */
	function getType() {
		return $this->type;
	}
	
	/**
	 * 儲存檔案
	 * @param string $file_target: 目標圖檔完整路徑
	 * @param string $overwrite: 是否覆寫目標圖檔
	 * @param string $delete: 是否刪除原圖檔(包含所有尺寸)
	 * @param boolean $suffix: 是否添加後綴
	 * @throws \Exception
	 * @return string
	 */
	function save($file_target=null, $overwrite=false, $delete=false, $suffix=true) {
		if ($file_target === null) {
			$this->file_target = $this->file;
		} else {
			$this->file_target = $file_target;
		}
	
		if (!is_file($this->file_target) || $overwrite) {
			
		}
	
		if ($delete) {
			$pathinfo = pathinfo($this->file);
			foreach (glob($pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '*.' . $pathinfo['extension']) as $v0) {
				unlink($v0);
			}
		}
	
		return $this->file_target;
	}
	
	/**
	 * 設置檔案, 取得資訊指令參考 http://ubuntuforums.org/archive/index.php/t-1708195.html
	 * @param string $file: 檔案絕對路徑
	 * @param boolean $source: 是否尋回原檔案進行處理
	 * @return \Core\Audio
	 */
	function setFile($file, $source=true) {
		if ($source) {
			$pathinfo = pathinfo($file);
			$tmp0 = explode('_', $pathinfo['filename']);
			if (isset($tmp0[1])) {
				$file = $pathinfo['dirname'].DIRECTORY_SEPARATOR.preg_replace('/(_[0-9]+x[0-9]+)$/i', '', $pathinfo['filename']).'.'.$pathinfo['extension'];
			}
		}
	
		if (!is_audio($file)) throw new \Exception('File\'s type is incorrect.');
	
		$this->file_target = $this->file = $file;
		
		$entries = json_decode(shell_exec('ffprobe -print_format json -select_streams v:0 -show_entries format=bit_rate,duration,format_name,size '.escapeshellarg($file)), true);
		
		//format
		$this->type = $entries['format']['format_name'];
		$this->duration = $entries['format']['duration'];
		$this->filesize = $entries['format']['size'];
		$this->bitrate = $entries['format']['bit_rate'];
		
		return $this;
	}
}