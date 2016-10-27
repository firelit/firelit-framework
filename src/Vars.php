<?PHP

namespace Firelit;

class Vars
{

    const   TYPE_DB = 'TYPE_DB',
            TYPE_OTHER = 'TYPE_OTHER';

    public static $config = array();
    public static $setter;
    public static $getter;

    public static function init($config = array())
    {
        if (!empty($config) || empty(static::$config)) {
            $default = array(
                'type' => static::TYPE_DB,
                'table' => 'Vars',
                'col_name' => 'name',
                'col_value' => 'value'
            );

            static::$config = array_merge($config, static::$config, $default);

            if (static::$config['type'] == static::TYPE_DB) {
                static::$setter = function ($name, $value) {

                    $q = new Query();
                    $q->replace(static::$config['table'], array(
                        static::$config['col_name'] => $name,
                        static::$config['col_value'] => $value
                    ));
                };

                static::$getter = function ($name) {

                    $q = new Query();
                    $q->query("SELECT :col_value AS `value` FROM :table WHERE :col_name = :name LIMIT 1", array(
                        ':col_value' => static::$config['col_value'],
                        ':table' => static::$config['table'],
                        ':col_name' => static::$config['col_name'],
                        ':name' => $name
                    ));

                    $result = $q->getRow();
                    return (!empty($result) ? $result['value'] : null);
                };
            }
        }

        $name = get_called_class();
        return new $name();
    }

    public function __construct()
    {
        if (empty(static::$config)) {
            throw new \Exception('Not yet configured; Call Firelit\Vars::init() first');
        }
    }

    public function __set($name, $val)
    {

        $setter = static::$setter;
        return $setter($name, $val);
    }

    public function __unset($name)
    {

        $setter = static::$setter;
        return $setter($name, null);
    }

    public function __isset($name)
    {

        $getter = static::$getter;
        return ($getter($name) !== null);
    }

    public function __get($name)
    {

        $getter = static::$getter;
        return $getter($name);
    }
}
