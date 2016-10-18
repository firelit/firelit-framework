<?PHP

namespace Firelit;

// @codingStandardsIgnoreStart
class ExpectedException extends \Exception
{

}

class UnexpectedException extends \Exception
{

}
// @codingStandardsIgnoreEnd

// @codingStandardsIgnoreLine (ignoring multiple classes in a file)
class RouterTest extends \PHPUnit_Framework_TestCase
{

    public function testAdd()
    {

        $this->setExpectedException('Firelit\ExpectedException');

        $r = new Router(new Request());

        $r->add('POST', '/.*/', function () {
            // Bad
            throw new UnexpectedException();
        });

        $r->add('*', false, function () {
            // Good!
            throw new ExpectedException();
        });

        Registry::clear();
        unset($r);
    }

    public function testDefault()
    {

        $this->setExpectedException('Firelit\ExpectedException');

        $r = new Router(new Request());

        $r->defaultRoute(function () {
            // Good!
            throw new ExpectedException();
        });

        $r->add('POST', '/.*/', function () {
            // Bad
            throw new UnexpectedException();
        });

        $r->add('*', '/.*/', function () {
            // Bad
            throw new UnexpectedException();
        });

        Registry::clear();
        unset($r);
    }

    public function testError()
    {

        $this->setExpectedException('Firelit\ExpectedException');

        $r = new Router(new Request());

        $r->errorRoute(500, function () {
            // Good!
            throw new ExpectedException();
        });

        $r->errorRoute(404, function () {
            // Bad
            throw new UnexpectedException();
        });

        $r->add('POST', '/.*/', function () {
            // Bad
            throw new UnexpectedException();
        });

        $r->add('*', false, function () {
            // Should take this route
            throw new RouteToError(500);
        });

        Registry::clear();
        unset($r);
    }
}
