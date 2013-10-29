<?PHP

class ResponseTest extends PHPUnit_Framework_TestCase {
	
	public function testBufferClear() {

		ob_start();

		$r = Firelit\Response::init();

		echo 'Should be cleared';

		$r->clearBuffer();
		$r->endBuffer();

		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('', $output);

	}

	public function testBufferFlush() {

		ob_start();

		$r = Firelit\Response::init();
		
		echo 'Should not be cleared';

		$r->endBuffer();

		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('Should not be cleared', $output);

	}

}