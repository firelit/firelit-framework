<?PHP

class RequestTest extends PHPUnit_Framework_TestCase {

	public function testConstructor() {

		$_POST = $orig = array(
			'test' => true,
			'tester' => array(
				0 => 'value0',
				1 => 'value1'
			)
		);

		$_GET = array();
		$_COOKIE = array();

		$sr = new Firelit\Request();

		$this->assertEquals( $orig, $sr->post, '$_POST should be copied into internal property.' );

	}

	public function testUnset() {

		$_POST = $orig = array(
			'test' => true,
			'tester' => array(
				0 => 'value0',
				1 => 'value1'
			)
		);

		$_GET = array();
		$_COOKIE = array();

		$sr = new Firelit\Request(function(&$val) {
			Firelit\Strings::cleanUTF8($val);
		});

		$this->assertEquals( $orig, $sr->post, '$_POST was not copied by Request object.' );
		$this->assertNull( $_POST, '$_POST was not set to null by Request object.' );

	}

	public function testJson() {

		$data = array(
			'test' => true,
			'test_array' => array(
				0 => 'value22',
				1 => 'value33'
			)
		);

		Firelit\Request::$dataInput = json_encode($data);
		Firelit\Request::$methodInput = 'POST';

		$sut = new Firelit\Request(false, 'json');

		$this->assertEquals( $data, $sut->post, 'Post JSON data was not correctly made available.' );

		Firelit\Request::$dataInput = '{"json":"invalid",:}';
		Firelit\Request::$methodInput = 'PUT';

		try {
			$sut = new Firelit\Request(false, 'json');

			$this->assertTrue( false, 'Invalid JSON data was not caught.' );

		} catch (Exception $e) {
			$this->assertTrue( (bool) preg_match('/JSON/', $e->getMessage()), 'Invalid JSON data was not caught.' );
		}

		Firelit\Request::$dataInput = null;
		Firelit\Request::$methodInput = null;

	}

	public function testFilter() {

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

		$sr = new Firelit\Request(function(&$val) {
			if ($val == 'bad') $val = 'clean';
		});

		$this->assertNotEquals( $orig, $sr->get, '$_GET value remains unchanged.' );
		$this->assertEquals( 'clean', $sr->get['nested']['deep']['deeper'], 'Deep array value not cleaned.' );
		$this->assertEquals( 'good', $sr->get['nested']['deep']['other'], 'Deep array value mistakenly cleaned.' );
		$this->assertEquals( 'clean', $sr->get['shallow'], 'Shallow array value not cleaned.' );

	}

	public function testCliDetection() {

		$sr = new Firelit\Request();

		$this->assertEquals('CLI', $sr->method);

	}
}