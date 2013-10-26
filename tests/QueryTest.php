<?PHP

class QueryTest extends PHPUnit_Framework_TestCase {
	
	protected $q, $res;
	
	public static function setUpBeforeClass() {
	
		Firelit\Query::config(array(
			'type' => 'other',
			'dsn' => 'sqlite::memory:'
		));
		
	}
	
	protected function setUp() {
		
		$this->q = new Firelit\Query();
		
	}

	public function testQuery() {
		
		
		$this->res = $this->q->query("CREATE TABLE IF NOT EXISTS `Tester` (`name` VARCHAR(10) PRIMARY KEY, `date` DATETIME, `state` BOOL)");
		$this->assertTrue($this->res);
		
	}
	
	/**
	* @depends testQuery
	*/
	public function testInsert() {
		
		$this->q->insert('Tester', array(
			'name' => 'John',
			'date' => array('SQL', "DATETIME('now')"),
			'state' => false
		));
		
		$this->assertTrue( $this->q->success() );
		$this->assertEquals( 1, $this->q->getAffected() );
		
		
	}
	
	/**
	* @depends testQuery
	*/
	public function testReplace() {
		
		$this->q->replace('Tester', array(
			'name' => 'Sally',
			'date' => array('SQL', "DATETIME('now')"),
			'state' => true
		));
		
		$this->assertTrue( $this->q->success() );
		$this->assertEquals( 1, $this->q->getAffected() );
		
		
	}
	
	/**
	* @depends testReplace
	*/
	public function testSelect() {

		$this->q->select('Tester', '*', '`name`=:name', array( ':name' => 'Sally' ), 0, 1);

		$this->assertTrue( $this->q->success() );

		$rows = $this->q->getAll();
		
		$this->assertEquals( 1, sizeof($rows) );
		
		$this->assertEquals( 'Sally', $rows[0]['name'] );
		
	}
	
	/**
	* @depends testInsert
	*/
	public function testUpdate() {
		
		$this->q->update('Tester', array( 'state' => true ), '`name`=:name', array( ':name' => 'John' ));
		
		$this->assertTrue( $this->q->success() );
		$this->assertEquals( 1, $this->q->getAffected() );
		
		// Verify that data was written
		$this->q->select('Tester', '`state`', '`name`=:name', array( ':name' => 'John' ));

		$this->assertTrue( $this->q->success() );

		$row = $this->q->getRow();
		
		$this->assertEquals( true, $row['state'] );
		
	}
	
	/**
	* @depends testUpdate
	*/
	public function testDelete() {
		
		$this->q->delete('Tester', '`name`=:name', array( ':name' => 'John' ), 1);
		
		$this->assertTrue( $this->q->success() );
		$this->assertEquals( 1, $this->q->getAffected() );
		
		// Verify that data was deleted
		$this->q->select('Tester', '*', '`name`=:name', array( ':name' => 'John' ));

		$this->assertTrue( $this->q->success() );

		$row = $this->q->getRow();
		
		$this->assertFalse( $row );
		
	}
	
	public static function tearDownAfterClass() {
		
		$q = new Firelit\Query();
		$q->query("DROP TABLE `Tester`");
		
	}
}