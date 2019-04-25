<?php
namespace lib;
class SphinxClient {
	private static $instance = array();
	private static $database;
	
	//setLimits
	private $offset = 0;
	private $limit = 20;
	private $max_matches = 20;
	
	//setMatchMode
	private $matchmode = SPH_MATCH_ALL;
	
	function __construct($database) {
		if (!class_exists('\SphinxClient')) {
			throw new \Exception('Not found : Class of [\SphinxClient] in '.__METHOD__);
		}
		
		if (!array_key_exists($database, self::$instance)) {
			self::$instance[$database] = new \SphinxClient();
			self::$instance[$database]->setServer(\Core::$_config['CONFIG']['SPHINX'][$database]['HOST'], \Core::$_config['CONFIG']['SPHINX'][$database]['PORT']);
			self::$instance[$database]->setMaxQueryTime(2000);//millisecond
			self::$instance[$database]->setConnectTimeout(5);//second
		}
		
		self::$database = $database;
	}
	
	function query($query, $index='*') {
		self::$instance[self::$database]->setLimits($this->offset, $this->limit, $this->max_matches);
		self::$instance[self::$database]->setMatchMode($this->matchmode);
		
		$result = self::$instance[self::$database]->query(self::$instance[self::$database]->escapeString($query), $index);
		
		return empty($result['matches'])? null : array_keys($result['matches']);
	}
	
	function setLimits($offset=null, $limit=null, $max_matches=null) {
		if ($offset !== null) $this->offset = $offset;
		if ($limit !== null) $this->limit = $limit;
		if ($max_matches !== null) $this->max_matches = $max_matches;
		
		return $this;
	}
	
	function setMatchMode($mode) {
		$this->matchmode = $mode;
		
		return $this;
	}
}