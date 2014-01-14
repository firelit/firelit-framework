<?PHP

namespace Firelit;

class Query {
	
	/* Global connection & state variables */
	private static $pdo = false;
	private static $database = false;
	private static $errorCount = 0;

	/* Object variables */
	private $res;
	
	public function __construct($sql = false, $binders = array()) {

		if ($sql === false) return;

		$this->query($sql, $binders);

	}	
	
	public static function connect() {

		$reg = Registry::get('database');

		if ($reg) {
			if ($reg['type'] == 'mysql') {

				if (!isset($reg['port'])) $reg['port'] = 3306;

				self::$pdo = new \PDO('mysql:host='. $reg['host'] .';port='. $reg['port'] .';dbname='. $reg['name'], $reg['user'], $reg['pass']);

			} else {

				self::$pdo = new \PDO($reg['dsn']);

			}
		} else {

			if (!isset($_SERVER['DB_PORT'])) $_SERVER['DB_PORT'] = 3306;

			self::$pdo = new \PDO('mysql:host='. $_SERVER['DB_HOST'] .';port='. $_SERVER['DB_PORT'] .';dbname='. $_SERVER['DB_NAME'], $_SERVER['DB_USER'], $_SERVER['DB_PASS']);

		}

		if (!self::$pdo) throw new \Exception('Could not connect to database.');

		return self::$pdo;

	}
	
	public function query($sql, $binders = array()) {

		if (is_string($sql))
			$this->cleanBinders($binders, $sql);

		$this->convertDateTimes($binders);

		if (!self::$pdo) self::connect();

		// $sql can be a PDOStatement or a SQL string
		if (is_string($sql))	
			$this->sql = self::$pdo->prepare($sql);
		elseif ($sql instanceof \PDOStatement)
			$this->sql = $sql;
		else
			throw new \Exception('Invalid parameter supplied to query method.');
		
		$this->res = $this->sql->execute($binders);

		if (!$this->res) {
			
			if (self::$errorCount++ > 10) die('ERROR Q'. __LINE__ .' <!-- TOO MANY QUERY ERRORS -->');

			throw new \Exception('Database error: '. $this->getErrorCode() .', '. $this->getError() .', '. $this->sql->queryString);

		}

		return $this->res;

	}
	
	public function cleanBinders(&$binder, $sql) {
		foreach ($binder as $name => $value) {
			if (strpos($sql, $name) === false)
				unset($binder[$name]);
		}
	}

	public function removeNulls(&$binder) {
		// Make DB updates compatible with badly-designed code bases and DB schemas (where NULL is not valid)
		foreach ($binder as $name => $value) {
			if (is_null($value)) $binder[$name] = '';
		}
	}

	public function convertDateTimes(&$binder) {
		foreach ($binder as $name => $value) {
			if (is_object($value) && is_a($value, 'DateTime')) $binder[$name] = $value->format('Y-m-d  H:i:s');
		}
	}

	public function getRes() {
		return $this->res;
	}
	
