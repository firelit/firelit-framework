<?php

use Firelit\Countries;

class CountriesTest extends PHPUnit_Framework_TestCase
{

    public function testGetName()
    {

        $this->assertEquals('United States', Countries::getName('US'));
        $this->assertEquals('Canada', Countries::getName('CA'));
        $this->assertEquals('Åland Islands', Countries::getName('AX', false));
        $this->assertEquals('&Aring;land Islands', Countries::getName('AX', true));
    }
}
