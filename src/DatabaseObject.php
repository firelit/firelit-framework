<?PHP

namespace Firelit;

class DatabaseObject
{

    // To Use: Extend this class and replace the following values
    protected static $tableName = 'ReplaceMe'; // The table name
    protected static $primaryKey = 'id'; // The primary key for table (or false if n/a)

    // Optional fields to set in extension
    protected static $colsSerialize = array(); // Columns that should be automatically php-serialized when using
    protected static $colsJson = array(); // Columns that should be automatically JSON-encoded/decoded when using
    protected static $colsDateTime = array(); // Columns that should be a DateTime object when loaded from DB

    protected $_data = array(); // The object's data
    protected $_dirty = array(); // Array of dirty properties that need saving
    protected $_new = false; // Indicates a new, unsaved object
    protected $_readOnly = false; // Cannot save or delete a read-only object

    protected static $query = false;

    protected $constructed = false;

    /* Set the databases default TZ (for creating php datetime objects from mysql datetime columns) */
    static public $defaultTz = false; // Should be a DateTimeZone object, defaults to UTC; should match Firelit\Query

    public function __construct($data = false)
    {

        $this->constructed = true;

        // If it doesn't come pre-loaded, it's new
        // (Pre-loading made possible by PDOStatement::fetchObject)
        if (!sizeof($this->_data)) {
            $this->_new = true;
        }

        if (is_array(static::$colsDateTime)) {
            if (!static::$defaultTz) {
                static::$defaultTz = new \DateTimeZone('UTC');
            }

            foreach (static::$colsDateTime as $aCol) {
                // Change all pre-loaded date/time data into DateTime object
                if (!empty($this->_data[$aCol]) && !is_object($this->_data[$aCol])) {
                    $this->_data[$aCol] = new \DateTime($this->_data[$aCol], static::$defaultTz);
                }
            }
        }

        $this->_dirty = array(); // Reset in case pre-loaded

        if (is_array($data)) {
            foreach ($data as $name => $value) {
                $this->__set($name, $value);
            }
        }
    }

    public static function setQueryObject(Query $query)
    {
        // Used for testing
        static::$query = $query;
    }

    public function save()
    {

        if (!$this->_new && !sizeof($this->_dirty)) {
            return;
        }
        if ($this->_readOnly) {
            throw new \Exception('Cannot save a read-only object.');
        }

        $saveData = $this->_data;

        foreach ($saveData as $var => $val) {
            // JIT encoding: Serialize/JSON-encode just before saving
            if (!is_null($val) && in_array($var, static::$colsSerialize)) {
                $saveData[$var] = serialize($val);
            } elseif (!is_null($val) && in_array($var, static::$colsJson)) {
                $saveData[$var] = json_encode($val);
            }
        }

        if (static::$query) {
            $q = static::$query;
        } else {
            $q = new Query();
        }

        if ($this->_new) {
            $q->insert(static::$tableName, $saveData);

            // If new and single primary key, set data id from autoincrement id
            if (static::$primaryKey && !is_array(static::$primaryKey)) {
                $this->_data[static::$primaryKey] = $q->getNewId();
            }
        } else {
            if (!static::$primaryKey) {
                throw new \Exception('Cannot perform update without a primary key.');
            }

            if (is_array(static::$primaryKey)) {
                foreach (static::$primaryKey as $aKey) {
                    if (!isset($saveData[$aKey])) {
                        throw new \Exception('Cannot perform update without all primary keys set.');
                    }
                }
            } elseif (!isset($saveData[static::$primaryKey])) {
                throw new \Exception('Cannot perform update without primary key set.');
            }

            $updateData = array();

            foreach ($this->_dirty as $key) {
                $updateData[$key] = $saveData[$key];
            }

            $wheres = array();

            if (is_array(static::$primaryKey)) {
                foreach (static::$primaryKey as $aKey) {
                    if (isset($updateData[$aKey])) {
                        throw new \Exception('Cannot perform update on primary key (it was marked dirty).');
                    }

                    $wheres[] = $saveData[$aKey];
                }
            } else {
                if (isset($updateData[static::$primaryKey])) {
                    throw new \Exception('Cannot perform update on primary key (it was marked dirty).');
                }

                $wheres = $saveData[static::$primaryKey];
            }

            list($whereSql, $whereBinder) = $this->getWhere($wheres);

            $q->update(static::$tableName, $updateData, $whereSql, $whereBinder);
        }

        $this->_new = false;
        $this->_dirty = array();
    }

