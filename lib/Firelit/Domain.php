<?php

class Domain extends Firelit\DatabaseObject {

	protected static $tableName = 'Domains'; // The table name
	protected static $primaryKey = 'ID'; // The primary key for table (or false if n/a)

	protected static $colsSerialize = array('contact', 'customization', 'smtp', 'gateways', 'merchapp');

	public function __get($name) {

		$value = parent::__get($name);

		if (in_array($name, static::$colsSerialize) && !is_null($value)) {
			$value = unserialize($value);
		}

		return $value;

	}

	public function __set($name, $value) {

		if (is_null($value)) return parent::__set($name, $value);

		if (in_array($name, static::$colsSerialize))
			$value = serialize($value);

		return parent::__set($name, $value);

	}

}