<?PHP

class DatabaseObjectTest extends PHPUnit_Framework_TestCase {

	public function testSaveNew() {

		$queryStub = $this->getMock('Firelit\Query', array('insert', 'getNewId'));

		$queryStub->expects($this->once())
				->method('insert');

		$queryStub->expects($this->once())
				->method('getNewId')
				->will( $this->returnValue( $newId = mt_rand(100,1000) ) );

		$do = new Firelit\DatabaseObject(array(
			'test' => '123'
		));

		$do->setQueryObject($queryStub);

		$this->assertTrue($do->isNew());

		$do->save();

		$this->assertEquals($do->id, $newId);

	}

	public function testNewFlag() {

		$queryStub = $this->getMock('Firelit\Query', array('update'));

		$queryStub->expects($this->once())
				->method('update');

		$do = new Firelit\DatabaseObject(array(
			'id' => 3,
			'test' => '123'
		));

		$do->setQueryObject($queryStub);

		$do->setNotNew();

		$this->assertFalse($do->isNew());

		$do->test = '321';

		$do->save();

	}

	public function testDirty() {

		$do = new Firelit\DatabaseObject(array(
			'name' => 'John Doe',
			'age' => 28,
			'sex' => 'M'
		));

		$do->setNotNew();

		$this->assertEquals($do->age, 28);

		// Change 1st parameter
		$do->age = 32;

		$this->assertEquals($do->age, 32);

		$dirty = $do->getDirty();

		$this->assertEquals(sizeof($dirty), 1);
		$this->assertEquals($dirty[0], 'age');

		// Change a second parameter
		$do->sex = 'F';

		$dirty = $do->getDirty();

		$this->assertEquals(sizeof($dirty), 2);
		$this->assertEquals($dirty[1], 'sex');

	}

}