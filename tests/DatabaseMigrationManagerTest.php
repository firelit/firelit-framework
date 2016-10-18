<?php

namespace Firelit;

// @codingStandardsIgnoreStart
class DatabaseMigrationMockTemplate extends DatabaseMigration
{
    public static $upCount = 0, $downCount = 0;

    public function up()
    {
        static::$upCount++;
    }

    public function down()
    {
        static::$downCount++;
    }
}

class DatabaseMigrationMock_1 extends DatabaseMigrationMockTemplate
{
    public static $upCount = 0, $downCount = 0;
    static public $version = '4.5';
}

class DatabaseMigrationMock_2 extends DatabaseMigrationMockTemplate
{
    public static $upCount = 0, $downCount = 0;
    static public $version = '5.0';
}

class DatabaseMigrationMock_3 extends DatabaseMigrationMockTemplate
{
    public static $upCount = 0, $downCount = 0;
    static public $version = '5.0.1';
}
// @codingStandardsIgnoreEnd

// @codingStandardsIgnoreLine (ignoring multiple classes in a file)
class DatabaseMigrationManagerTest extends \PHPUnit_Framework_TestCase
{

    public function testSimple()
    {

        $mock = new DatabaseMigrationMock_1();

        $manager = new DatabaseMigrationManager('4.4.12');

        $manager->submitMigration($mock);

        $this->assertEquals(1, $manager->count());
    }

    public function testUpMigration()
    {

        $mock1 = new DatabaseMigrationMock_1();
        $mock2 = new DatabaseMigrationMock_2();
        $mock3 = new DatabaseMigrationMock_3();

        $manager = new DatabaseMigrationManager('4.4.12', 'up', '5.0');

        $manager->submitMigration($mock1);
        $this->assertEquals(1, $manager->count());

        $manager->submitMigration($mock2);
        $this->assertEquals(2, $manager->count());

        $manager->submitMigration($mock3);
        $this->assertEquals(2, $manager->count());
    }

    public function testDownMigration()
    {

        $mock1 = new DatabaseMigrationMock_1();
        $mock2 = new DatabaseMigrationMock_2();

        $manager = new DatabaseMigrationManager('5.0', 'down', '4.5.1');

        $manager->submitMigration($mock1);
        $manager->submitMigration($mock2);

        $this->assertEquals(1, $manager->count());
    }

    public function testSortAndCallback()
    {

        $manager = new DatabaseMigrationManager('4.4');

        $manager->submitMigration('Firelit\DatabaseMigrationMock_2');
        $manager->submitMigration('Firelit\DatabaseMigrationMock_3');
        $manager->submitMigration('Firelit\DatabaseMigrationMock_1');

        $this->assertEquals(3, $manager->count());

        $manager->sortMigrations();

        $test = $this;

        $manager->setPostExecCallback(function ($ver, $on, $of) use ($test) {

            if ($on == 0) {
                $test->assertEquals(1, DatabaseMigrationMock_1::$upCount);
                $test->assertEquals(0, DatabaseMigrationMock_2::$upCount);
                $test->assertEquals(0, DatabaseMigrationMock_3::$upCount);
            } elseif ($on == 1) {
                $test->assertEquals(1, DatabaseMigrationMock_1::$upCount);
                $test->assertEquals(1, DatabaseMigrationMock_2::$upCount);
                $test->assertEquals(0, DatabaseMigrationMock_3::$upCount);
            } elseif ($on == 2) {
                $test->assertEquals(1, DatabaseMigrationMock_1::$upCount);
                $test->assertEquals(1, DatabaseMigrationMock_2::$upCount);
                $test->assertEquals(1, DatabaseMigrationMock_3::$upCount);
            } else {
                throw new Exception('Invalid loop index: '. $on);
            }
        });

        $manager->executeMigrations();
    }
}
