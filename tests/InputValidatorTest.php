<?PHP

use Firelit\InputValidator;

class InputValidatorTest extends PHPUnit_Framework_TestCase {

	public function testStatic() {

		// Static versions of called functions
		$res = InputValidator::validate(InputValidator::EMAIL, 'test@test');
		$this->assertEquals(false, $res);

		$res = InputValidator::validate(InputValidator::EMAIL, 'test@test.com');
		$this->assertEquals(true, $res);

	}

	public function testNotRequired() {

		// Fails, blank but required
		$iv = new InputValidator(InputValidator::STATE, '', 'US');
		$this->assertEquals(false, $iv->isValid());

		// Blank ok, not required
		$iv = new InputValidator(InputValidator::STATE, '', 'US');
		$iv->setRequired(false);
		$this->assertEquals(true, $iv->isValid());

	}

	public function testName() {

		// Valid name
		$iv = new InputValidator(InputValidator::NAME, 'JOHN doe');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('John Doe', $iv->getNormalized());

		// Valid name with accent
		$iv = new InputValidator(InputValidator::NAME, 'Juán Doe');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('Juán Doe', $iv->getNormalized());

		// Valid name with accent and abbreviation
		$iv = new InputValidator(InputValidator::NAME, 'juan s.');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('Juan S.', $iv->getNormalized());

		// Invalid name
		$iv = new InputValidator(InputValidator::NAME, '123Sam');
		$this->assertEquals(false, $iv->isValid());

		// Name normalization
		$iv = new InputValidator(InputValidator::NAME, 'j. d. doe i');
		$this->assertEquals('J. D. Doe I', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'JOHN doe-dream ii');
		$this->assertEquals('John Doe-Dream II', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'John mcdonald iii');
		$this->assertEquals('John McDonald III', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'John VanPlaat iV');
		$this->assertEquals('John VanPlaat IV', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'JOHN doe SR');
		$this->assertEquals('John Doe Sr', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'JOHN DeBoer j.r.');
		$this->assertEquals('John DeBoer Jr', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'john di\'vinici');
		$this->assertEquals('John Di\'Vinici', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'Sam + John Smith');
		$this->assertEquals('Sam & John Smith', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'Sam and John Smith');
		$this->assertEquals('Sam and John Smith', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'Sam+John Smith');
		$this->assertEquals('Sam & John Smith', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::NAME, 'Sam&John Smith');
		$this->assertEquals('Sam & John Smith', $iv->getNormalized());

	}

	public function testCity() {

		// Valid city
		$iv = new InputValidator(InputValidator::CITY, 'maryville');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('Maryville', $iv->getNormalized());

		// Invalid name
		$iv = new InputValidator(InputValidator::CITY, '123Sam');
		$this->assertEquals(false, $iv->isValid());

	}


	public function testEmail() {

		// Valid email address
		$iv = new InputValidator(InputValidator::EMAIL, 'test-test.test@test-email.com');
		$this->assertEquals(true, $iv->isValid());

		// Valid email address
		$iv = new InputValidator(InputValidator::EMAIL, 'test_test55@test.what.NET');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('test_test55@test.what.net', $iv->getNormalized());

		// Invalid email address
		$iv = new InputValidator(InputValidator::EMAIL, 'test55@test');
		$this->assertEquals(false, $iv->isValid());

		// Invalid email address
		$iv = new InputValidator(InputValidator::EMAIL, 'test(55)@test');
		$this->assertEquals(false, $iv->isValid());

	}

	public function testCreditCardNumber() {

		// Valid VISA credit card number
		$iv = new InputValidator(InputValidator::CREDIT_ACCT, '4111111111111111');
		$this->assertEquals(true, $iv->isValid());

		// Valid AMEX credit card number
		$iv = new InputValidator(InputValidator::CREDIT_ACCT, '370000000000002');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('xxxxxxxxxxx0002', $iv->getNormalized());

		// Valid MC credit card number
		$iv = new InputValidator(InputValidator::CREDIT_ACCT, '5454 5454 5454 5454');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('5454545454545454', $iv->getNormalized(InputValidator::TYPE_GATEWAY));

		// Invalid credit card number
		$iv = new InputValidator(InputValidator::CREDIT_ACCT, '4111 1111 1111 1112');
		$this->assertEquals(false, $iv->isValid());

	}

