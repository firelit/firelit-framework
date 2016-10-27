<?PHP

namespace Firelit;

class RegistryTest extends \PHPUnit_Framework_TestCase
{

    private $encrypted;
    private $password;
    private $iv;
    private $unencrypted;

    protected function setUp()
    {

        Registry::set('test', 'value');
    }

    public function testGet()
    {

        Registry::set('boolean', true);

        $this->assertEquals('value', Registry::get('test'));

        $this->assertTrue(Registry::get('boolean'));
    }

    public function testSet()
    {

        $r = new Registry();

        $r->set('Peter', 'Piper');
        $r->set('Red', 'Herring');

        $this->assertEquals('Piper', $r->get('Peter'));
    }
}
