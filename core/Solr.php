<?php
namespace Core;
class Solr {
	private static $instance = [];
	private $core;
	
	private static $column = [];
	private static $where = [];//q 與 fq 有效能上的處理，參考 http://stackoverflow.com/questions/11627427/solr-query-q-or-filter-query-fq
	private static $order = [];
	private static $limit;
	
	function __construct($core) {
		if (!class_exists('\SolrClient')) {
			throw new \Exception('Not found : Class of [\SolrClient] in '.__METHOD__);
		}
		
		if (!isset(self::$instance[$core])) {
			$options = [
					'hostname'=>'localhost',
					'path'=>'solr/'.$core,
					'port'=>8983,
					'wt'=>'json',
			];
			self::$instance[$core] = new \SolrClient($options);
		}
		$this->core = $core;
	}
	
	function add(array $param) {
		if (empty($param)) throw new Exception("[".__METHOD__."] Parameters error");
		
		$doc = new \SolrInputDocument();
		
		foreach ($param as $k0 => $v0) {
			$doc->addField($k0, $v0);
		}
		
		$response = self::$instance[$this->core]->addDocument($doc);
		
		self::$instance[$this->core]->commit();
		
		return $response->success();
	}
	
	function column(array $column=null) {
		if ($column) self::$column = array_merge(self::$column, $column);
		return $this;
	}
	
	function delete() {
		$response = self::$instance[$this->core]->deleteByQuery(empty(self::$where)? '*:*' : implode(' AND ', self::$where));
		$this->reset();
		self::$instance[$this->core]->commit();
		
		return $response->success();
	}
	
	function edit(array $param) {
		return $this->add($param);
	}
	
	function fetch($expire=null) {
		return $this->fetch_logic(__FUNCTION__, $expire);
	}
	
	function fetchAll($expire=null) {
		return $this->fetch_logic(__FUNCTION__, $expire);
	}
	
	function fetch_logic($fetch_case, $expire) {
		$query = new \SolrQuery();
		
		$query->setQuery('*:*');
		
		if (!empty(self::$column)) foreach (self::$column as $v0) $query->addField($v0);
		
		if (!empty(self::$where)) $query->addFilterQuery(implode(' AND ', self::$where));
		
		if (!empty(self::$order)) foreach (self::$order as $k0 => $v0) {$order = $v0 == 'asc'? $query::ORDER_ASC : $query::ORDER_DESC; $query->addSortField($k0, $order);}
		
		if (empty(self::$limit)) {
			$start = 0;
			$rows = Model($this->core)->column(['count(1)'])->fetchColumn();
		} else {
			list($start, $rows) = explode(',', self::$limit);
		}
		$query->setStart($start)->setRows($rows);
		
		$this->reset();
		
		$query_response = self::$instance[$this->core]->query($query);
		
		$sql = $query_response->getRequestUrl().'&'.$query_response->getRawRequest();//^整合 memcache?
		
		$response = $query_response->getResponse();
		
		$data = null;
		
		if ($response->response->docs) {
			switch ($fetch_case) {
				case 'fetch':
					$data = (array)$response->response->docs[0];
					break;
			
				case 'fetchAll':
					foreach ($response->response->docs as $v0) {
						$data[] = (array)$v0;
					}
					break;
			
				default:
					throw new \Exception("[".__METHOD__."] Unknown case");
					break;
			}
		}
		
		return $data;
	}
	
	function limit($limit=null) {
		if ($limit) self::$limit = str_replace(' ', '', $limit);
		
		return $this;
	}
	
	function order(array $order=null) {
		if ($order) {
			foreach ($order as $k0 => $v0) {
				self::$order[trim($k0)] = strtolower(trim($v0));
			}
		}
		
		return $this;
	}
	
	function reset() {
		self::$column = [];
		self::$where = [];
		self::$order = [];
		self::$limit = null;
	}
	
	function where(array $where=null) {
		if ($where) {
			foreach ($where as $v0) {
				list($filters, $logic) = $v0;
				$tmp0 = [];
				foreach ($filters as $v1) {
					list($field, $operator, $value) = $v1;
					switch ($operator) {
						case '=':
							$tmp0[] = $field.':'.\SolrUtils::escapeQueryChars($value);
							break;
				
						case '!=':
							$tmp0[] = '-'.$field.':'.\SolrUtils::escapeQueryChars($value);
							break;
				
						default:
							throw new \Exception("[".__METHOD__."] Unknown case of where's operator");
							break;
					}
				}
				switch (strtolower($logic)) {
					case 'and':
						self::$where[] = implode(' '.strtoupper($logic).' ', $tmp0);
						break;
						
					case 'or':
						self::$where[] = '('.implode(' '.strtoupper($logic).' ', $tmp0).')';
						break;
				
					default:
						throw new \Exception("[".__METHOD__."] Unknown case of where's logic");
						break;
				}
			}
		}
		
		return $this;
	}
}