	public function testExpirationDates() {

		// Valid expiration date
		$iv = new InputValidator(InputValidator::CREDIT_EXP, '1217');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('12/17', $iv->getNormalized());
		$this->assertEquals('1217', $iv->getNormalized(InputValidator::TYPE_GATEWAY));
		$this->assertEquals('2017-12-31', $iv->getNormalized(InputValidator::TYPE_DB));

		// Valid expiration date
		$iv = new InputValidator(InputValidator::CREDIT_EXP, '5/18');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('0518', $iv->getNormalized(InputValidator::TYPE_GATEWAY));

		// Valid expiration date
		$iv = new InputValidator(InputValidator::CREDIT_EXP, '01/16');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('01/16', $iv->getNormalized());
		$this->assertEquals('2016-01-31', $iv->getNormalized(InputValidator::TYPE_DB));

		// Invalid expiration date
		$iv = new InputValidator(InputValidator::CREDIT_EXP, '1317');
		$this->assertEquals(false, $iv->isValid());

		// Invalid expiration date
		$iv = new InputValidator(InputValidator::CREDIT_EXP, '0016');
		$this->assertEquals(false, $iv->isValid());

	}

	public function testSecurityCode() {

		// Valid security code
		$iv = new InputValidator(InputValidator::CREDIT_CVV, '123');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('xxx', $iv->getNormalized());
		$this->assertEquals('123', $iv->getNormalized(InputValidator::TYPE_GATEWAY));

		// Valid security code
		$iv = new InputValidator(InputValidator::CREDIT_CVV, '0234');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('xxxx', $iv->getNormalized());
		$this->assertEquals('0234', $iv->getNormalized(InputValidator::TYPE_GATEWAY));

		// Invalid security code
		$iv = new InputValidator(InputValidator::CREDIT_CVV, '00');
		$this->assertEquals(false, $iv->isValid());

	}

	public function testRoutingNumber() {

		// Valid routing number
		$iv = new InputValidator(InputValidator::ACH_ROUT, '123123123');
		$this->assertEquals(true, $iv->isValid());

		// Invalid routing number
		$iv = new InputValidator(InputValidator::ACH_ROUT, '123123124');
		$this->assertEquals(false, $iv->isValid());

	}

	public function testBankAccountNumber() {

		// Valid account number
		$iv = new InputValidator(InputValidator::ACH_ACCT, '33213321');
		$this->assertEquals(true, $iv->isValid());

		// Valid account number
		$iv = new InputValidator(InputValidator::ACH_ACCT, '3321-3321');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('xxxx3321', $iv->getNormalized());
		$this->assertEquals('33213321', $iv->getNormalized(InputValidator::TYPE_GATEWAY));

		// Invalid character
		$iv = new InputValidator(InputValidator::ACH_ACCT, '3321@3321');
		$this->assertEquals(false, $iv->isValid());

		// Valid
		$iv = new InputValidator(InputValidator::ACH_ACCT, '3321');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('xx21', $iv->getNormalized());

		// Too short (3 chars)
		$iv = new InputValidator(InputValidator::ACH_ACCT, '332');
		$this->assertEquals(false, $iv->isValid());

		// Too long (30 chars)
		$iv = new InputValidator(InputValidator::ACH_ACCT, '123456789012345678901234567890');
		$this->assertEquals(false, $iv->isValid());

	}
	
	public function testBankAccountType() {

		// Valid type: checking
		$iv = new InputValidator(InputValidator::ACH_TYPE, 'C');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('Checking', $iv->getNormalized());
		$this->assertEquals('C', $iv->getNormalized(InputValidator::TYPE_DB));

		// Valid type: checking
		$iv = new InputValidator(InputValidator::ACH_TYPE, 'checking');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('Checking', $iv->getNormalized());
		$this->assertEquals('C', $iv->getNormalized(InputValidator::TYPE_DB));

		// Valid type: savings
		$iv = new InputValidator(InputValidator::ACH_TYPE, 'savings');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('Savings', $iv->getNormalized());
		$this->assertEquals('S', $iv->getNormalized(InputValidator::TYPE_DB));

		// Invalid type
		$iv = new InputValidator(InputValidator::ACH_TYPE, 'other');
		$this->assertEquals(false, $iv->isValid());

	}

