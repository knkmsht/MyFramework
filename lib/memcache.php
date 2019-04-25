<?php
namespace lib;
class Memcache {
	private static $instance;
	private static $pool = array();
	private $server = '127.0.0.1';
	private $port = '11211';
	public $expire = 5;
	
	function __construct($param) {
		if (!class_exists('\Memcache')) {
			throw new \Exception('Not found : Class of [\Memcache] in '.__METHOD__);
		}
		
		if (!self::$instance) self::$instance = new \Memcache();
		
		if (!empty($param['SERVER'])) $this->server = $param['SERVER'];
		if (!empty($param['PORT'])) $this->port = $param['PORT'];
		if (!empty($param['EXPIRE'])) $this->expire = $param['EXPIRE'];
		
		if (!in_array($this->server.':'.$this->port, self::$pool)) {
			self::$pool[] = $this->server.':'.$this->port;
			self::$instance->addserver($this->server, $this->port);
		}
	}
	
	function __destruct() {
		return self::$instance->close();
	}
	
	function delete($key) {
		return self::$instance->delete($key, 0);
	}
	
	function exists($key) {
		return !self::$instance->add($key, null);
	}
	
	function get($key) {
		return self::$instance->get($key);
	}
	
	function set($key, $var, $expire) {
		return self::$instance->set($key, $var, MEMCACHE_COMPRESSED, $expire);
	}
}