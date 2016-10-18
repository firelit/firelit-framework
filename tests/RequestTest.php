<?PHP

namespace Firelit;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {

        $_POST = $orig = array(
            'test' => true,
            'tester' => array(
                0 => 'value0',
                1 => 'value1'
            )
        );

        $_GET = array();
        $_COOKIE = array();

        $sr = new Request();

        $this->assertEquals($orig, $sr->post, '$_POST should be copied into internal property.');
    }

    public function testUnset()
    {

        $_POST = $orig = array(
            'test' => true,
            'tester' => array(
                0 => 'value0',
                1 => 'value1'
            )
        );

        $_GET = array();
        $_COOKIE = array();

        $sr = new Request(function (&$val) {
            Strings::cleanUTF8($val);
        });

        $this->assertEquals($orig, $sr->post, '$_POST was not copied by Request object.');
        $this->assertNull($_POST, '$_POST was not set to null by Request object.');
    }

    public function testJson()
    {

        $data = array(
            'test' => true,
            'test_array' => array(
                0 => 'value22',
                1 => 'value33'
            )
        );

        Request::$dataInput = json_encode($data);
        Request::$methodInput = 'POST';

        $sut = new Request(false, 'json');

        $this->assertEquals($data, $sut->post, 'Post JSON data was not correctly made available.');

        Request::$dataInput = '{"json":"invalid",:}';
        Request::$methodInput = 'PUT';

        try {
            $sut = new Request(false, 'json');

            $this->assertTrue(false, 'Invalid JSON data was not caught.');
        } catch (InvalidJsonException $e) {
            $this->assertTrue(true, 'Invalid JSON data was not caught.');
        }

        Request::$dataInput = null;
        Request::$methodInput = null;
    }

    public function testFilter()
    {

        $_POST = array();

        $_GET = $orig = array(
            'nested' => array(
                'deep' => array(
                    'deeper' => 'bad',
                    'other' => 'good'
                )
            ),
            'shallow' => 'bad'
        );

        $_COOKIE = array();

        $sr = new Request(function (&$val) {
            if ($val == 'bad') {
                $val = 'clean';
            }
        });

        $this->assertNotEquals($orig, $sr->get, '$_GET value remains unchanged.');
        $this->assertEquals('clean', $sr->get['nested']['deep']['deeper'], 'Deep array value not cleaned.');
        $this->assertEquals('good', $sr->get['nested']['deep']['other'], 'Deep array value mistakenly cleaned.');
        $this->assertEquals('clean', $sr->get['shallow'], 'Shallow array value not cleaned.');
    }

    public function testCliDetection()
    {

        $sr = new Request();

        $this->assertEquals('CLI', $sr->method);
    }
}
