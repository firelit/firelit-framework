<?PHP

namespace Firelit;

// @codingStandardsIgnoreStart
class TestObject extends DatabaseObject
{

    // To Use: Extend this class and replace the following values
    protected static $tableName = 'TableName'; // The table name
    protected static $primaryKey = 'id'; // The primary key for table (or false if n/a)

    // Optional fields to set in extension
    protected static $colsSerialize = array('serialize'); // Columns that should be automatically php-serialized when using
    protected static $colsJson = array('jsonize'); // Columns that should be automatically JSON-encoded/decoded when using
}
// @codingStandardsIgnoreEnd

// @codingStandardsIgnoreLine (ignoring multiple classes in a file)
class DatabaseObjectTest extends \PHPUnit_Framework_TestCase
{

    public function testSaveNew()
    {

        $queryStub = $this->createMock('Firelit\Query', array('insert', 'getNewId'));

        $queryStub->expects($this->once())
                ->method('insert');

        $queryStub->expects($this->once())
                ->method('getNewId')
                ->will($this->returnValue($newId = mt_rand(100, 1000)));

        $do = new DatabaseObject(array(
            'test' => '123'
        ));

        $do->setQueryObject($queryStub);

        $this->assertTrue($do->isNew());

        $do->save();

        $this->assertEquals($newId, $do->id);
    }

    public function testNewFlag()
    {

        $queryStub = $this->createMock('Firelit\Query', array('update'));

        $queryStub->expects($this->once())
                ->method('update');

        $do = new DatabaseObject(array(
            'id' => 3,
            'test' => '123'
        ));

        $do->setQueryObject($queryStub);

        $do->setNotNew();

        $this->assertFalse($do->isNew());

        $do->test = '321';

        $do->save();
    }

    public function testDirty()
    {

        $do = new DatabaseObject(array(
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

    public function testSerialization()
    {

        $to = new TestObject(array(
            'test' => '123',
            'serialize' => $class = new \stdClass(),
            'jsonize' => array(4, 5, 6)
        ));

        $this->assertEquals($to->test, '123');
        $this->assertSame($to->serialize, $class);
        $this->assertEquals($to->jsonize, array(4, 5, 6));
        $this->assertEquals($to->jsonize[2], 6);
    }

    public function testFindBy()
    {

        $queryStub = $this->createMock('Firelit\Query', array('query', 'getObject'));

        $queryStub->expects($this->once())
                ->method('query')
                ->with(
                    $this->stringContains('SELECT * FROM `TableName` WHERE `email`'),
                    $this->callback(function ($subject) {
                            // Make sure the 2nd param is an array
                        if (!is_array($subject)) {
                            return false;
                        }
                        if (sizeof($subject) != 1) {
                            return false;
                        }
                            // Make sure the primary key value is in the 2nd param
                            $key = key($subject);
                        if ($subject[$key] != 'test@test.com') {
                            return false;
                        }
                            // All good
                            return true;
                    })
                );

        $queryStub->expects($this->once())
                ->method('getObject')
                ->with($this->equalTo('Firelit\TestObject'))
                ->will($this->returnValue(new TestObject()));

        TestObject::setQueryObject($queryStub);

        $to = TestObject::findBy('email', 'test@test.com');

        $this->assertTrue($to instanceof TestObject);

        // Test with arrays:

        $queryStub = $this->createMock('Firelit\Query', array('query', 'getObject'));

        $queryStub->expects($this->once())
                ->method('query')
                ->with(
                    $this->stringContains('SELECT * FROM `TableName` WHERE'),
                    $this->callback(function ($subject) {
                            // Make sure the 2nd param is an array
                        if (!is_array($subject)) {
                            return false;
                        }
                        if (sizeof($subject) != 2) {
                            return false;
                        }
                            // Make sure the primary key value is in the 2nd param
                            $key = key($subject);
                        if ($subject[$key] != 'john') {
                            return false;
                        }
                            // Make sure the primary key value is in the 2nd param
                            next($subject);
                            $key = key($subject);
                        if ($subject[$key] != 'test@test.com') {
                            return false;
                        }
                            // All good
                            return true;
                    })
                );

        $queryStub->expects($this->once())
                ->method('getObject')
                ->with($this->equalTo('Firelit\TestObject'))
                ->will($this->returnValue(new TestObject()));

        TestObject::setQueryObject($queryStub);

        $to = TestObject::findBy(array('name', 'email'), array('john', 'test@test.com'));

        $this->assertTrue($to instanceof TestObject);

        try {
            $to = TestObject::findBy(array('test', 'test2'), 'nope');
            $exception = false;
        } catch (\Exception $e) {
            $exception = true;
        }

        $this->assertTrue($exception, 'Exception expected due to searching multiple columns with singular value');
    }

    public function testFind()
    {

        $queryStub = $this->createMock('Firelit\Query', array('query', 'getObject'));

        $queryStub->expects($this->once())
                ->method('query')
                ->with(
                    $this->stringContains('SELECT * FROM `TableName` WHERE `id`'),
                    $this->callback(function ($subject) {
                            // Make sure the 2nd param is an array
                        if (!is_array($subject)) {
                            return false;
                        }
                            // Make sure the primary key value is in the 2nd param
                            $key = key($subject);
                        if ($subject[$key] != 100) {
                            return false;
                        }
                            // All good
                            return true;
                    })
                );

        $queryStub->expects($this->once())
                ->method('getObject')
                ->with($this->equalTo('Firelit\TestObject'))
                ->will($this->returnValue(new TestObject()));

        TestObject::setQueryObject($queryStub);

        $to = TestObject::find(100);

        $this->assertTrue($to instanceof TestObject);

        try {
            $to = TestObject::find(array(1, 2));
            $exception = false;
        } catch (\Exception $e) {
            $exception = true;
        }

        $this->assertTrue($exception, 'Exception expected due to searching array with singular PK');
    }
}
