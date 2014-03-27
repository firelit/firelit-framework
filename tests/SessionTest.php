<?PHP

class SessionTest extends PHPUnit_Framework_TestCase {
	
	public function testOpenReadSetSession() {
	
		$this->store = $this->getMock('Firelit\DatabaseSessionHandler', array('open', 'close', 'read', 'write', 'destroy', 'gc'));
		
		$varName = 'name'. mt_rand(0, 1000);
		$varValue = 'value'. mt_rand(0, 1000);
		
		$sessionId = Firelit\Session::generateSessionId(); // Of the valid format
		
		$this->assertRegExp('/^[A-Za-z0-9\+\/=]{50}$/', $sessionId);

		$this->assertTrue(Firelit\Session::sessionIdIsValid($sessionId));

		$this->store->expects($this->once())
					->method('open')
					->will($this->returnValue(true));

		$this->store->expects($this->once())
					->method('read')
					->with($this->equalTo($sessionId))
					->will($this->returnValue(false));
                 
		$session = new Firelit\Session($this->store, $sessionId);
		
		$session->$varName = $varValue;

		$this->assertEquals($varValue, $session->$varName);

		$session->destroy();

	}
	
	public function testOpenReadCookieRead() {
	
		$this->store = $this->getMock('Firelit\DatabaseSessionHandler', array('open', 'close', 'read', 'write', 'destroy', 'gc'));
		
		$varName = 'name'. mt_rand(0, 1000);
		$varValue = 'value'. mt_rand(0, 1000);

		$sessionId = Firelit\Session::generateSessionId(); // Of the valid format
		$_COOKIE[Firelit\Session::$config['cookie']['name']] = $sessionId;

		$this->store->expects($this->once())
					->method('open')
					->will($this->returnValue(true));

		$this->store->expects($this->once())
					->method('read')
					->with($this->equalTo($sessionId))
					->will($this->returnValue(false));
                 
		$this->store->expects($this->once())
					->method('write')
					->with(	$this->equalTo($sessionId),
							$this->stringContains($varValue))
					->will($this->returnValue(true));

		$session = new Firelit\Session($this->store);
		
		$session->$varName = $varValue;

		$this->assertEquals($varValue, $session->$varName);

		$this->assertEquals($sessionId, $session->getSessionId());

	}

}