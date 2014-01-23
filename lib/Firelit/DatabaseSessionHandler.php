<?php

namespace Firelit;

class DatabaseSessionHandler implements \SessionHandlerInterface {

	static public $config = array(
		'tableName' => 'Sessions', // Table where it is all stored
		'colKey' => 'key', // The key column for the unique session ID
		'colData' => 'data', // The data column for storing session data
		'colExp' => 'expires', // The datetime column for expiration date
		'expSeconds' => 14400 // 4 hours
	);

	public function open($savePath, $sessionName) {
		return true;
	}

	public function close() {
		return true;
	}

	public function read($id) {

		$sql = "SELECT `". self::$config['colData'] ."` FROM `". self::$config['tableName'] ."` WHERE `". self::$config['colKey'] ."`=:session_id AND `". self::$config['colExp'] ."` > NOW() LIMIT 1";

		$q = new Query($sql, array(
			':session_id' => $id
		));

		return $q->getRow();

    }

	public function write($id, $data) {

    	Query::replace(self::$config['tableName'], array(
    		self::$config['colKey'] => $id,
    		self::$config['colData'] => $data,
    		self::$config['colExp'] => Query::SQL('DATE_ADD(NOW(), INTERVAL '. self::$config['expSeconds'] .' SECOND)')
    	));

        return true;

	}

	public function destroy($id) {

		$sql = "DELETE FROM `". self::$config['tableName'] ."` WHERE `". self::$config['colKey'] ."`=:session_id LIMIT 1";

		new Query($sql, array(
			':session_id' => $id
		));

		return true;

	}

	public function gc($maxlifetime) {
    	// $maxlifetime not implemented. 

		$sql = "DELETE FROM `". self::$config['tableName'] ."` WHERE `". self::$config['colExp'] ."` <= NOW()";

		new Query($sql, array(
			':session_id' => $id
		));

        return true;

	}
}