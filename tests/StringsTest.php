<?PHP

class StringsTest extends PHPUnit_Framework_TestCase
{

    public function testValidation()
    {

        // Check if an email is valid
        $this->assertTrue(Firelit\Strings::validEmail('test@test.com')); // Valid
        $this->assertTrue(Firelit\Strings::validEmail('test.test@test.com')); // Valid
        $this->assertFalse(Firelit\Strings::validEmail('test')); // Invalid
        $this->assertFalse(Firelit\Strings::validEmail('test test@test.com')); // Invalid
        $this->assertFalse(Firelit\Strings::validEmail('@test.com')); // Invalid
    }

    public function testFilter()
    {

        $test = "1ø2\x08"; // Ends with a backspace

        // Clean the strings in the array for all valid UTF8
        Firelit\Strings::cleanUTF8($test); // The parameter is passed by reference and directly filtered

        $this->assertEquals('1ø2', $test);

        $array = array(
            'first' => 'first',
            'second' => "\x00\x00", // Two nulls
            'third' => 'last'
        );

        Firelit\Strings::cleanUTF8($array); // The parameter is passed by reference and directly filtered

        $this->assertTrue(is_array($array));
        $this->assertEquals(3, sizeof($array));
        $this->assertEquals('first', $array['first']);
        $this->assertEquals('', $array['second']);
        $this->assertEquals('last', $array['third']);
    }

    public function testNames()
    {

        // Name normalization
        $out = Firelit\Strings::nameFix('JOHN P.  DePrez SR');
        $this->assertEquals('John P. DePrez Sr', $out);

        $out = Firelit\Strings::nameFix('j. d. doe i');
        $this->assertEquals('J. D. Doe I', $out);

        $out = Firelit\Strings::nameFix('JOHN doe-dream ii');
        $this->assertEquals('John Doe-Dream II', $out);

        $out = Firelit\Strings::nameFix('John mcdonald iii');
        $this->assertEquals('John McDonald III', $out);

        $out = Firelit\Strings::nameFix('John VanPlaat iV');
        $this->assertEquals('John VanPlaat IV', $out);

        $out = Firelit\Strings::nameFix('JOHN doe SR');
        $this->assertEquals('John Doe Sr', $out);

        $out = Firelit\Strings::nameFix('JOHN DeBoer j.r.');
        $this->assertEquals('John DeBoer Jr', $out);

        $out = Firelit\Strings::nameFix('john di\'vinici');
        $this->assertEquals('John Di\'Vinici', $out);

        $out = Firelit\Strings::nameFix('Sam + John Smith');
        $this->assertEquals('Sam & John Smith', $out);

        $out = Firelit\Strings::nameFix('Sam and John Smith');
        $this->assertEquals('Sam and John Smith', $out);

        $out = Firelit\Strings::nameFix('Sam+John Smith');
        $this->assertEquals('Sam & John Smith', $out);

        $out = Firelit\Strings::nameFix('Sam&John Smith');
        $this->assertEquals('Sam & John Smith', $out);
    }

    public function testAddresses()
    {

        $out = Firelit\Strings::addressFix('123 Upper Ave. s.e.');
        $this->assertEquals('123 Upper Ave. SE', $out);

        $out = Firelit\Strings::addressFix('123 Upper Ave. Ne.');
        $this->assertEquals('123 Upper Ave. NE', $out);

        $out = Firelit\Strings::addressFix('123 Upper Ave. sw');
        $this->assertEquals('123 Upper Ave. SW', $out);

        $out = Firelit\Strings::addressFix('123 Upper Ave. NW');
        $this->assertEquals('123 Upper Ave. NW', $out);

        $out = Firelit\Strings::addressFix('123 NORTH AVENUE SE');
        $this->assertEquals('123 North Avenue SE', $out);

        $out = Firelit\Strings::addressFix('po box 3484');
        $this->assertEquals('PO Box 3484', $out);

        $out = Firelit\Strings::addressFix('P.O. box 3484');
        $this->assertEquals('PO Box 3484', $out);
    }

    public function testEscaping()
    {
        // Multi-byte HTML and XML escaping
        $out = Firelit\Strings::html('You & I Rock');
        $this->assertEquals('You &amp; I Rock', $out);

        $out = Firelit\Strings::xml('You & I Rock');
        $this->assertEquals('You &#38; I Rock', $out);
    }

    public function testCaseManipulation()
    {

        // Multi-byte safe string case maniuplation
        $out = Firelit\Strings::upper('this started lower, èric');
        $this->assertEquals('THIS STARTED LOWER, ÈRIC', $out);

        $out = Firelit\Strings::lower('THIS STARTED UPPER, ÈRIC');
        $this->assertEquals('this started upper, èric', $out);

        $out = Firelit\Strings::title('this STARTED mixed, èric');
        $this->assertEquals('This Started Mixed, Èric', $out);

        $out = Firelit\Strings::ucwords('this STARTED mixed, èric');
        $this->assertEquals('This STARTED Mixed, Èric', $out);
    }
}