	public function getAll() {
		if (!$this->res) return false;
		return $this->sql->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function getRow() {
		if (!$this->res) return false;
		return $this->sql->fetch(\PDO::FETCH_ASSOC);
	}
	
	public function getObject($className) {
		if (!$this->res) return false;
		return $this->sql->fetchObject($className);
	}

	public function getNewId() {
		return self::$pdo->lastInsertId();
	}
	
	public function getAffected() {
		return $this->sql->rowCount();
	}
	
	public function getNumRows() {
		// May not always return the correct number of rows
		// See note at http://php.net/manual/en/pdostatement.rowcount.php
		return $this->sql->rowCount();
	}
	
	public function getError() {
		$e = self::$pdo->errorInfo();
		if ($e[0] == '00000') $e = $this->sql->errorInfo();
		return $e[2]; // Driver specific error message.
	}
	
	public function getErrorCode() {
		$e = self::$pdo->errorInfo();
		if ($e[0] == '00000') $e = $this->sql->errorInfo();
		return $e[1]; // Driver specific error code.
	}

	public function getQuery() {
		return $this->sql->queryString;
	}

	public function success() {
		return $this->res;
	}
	
	public function insert($table, $array) {
		// Preform an insert on the table
		// Enter an associative array for $array with column names as keys

		if (!self::$pdo) self::connect();

		list($statementArray, $binderArray) = self::splitArray($array);

		$this->sql = self::$pdo->prepare("INSERT INTO `". $table ."` ". self::toSQL('INSERT', $statementArray));
		
		return $this->query($this->sql, $binderArray);
		
	}
	
	public function replace($table, $array) {
		// Preform an replace on the table
		// Enter an associative array for $array with column names as keys
		
		if (!self::$pdo) self::connect();

		list($statementArray, $binderArray) = self::splitArray($array);

		$this->sql = self::$pdo->prepare("REPLACE INTO `". $table ."` ". self::toSQL('REPLACE', $statementArray));
		
		return $this->query($this->sql, $binderArray);
		
	}
	
	public function update($table, $array, $whereSql, $whereBinder = array()) {
		// Preform an update on the table
		// Enter an associative array for $array with column names as keys
		
		if (!self::$pdo) self::connect();
		
		list($statementArray, $binderArray) = self::splitArray($array);

		// Look for binder conflicts
		foreach ($whereBinder as $placeholder => $value) {
			if (isset($binderArray[$placeholder])) {
				// Binder conflict!
				$newPlaceholder = $placeholder.'_'.mt_rand(100,10000);
				$whereBinder[$newPlaceholder] = $value;
				unset($whereBinder[$placeholder]);

				$whereSql = preg_replace('/'.preg_quote($placeholder).'\b/', $newPlaceholder, $whereSql);
			}

		}
		
		$this->sql = self::$pdo->prepare("UPDATE `". $table ."` SET ". self::toSQL('UPDATE', $statementArray) ." WHERE ". preg_replace('/^\s?WHERE\s/', '', $whereSql));
		
		return $this->query($this->sql, array_merge($binderArray, $whereBinder));
		
	}

	public static function splitArray($arrayIn) {

		if (!is_array($arrayIn))
			throw new \Exception('Parameter is not an array.');

		$statement = array();
		$binder = array();

		foreach ($arrayIn as $key => $value) {
			
			// If value is a 2-element array with the first value
			// having one of the following values, the second is assumed
			// to need special attention.
			if (is_array($value) && (sizeof($value) == 2)) {
				switch (strtoupper($value[0])) {
					case 'SQL':
						if (!is_string($value[1])) break;
						$statement[$key] = $value[1];
						continue 2;
					case 'SERIALIZE':
						$value = serialize($value[1]);
						break;
					case 'JSON':
						$value = json_encode($value[1]);
						break;
				}
			}

			$crossKey = ':'.preg_replace('/[^A-Za-z0-9]+/', '_', $key);

			// Key is already used, add random characters to end
			if (isset($binder[$crossKey])) $crossKey .= '_'. mt_rand(1000, 10000);

			$statement[$key] = $crossKey;

			if (is_object($value) && is_a($value, 'DateTime')) 
				$value = $value->format('Y-m-d H:i:s');

			if (is_array($value) || is_object($value)) 
				$value = serialize($value);

			$binder[$crossKey] = $value;

		}

		return array($statement, $binder);

	}

	public static function toSQL($verb, $assocArray) {
		// $assocArray should be an array of 'raw' items (not yet escaped for database)
		
		$verb = strtoupper($verb);

		if (($verb == 'INSERT') || ($verb == 'REPLACE')) {
			
			$sql1 = '';
			$sql2 = '';
			
			foreach ($assocArray as $key => $value) {
				
				$sql1 .= ', `'. str_replace('`', '', $key) .'`';
				$sql2 .= ", ". $value;
				
			}

			return '('. substr($sql1, 2) . ') VALUES ('. substr($sql2, 2) .')';
			
		} elseif ($verb == 'UPDATE') {
			
			$sql = '';
			
			foreach ($assocArray as $key => $value) {
				
				$sql .= ', `'. str_replace('`', '', $key) .'`='. $value;
				
			}
			
			return substr($sql, 2);
			
		} else throw new \Exception("Invalid verb for toSQL();");
	}
	
	public static function escapeLike($sql) {
		return preg_replace('/([%_])/', '\\\\\\1', $sql);
	}

	public static function SQL($sql) {
		return array('SQL', $sql);
	}
}
