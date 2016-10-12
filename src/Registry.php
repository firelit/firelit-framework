<?PHP

namespace Firelit;

class Registry
{

    static private $data = array();

    public static function get($name)
    {
        if (!isset(self::$data[$name])) {
            return null;
        }
        return self::$data[$name];
    }

    public static function set($name, $value)
    {
        self::$data[$name] = $value;
    }

    public function __get($name)
    {
        return self::get($name);
    }

    public function __set($name, $value)
    {
        self::set($name, $value);
    }

    public static function clear()
    {
        self::$data = array();
    }
}
