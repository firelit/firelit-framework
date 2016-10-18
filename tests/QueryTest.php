<?PHP

namespace Firelit;

class QueryTest extends \PHPUnit_Framework_TestCase
{

    protected $q;
    protected $res;
    protected static $pdo;

    public static function setUpBeforeClass()
    {

        Registry::set('database', array(
            'type' => 'other',
            'dsn' => 'sqlite::memory:'
        ));
    }

    protected function setUp()
    {

        $this->q = new Query();
    }

    public function testQuery()
    {


        $this->res = $this->q->query("CREATE TABLE IF NOT EXISTS `Tester` (`name` VARCHAR(10) PRIMARY KEY, `date` DATETIME, `state` BOOL)");
        $this->assertTrue($this->res);
    }

    /**
    * @depends testQuery
    */
    public function testInsert()
    {

        $this->q->insert('Tester', array(
            'name' => 'John',
            'date' => Query::SQL("DATETIME('now')"),
            'state' => false
        ));

        $this->assertTrue($this->q->success());
        $this->assertEquals(1, $this->q->getAffected());
    }

    /**
    * @depends testQuery
    */
    public function testReplace()
    {

        $this->q->replace('Tester', array(
            'name' => 'Sally',
            'date' => Query::SQL("DATETIME('now')"),
            'state' => true
        ));

        $this->assertTrue($this->q->success());
        $this->assertEquals(1, $this->q->getAffected());
    }

    /**
    * @depends testReplace
    */
    public function testSelect()
    {

        $sql = "SELECT * FROM `Tester` WHERE `name`=:name LIMIT 1";
        $this->q->query($sql, array(':name' => 'Sally'));

        $this->assertTrue($this->q->success());

        $rows = $this->q->getAll();

        $this->assertEquals(1, sizeof($rows));

        $this->assertEquals('Sally', $rows[0]['name']);

        $row = $this->q->getRow();

        $this->assertFalse($row);
    }

    /**
    * @depends testInsert
    */
    public function testUpdate()
    {

        $this->q->update('Tester', array( 'state' => true ), '`name`=:name', array( ':name' => 'John' ));

        $this->assertTrue($this->q->success());
        $this->assertEquals(1, $this->q->getAffected());

        // Verify that data was written
        $sql = "SELECT * FROM `Tester` WHERE `name`=:name LIMIT 1";
        $this->q->query($sql, array(':name' => 'John'));

        $this->assertTrue($this->q->success());

        $row = $this->q->getRow();

        $this->assertEquals(true, $row['state']);

        $row = $this->q->getRow();

        $this->assertFalse($row);
    }

    /**
    * @depends testUpdate
    */
    public function testDelete()
    {

        $sql = "DELETE FROM `Tester` WHERE `name`=:name";
        $this->q = new Query($sql, array(':name' => 'John'));

        $this->assertTrue($this->q->success());
        $this->assertEquals(1, $this->q->getAffected());

        // Verify that data was deleted
        $sql = "SELECT * FROM `Tester` WHERE `name`=:name LIMIT 1";
        $this->q->query($sql, array(':name' => 'John'));

        $this->assertTrue($this->q->success());

        $row = $this->q->getRow();

        $this->assertFalse($row);
    }

    public static function tearDownAfterClass()
    {
        try {
            // Why no work with sqlite? "Database error: 6, database table is locked"
            $q = new Query("DROP TABLE `Tester`");
        } catch (Exception $e) {
        }
    }
}