	public function testAddress() {

		// Invalid US address
		$iv = new InputValidator(InputValidator::ADDRESS, 'North', 'US');
		$this->assertEquals(false, $iv->isValid());

		// Valid US address
		$iv = new InputValidator(InputValidator::ADDRESS, '123 NORTH AVENUE SE', 'US');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('123 North Avenue SE', $iv->getNormalized());

		// Directional normalization
		$iv = new InputValidator(InputValidator::ADDRESS, '123 Upper Ave. s.e.', 'US');
		$this->assertEquals('123 Upper Ave. SE', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::ADDRESS, '123 Upper Ave. Ne.', 'US');
		$this->assertEquals('123 Upper Ave. NE', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::ADDRESS, '123 Upper Ave. sw', 'US');
		$this->assertEquals('123 Upper Ave. SW', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::ADDRESS, '123 Upper Ave. NW', 'US');
		$this->assertEquals('123 Upper Ave. NW', $iv->getNormalized());

		// Valid no-region address
		$iv = new InputValidator(InputValidator::ADDRESS, 'Field 12');
		$this->assertEquals(true, $iv->isValid());

		// PO Box normalization
		$iv = new InputValidator(InputValidator::ADDRESS, 'po box 123');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('PO Box 123', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::ADDRESS, 'p.o. BOX 123');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('PO Box 123', $iv->getNormalized());

		$iv = new InputValidator(InputValidator::ADDRESS, 'Po. BOX 123');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('PO Box 123', $iv->getNormalized());

	}

	public function testPhone() {

		// Valid US phone
		$iv = new InputValidator(InputValidator::PHONE, '6165551234', 'US');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('(616) 555-1234', $iv->getNormalized());

		// US phone with country code
		$iv = new InputValidator(InputValidator::PHONE, '+16165551234', 'US');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('(616) 555-1234', $iv->getNormalized());

		// Invalid US phone (not enough digits)
		$iv = new InputValidator(InputValidator::PHONE, '1165551234', 'US');
		$this->assertEquals(false, $iv->isValid());

		// International
		$iv = new InputValidator(InputValidator::PHONE, '(11) 655-5123');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('(11) 655-5123', $iv->getNormalized());

		// Not required, blank
		$iv = new InputValidator(InputValidator::PHONE, '', 'US');
		$iv->setRequired(false);
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('', $iv->getNormalized());

	}

	public function testState() {

		// Valid US state
		$iv = new InputValidator(InputValidator::STATE, 'CA', 'US');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('CA', $iv->getNormalized());

		// Valid lower case US state
		$iv = new InputValidator(InputValidator::STATE, 'ca', 'US');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('CA', $iv->getNormalized());

		// Not a Canadian province
		$iv = new InputValidator(InputValidator::STATE, 'MI', 'CA');
		$this->assertEquals(false, $iv->isValid());

		// Valid Canadian province
		$iv = new InputValidator(InputValidator::STATE, 'BC', 'CA');
		$this->assertEquals(true, $iv->isValid());

	}

	public function testZip() {

		// No-region zip
		$iv = new InputValidator(InputValidator::ZIP, '91101');
		$this->assertEquals(true, $iv->isValid());

		// Valid US zip
		$iv = new InputValidator(InputValidator::ZIP, '91101', 'US');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('91101', $iv->getNormalized());

		// Valid US zip
		$iv = new InputValidator(InputValidator::ZIP, '91101-1234', 'US');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('91101-1234', $iv->getNormalized());

		// Invalid Candian zip
		$iv = new InputValidator(InputValidator::ZIP, '91101', 'CA');
		$this->assertEquals(false, $iv->isValid());

		// No-region zip
		$iv = new InputValidator(InputValidator::ZIP, 'K1A 0B1');
		$this->assertEquals(true, $iv->isValid());

		// Invalid US zip
		$iv = new InputValidator(InputValidator::ZIP, '91101-123', 'US');
		$this->assertEquals(false, $iv->isValid());

		// Invalid US zip
		$iv = new InputValidator(InputValidator::ZIP, 'K1A 0B1', 'US');
		$this->assertEquals(false, $iv->isValid());

		// Valid Candian zip
		$iv = new InputValidator(InputValidator::ZIP, 'K1A 0B1', 'CA');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('K1A 0B1', $iv->getNormalized());

		// Valid unformated Candian zip
		$iv = new InputValidator(InputValidator::ZIP, 'K1a0B1', 'CA');
		$this->assertEquals(true, $iv->isValid());
		$this->assertEquals('K1A 0B1', $iv->getNormalized());

	}

	public function testCountry() {

		// Valid country
		$iv = new InputValidator(InputValidator::COUNTRY, 'US');
		$this->assertEquals(true, $iv->isValid());

		// Invalid country
		$iv = new InputValidator(InputValidator::COUNTRY, 'ZZ');
		$this->assertEquals(false, $iv->isValid());

	}


}