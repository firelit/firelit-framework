<?PHP

namespace Firelit;

class CacheTest extends \PHPUnit_Framework_TestCase
{

    private $session;
    private $store;
    private $testVal;

    protected function setUp()
    {

        Cache::config(array(
            'memecached' => array(
                'enabled' => false
            )
        ));
    }

    public function testGetNoClosure()
    {

        $val = Cache::get('index');

        $this->assertNull($val);
    }

    /**
    * @depends testGetNoClosure
    */
    public function testGetCacheMiss()
    {

        $val = Cache::get('index', function () {

            return 12345;
        });

        $this->assertEquals(12345, $val);
        $this->assertFalse(Cache::$cacheHit);
    }

    /**
    * @depends testGetCacheMiss
    */
    public function testVariableSeperation()
    {

        $val = Cache::get('index2', function () {

            return 100;
        });

        $this->assertEquals(100, $val);
        $this->assertFalse(Cache::$cacheHit);
    }

    /**
    * @depends testGetCacheMiss
    */
    public function testGetCacheHit()
    {

        $val = Cache::get('index', function () {

            return -1;
        });

        $this->assertEquals(12345, $val);
        $this->assertTrue(Cache::$cacheHit);
    }

    /**
    * @depends testGetCacheHit
    */
    public function testGetCacheSet()
    {

        $val = Cache::get('index');

        $this->assertEquals(12345, $val);

        Cache::set('index', 123456);

        $val = Cache::get('index');

        $this->assertEquals(123456, $val);

        Cache::set('index', null);

        $val = Cache::get('index');

        $this->assertNull($val);
    }
}
