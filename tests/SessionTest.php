<?PHP

class SessionTest extends PHPUnit_Framework_TestCase {
	
	public function testOpenRead() {
	
		$this->store = $this->getMock('Firelit\DatabaseSessionHandler', array('open', 'close', 'read', 'write', 'destroy', 'gc'));
		
		$varName = 'name'. mt_rand(0, 1000);
		$varValue = 'value'. mt_rand(0, 1000);
		$sessionId = '0hIWWN5z1tiaIhrAOC2YpjYSNbqRIE+D3Z69M/Q5eOQ=LzBpo7'; // Of the valid format
		
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

	}
	
}