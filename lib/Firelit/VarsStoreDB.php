<?php

namespace('Firelit');

class VarsStoreDB extends VarsStore {
	
	private $db;
	
	public static $config = array(
		'tableName' => 'Vars',
		'maxNameLength' => 32
	);
	
	public function __construct(Query $queryObject, $config = array()) { 
		
		$this->db = $queryObject;
		
		// Merge config data with defaults
		self::config($config);
		
	}
	
	public static function config($config) {
		
		self::$config = array_merge(self::$config, $config);
			
	}
	
	public function set($name, $value) {
		
		$this->db->replace(self::$config['tableName'], array(
			'name' => $name,
			'value' => serialize($value)
		));
		
		if (!$this->db->success()) 
			throw new \Exception('Error setting value in database.');
			
	}
	
	public function get($name) {
		
		$this->db->select(self::$config['tableName'], array('value'), "`name`=:name", array( 'name' => $name ), 1);
		
		if (!$this->db->success()) 
			throw new \Exception('Error getting value from database.');
			
		if ($row = $this->db->fetch()) return unserialize($row['value']);
		else return null;
		
	}
	
	public static function install(Query $query) {
		// One-time install
		// Create the supporting tables in the db
		
		// Running MySql >= 5.5.3 ? Use utf8mb4 insetad of utf8.
		$sql = "CREATE TABLE IF NOT EXISTS `". self::$config['tableName'] ."` (
			  `name` varchar(". self::$config['maxNameLength'] .") character set utf8 collate utf8_bin NOT NULL,
			  `value` longtext NOT NULL,
			  UNIQUE KEY `name` (`name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
			
		$q = $query->query($sql);
		
		if (!$q->success()) 
			throw new \Exception('Install failed! ('. __FILE__ .':'. __LINE__ .')');
			
		return $query->insertId();
		
	}
	
}