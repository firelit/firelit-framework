<?php

namespace Firelit;

class QueryIteratorTest extends \PHPUnit_Framework_TestCase
{

    public function testQueryIterator()
    {

        $qmock = $this->createMock('Firelit\Query', array('getRow'));
        $qmock
            ->method('getRow')
            ->will($this->onConsecutiveCalls(
                array(
                    'id' => 1,
                    'info' => 'yup'
                ),
                array(
                    'id' => 2,
                    'info' => 'uhhuh'
                ),
                false
            ));


        $sut = new QueryIterator($qmock);

        $sut->rewind();

        $this->assertTrue($sut->valid(), 'The first value should be valid');
        $this->assertEquals(0, $sut->key(), 'The first index should be 0');
        $this->assertEquals(array(
                    'id' => 1,
                    'info' => 'yup'
                ), $sut->current(), 'The first record should match');

        $sut->next();

        $this->assertTrue($sut->valid(), 'The second value should be valid');
        $this->assertEquals(1, $sut->key(), 'The second index should be 1');
        $this->assertEquals(array(
                    'id' => 2,
                    'info' => 'uhhuh'
                ), $sut->current(), 'The second record should match');

        $sut->next();

        $this->assertFalse($sut->valid(), 'The third value should not be valid');
    }
}
