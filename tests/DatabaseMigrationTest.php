<?PHP

namespace Firelit;

// @codingStandardsIgnoreStart
class DatabaseMigrationMock extends DatabaseMigration
{

    static protected $version = '5.0.5';

    public function up()
    {
    }

    public function down()
    {
    }
}
// @codingStandardsIgnoreEnd

// @codingStandardsIgnoreLine (ignoring multiple classes in a file)
class DatabaseMigrationTest extends \PHPUnit_Framework_TestCase
{

    public function testGetVersion()
    {

        $mock = new DatabaseMigrationMock();

        $this->assertEquals('5.0.5', $mock->getVersion());
    }

    public function testCheckVersionUp()
    {

        $mock = new DatabaseMigrationMock();

        $this->assertTrue($mock->checkVersionUp('4.15.15'));
        $this->assertTrue($mock->checkVersionUp('5.0'));
        $this->assertTrue($mock->checkVersionUp('5.0.4'));

        $this->assertFalse($mock->checkVersionUp('5.0.5'));

        $this->assertFalse($mock->checkVersionUp('5.0.6'));
        $this->assertFalse($mock->checkVersionUp('5.1'));
        $this->assertFalse($mock->checkVersionUp('6.0'));
    }

    public function testCheckVersionUpLimit()
    {

        $mock = new DatabaseMigrationMock();

        $this->assertTrue($mock->checkVersionUp('5.0.4', '5.0.5'));
        $this->assertTrue($mock->checkVersionUp('5.0.3', '5.0.6'));
        $this->assertFalse($mock->checkVersionUp('5.0.4', '5.0.4'));
        $this->assertFalse($mock->checkVersionUp('5.0.3', '5.0.4'));

        $this->assertFalse($mock->checkVersionUp('5.0.5', '5.0.2'));
        $this->assertFalse($mock->checkVersionUp('5.0.5', '5.0.6'));

        $this->assertFalse($mock->checkVersionUp('5.0.6', '5.0.2'));
        $this->assertFalse($mock->checkVersionUp('5.0.6', '5.0.6'));
    }

    public function testCheckVersionDown()
    {

        $mock = new DatabaseMigrationMock();

        $this->assertFalse($mock->checkVersionDown('5.0', '4.15.1'), 'Current version is below mock migration version');
        $this->assertFalse($mock->checkVersionDown('5.0', '5.0.5'), 'Current version is below mock migration version');
        $this->assertFalse($mock->checkVersionDown('5.0', '5.2'), 'Current version is below mock migration version');

        $this->assertTrue($mock->checkVersionDown('5.0.5', '4.15.1'), 'Current version matches, target is below');
        $this->assertFalse($mock->checkVersionDown('5.0.5', '5.0.5'), 'Current version matches, but target is same');
        $this->assertFalse($mock->checkVersionDown('5.0.5', '5.1'), 'Current version matches, but target is above');

        $this->assertTrue($mock->checkVersionDown('5.1', '4.16'), 'Current version ahead of mock and target is below');
        $this->assertFalse($mock->checkVersionDown('5.1', '5.0.5'), 'Current version ahead of mock but target is same');
        $this->assertFalse($mock->checkVersionDown('5.1', '5.1.1'), 'Current version ahead of mock but target is above');
    }
}
