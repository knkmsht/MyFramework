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
	 * 設置音頻, 取得資訊指令參考 http://ubuntuforums.org/archive/index.php/t-1708195.html
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
	
		if (!is_audio($file)) throw new \Exception("[".__METHOD__."] Parameters error");
	
		$this->file_target = $this->file = $file;
	
		unset($entries);//exec 會在 array 末端追加內容, 保全起見先 unset
		exec('ffprobe -of default=noprint_wrappers=1:nokey=1 -select_streams v:0 -show_entries format=bit_rate,duration,format_name,size -v error '.$file, $entries);
		list($this->type, $this->duration, $this->filesize, $this->bitrate) = $entries;//注意丟出來的資訊有順序, 不隨指令變動
	
		return $this;
	}
}