<?PHP

class ExpectedException extends Exception { }
class UnexpectedException extends Exception { }

class RouterTest extends PHPUnit_Framework_TestCase {
	
	public function testAdd() {

		$this->setExpectedException('ExpectedException');

		$r = new Firelit\Router(new Firelit\Request());

		$r->add('POST', '/.*/', function() {
			// Bad
			throw new UnexpectedException();
		});

		$r->add('*', false, function() {
			// Good!
			throw new ExpectedException();
		});

		Firelit\Registry::clear();
		unset($r);
	}

	public function testDefault() {

		$this->setExpectedException('ExpectedException');

		$r = new Firelit\Router(new Firelit\Request());

		$r->defaultRoute(function() {
			// Good!
			throw new ExpectedException();
		});

		$r->add('POST', '/.*/', function() {
			// Bad
			throw new UnexpectedException();
		});

		$r->add('*', '/.*/', function() {
			// Bad
			throw new UnexpectedException();
		});

		Firelit\Registry::clear();
		unset($r);

	}

	public function testError() {

		$this->setExpectedException('ExpectedException');

		$r = new Firelit\Router(new Firelit\Request());

		$r->errorRoute(500, function() {
			// Good!
			throw new ExpectedException();
		});
		
		$r->errorRoute(404, function() {
			// Bad
			throw new UnexpectedException();
		});

		$r->add('POST', '/.*/', function() {
			// Bad
			throw new UnexpectedException();
		});

		$r->add('*', false, function() {
			// Should take this route
			throw new Firelit\RouteToError(500);
		});

		Firelit\Registry::clear();
		unset($r);
		
	}

}
		