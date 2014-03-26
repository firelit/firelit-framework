<?PHP

namespace Firelit;

class DatabaseObject {
	
	// To Use: Extend this class and replace the following values
	protected static $tableName = 'ReplaceMe'; // The table name
	protected static $primaryKey = 'id'; // The primary key for table (or false if n/a)

	// Optional fields to set in extension
	protected static $colsSerialize = array(); // Columns that should be automatically php-serialized when using
	protected static $colsJson = array(); // Columns that should be automatically JSON-encoded/decoded when using

	protected $_data = array(); // The object's data
	protected $_dirty = array(); // Array of dirty properties that need saving
	protected $_new = false; // Indicates a new, unsaved object
	protected $_readOnly = false; // Cannot save or delete a read-only object
	
	protected static $query = false;

	public function __construct($data = false) {

		// If it doesn't come pre-loaded, it's new
		// (Pre-loading made possible by PDOStatement::fetchObject)
		if (!sizeof($this->_data)) $this->_new = true;

		$this->_dirty = array(); // Reset in case pre-loaded

		if (is_array($data))
			foreach ($data as $name => $value)
				$this->__set($name, $value);

	}

	public static function setQueryObject(Query $query) {
		// Used for testing
		static::$query = $query;
	}

	public function save() {

		if (!$this->_new && !sizeof($this->_dirty)) return;
		if ($this->_readOnly) throw new \Exception('Cannot save a read-only object.');

		if (static::$query) $q = static::$query;
		else $q = new Query();
		
		if ($this->_new) {

			$q->insert(static::$tableName, $this->_data);

			// If new and single primary key, set data id from autoincrement id
			if (static::$primaryKey && !is_array(static::$primaryKey))
				$this->_data[static::$primaryKey] = $q->getNewId();

		} else {

			if (!static::$primaryKey) 
				throw new \Exception('Cannot perform update without a primary key.');

			if (is_array(static::$primaryKey))
				foreach (static::$primaryKey as $aKey) {
					if (!isset($this->_data[$aKey]))
						throw new \Exception('Cannot perform update without all primary keys set.');
				}

			elseif (!isset($this->_data[static::$primaryKey]))
				throw new \Exception('Cannot perform update without primary key set.');

			$updateData = array(); 

			foreach ($this->_dirty as $key) {
				if (isset($this->_data[$key]))
					$updateData[$key] = $this->_data[$key];
			}

			if (is_array(static::$primaryKey)) {
				foreach (static::$primaryKey as $aKey) {
					if (isset($updateData[$aKey]))
						throw new \Exception('Cannot perform update on primary key (it was marked dirty).');
				}
			} elseif (isset($updateData[static::$primaryKey])) {
				throw new \Exception('Cannot perform update on primary key (it was marked dirty).');
			}

			list($whereSql, $whereBinder) = $this->getWhere($this->_data);

			$q->update(static::$tableName, $updateData, $whereSql, $whereBinder);
			
		}
		
		$this->_new = false;
		$this->_dirty = array();

	}

	static protected function getWhere($valueArray) {

		$whereBinder = array();
		
		if (is_array(static::$primaryKey)) {

			$whereSql = "WHERE";

			foreach (static::$primaryKey as $aKey) {
				$binderName = ':primary_key_'.mt_rand(0,1000000);
				$whereSql .= " `". $aKey ."`=". $binderName ." AND";
				$whereBinder[$binderName] = $valueArray[$aKey];
			}

			$whereSql = substr($whereSql, 0, -3). "LIMIT 1";

		} else {

			$binderName = ':primary_key_'.mt_rand(0,1000000);
			$whereSql = "WHERE `". static::$primaryKey ."`=". $binderName ." LIMIT 1";
			$whereBinder[$binderName] = $valueArray[static::$primaryKey];

		}

		return array($whereSql, $whereBinder);

	}

	public function setNew() {
		$this->_new = true;
		$this->_dirty = array();
	}

	public function setNotNew() {
		$this->_new = false;
	}

	public function setReadOnly() {
		$this->_new = false;
		$this->_dirty = array();
		$this->_readOnly = true;
	}

	public function isNew() {
		return $this->_new;
	}

	public function getDirty() {
		return $this->_dirty;
	}

	public function __get($var) {

		if (isset($this->_data[$var])) $val = $this->_data[$var];
		else return null;

		if (in_array($var, static::$colsSerialize)) {
			$val = unserialize($val);
		} elseif (in_array($var, static::$colsJson)) {
			$val = json_decode($val, true);
		}

		return $val;

	}

	public function __set($var, $val) {

		if (!is_null($val) && in_array($var, static::$colsSerialize)) {
			$val = serialize($val);
		} elseif (!is_null($val) && in_array($var, static::$colsJson)) {
			$val = json_encode($val);
		}

		if (isset($this->_data[$var]) && ($this->_data[$var] === $val)) return;

		$this->_data[$var] = $val;

		if (!$this->_new && !in_array($var, $this->_dirty))
			$this->_dirty[] = $var;

	}

	public function __clone() {
		
		if (static::$primaryKey)
			unset($this->_data[static::$primaryKey]);
		
		$this->_new = true;
		$this->_dirty = array();

	}

	public function delete() {

		if ($this->_readOnly) throw new \Exception('Cannot delete a read-only object.');

		if (!static::$primaryKey) 
			throw new \Exception('Cannot perform delete without a primary key.');

		$this->_dirty = array();

		if ($this->_new) return;
		
		list($whereSql, $whereBinder) = $this->getWhere($this->_data);

		$sql = "DELETE FROM `". static::$tableName ."` ". $whereSql;

		if (static::$query) $q = static::$query;
		else $q = new Query();

		$q->query($sql, $whereBinder);

		$this->_data = array();

	}

	public static function create($data) {

		$class = get_called_class();

		$do = new $class($data);
		$do->save();

		return $do;

	}

	public static function find($searchValue) {

		if (!static::$primaryKey) 
			throw new \Exception('Cannot perform find without a primary key.');

		if (is_array($searchValue) != is_array(static::$primaryKey))
			throw new \Exception('If primary key is an array, must search by array and vice versa.');

		if (is_array($searchValue) && (sizeof($searchValue) != sizeof(static::$primaryKey)))
			throw new \Exception('Number of elements in search array must match primary key array.');

		if (!is_array($searchValue))
			$searchValue = array(static::$primaryKey => $searchValue);

		list($whereSql, $whereBinder) = static::getWhere($searchValue);

		$sql = "SELECT * FROM `". static::$tableName ."` ". $whereSql;

		if (static::$query) $q = static::$query;
		else $q = new Query();

		$q->query($sql, $whereBinder);

		return $q->getObject(get_called_class());

	}

}