    protected static function getWhere($valueArray)
    {

        $whereBinder = array();

        if (is_array($valueArray)) {
            $whereSql = "WHERE";

            // If not associative array, set the primary keys as the column names
            $hasStringKeys = count(array_filter(array_keys($valueArray), 'is_string')) > 0;
            if (!$hasStringKeys) {
                $valueArray = array_merge(static::$primaryKey, $valueArray);
            }

            foreach ($valueArray as $aKey => $aVal) {
                $binderName = ':binder_'.mt_rand(0, 1000000);
                $whereSql .= " `". $aKey ."`=". $binderName ." AND";
                $whereBinder[$binderName] = $aVal;
            }

            $whereSql = substr($whereSql, 0, -3). "LIMIT 1";
        } else {
            if (is_array(static::$primaryKey)) {
                throw new \Exception('The valueArray must be an array if the primary key is an array.');
            }

            $binderName = ':binder_'.mt_rand(0, 1000000);
            $whereSql = "WHERE `". static::$primaryKey ."`=". $binderName ." LIMIT 1";
            $whereBinder[$binderName] = $valueArray;
        }

        return array($whereSql, $whereBinder);
    }

    public function setNew()
    {
        $this->_new = true;
        $this->_dirty = array();
    }

    public function setNotNew()
    {
        $this->_new = false;
    }

    public function setReadOnly()
    {
        $this->_new = false;
        $this->_dirty = array();
        $this->_readOnly = true;
    }

    public function isNew()
    {
        return $this->_new;
    }

    public function getDirty()
    {
        return $this->_dirty;
    }

    public function __get($var)
    {

        if (isset($this->_data[$var])) {
            return $this->_data[$var];
        }

        return null;
    }

    public function __isset($var)
    {

        return isset($this->_data[$var]);
    }

    public function __set($var, $val)
    {

        // If pre-construct loading
        if (!$this->constructed) {
            // Take out of deep-freeze (as stored in DB)
            if (!is_null($val) && in_array($var, static::$colsSerialize)) {
                $val = unserialize($val);
            } elseif (!is_null($val) && in_array($var, static::$colsJson)) {
                $val = json_decode($val, true);
            }

            $this->_data[$var] = $val;
            return;
        }

        if (isset($this->_data[$var]) && ($this->_data[$var] === $val)) {
            return;
        }

        $this->_data[$var] = $val;

        if (!$this->_new && !in_array($var, $this->_dirty)) {
            $this->_dirty[] = $var;
        }
    }

    public function __clone()
    {

        if (static::$primaryKey) {
            unset($this->_data[static::$primaryKey]);
        }

        $this->_new = true;
        $this->_dirty = array();
    }

    public function delete()
    {

        if ($this->_readOnly) {
            throw new \Exception('Cannot delete a read-only object.');
        }

        if (!static::$primaryKey) {
            throw new \Exception('Cannot perform delete without a primary key.');
        }

        $this->_dirty = array();

        if ($this->_new) {
            return;
        }

        list($whereSql, $whereBinder) = $this->getWhere($this->_data);

        $sql = "DELETE FROM `". static::$tableName ."` ". $whereSql;

        if (static::$query) {
            $q = static::$query;
        } else {
            $q = new Query();
        }

        $q->query($sql, $whereBinder);

        $this->_data = array();
    }

    public static function create($data)
    {

        $class = get_called_class();

        $do = new $class($data);
        $do->save();

        return $do;
    }

    public static function find($searchValue)
    {

        if (!static::$primaryKey) {
            throw new \Exception('Cannot perform find without a primary key.');
        }

        return static::findBy(static::$primaryKey, $searchValue);
    }

    public static function findBy($column, $searchValue)
    {

        if (is_array($searchValue) != is_array($column)) {
            throw new \Exception('If column is an array, must search by array and vice versa.');
        }

        if (is_array($searchValue) && (sizeof($searchValue) != sizeof($column))) {
            throw new \Exception('Number of elements in search array must match column array.');
        }

        if (!is_array($searchValue)) {
            $searchValue = array($column => $searchValue);
        } else {
            $searchValue = array_combine($column, $searchValue);
        }

        list($whereSql, $whereBinder) = static::getWhere($searchValue);

        $sql = "SELECT * FROM `". static::$tableName ."` ". $whereSql;

        if (static::$query) {
            $q = static::$query;
        } else {
            $q = new Query();
        }

        $q->query($sql, $whereBinder);

        return $q->getObject(get_called_class());
    }
}
