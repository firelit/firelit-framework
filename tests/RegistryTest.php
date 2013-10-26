<?PHP

class RegistryTest extends PHPUnit_Framework_TestCase {
	
	private $encrypted, $password, $iv, $unencrypted;
	
	protected function setUp() {
		
		Firelit\Registry::set('test', 'value');

	}
	
	public function testGet() {

		Firelit\Registry::set('boolean', true);

		$this->assertEquals('value', Firelit\Registry::get('test'));

		$this->assertTrue(Firelit\Registry::get('boolean'));

	}

	public function testSet() {

		$r = new Firelit\Registry();

		$r->set('Peter', 'Piper');
		$r->set('Red', 'Herring');

		$this->assertEquals('Piper', $r->get('Peter'));

	}

}