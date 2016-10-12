<?PHP

class VarsTest extends PHPUnit_Framework_TestCase {

	private static $store = array();

	public function setup() {

		Firelit\Vars::init(array('type' => Firelit\Vars::TYPE_OTHER));

		// Setting custom getter/setter functions for DB-less testing
		Firelit\Vars::$getter = function($name) {
			if (!isset(static::$store[$name])) return null;
			return static::$store[$name];
		};

		Firelit\Vars::$setter = function($name, $value) {
			static::$store[$name] = $value;
		};

	}

	public function testVars() {

		$vars = Firelit\Vars::init();

		$this->assertEquals(null, $vars->smith); // Null for not-set
		$this->assertEquals(false, isset($vars->smith));

		$vars->smith = 'peanuts';

		$this->assertEquals('peanuts', $vars->smith);
		$this->assertEquals('peanuts', static::$store['smith']); // Make sure the mocked store is working
		$this->assertEquals(true, isset($vars->smith));

		unset($vars->smith);

		$this->assertEquals(null, $vars->smith); // We're back to null for not-set
		$this->assertEquals(false, isset(static::$store['smith'])); // Make sure the mocked store is working
		$this->assertEquals(false, isset($vars->smith));

	}

}
