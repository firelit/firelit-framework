<?PHP

class ViewTest extends PHPUnit_Framework_TestCase {
	
	public function testLayoutTemplate() {
		$view = new Firelit\View();
		$view->setLayout('Test');
		$view->setTemplate('Temp');

		$this->assertEquals('Test', $view->layout);
		$this->assertEquals('Temp', $view->template);
	}

}