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

        $r->go();

        Registry::clear();
        unset($r);
    }

    /**
     * @group failing
     * Tests the api edit form
     */
    public function testNesting()
    {

        $request = new Request();

        $reflection = new \ReflectionClass($request);

        $reflection_property = $reflection->getProperty('path');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($request, '/test/nest/path.txt');

        $reflection_property = $reflection->getProperty('cli');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($request, false);

        $reflection_property = $reflection->getProperty('method');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($request, 'GET');

        $this->setExpectedException('Firelit\ExpectedException');

        $subR = new Router($request);

        $subR->add('GET', '!^path\.json$!', function () {
            // Bad
            throw new UnexpectedException();
        });

        $subR->add('GET', '!^path\.txt$!', function () {
            // Good!
            throw new ExpectedException();
        });

        $r = new Router($request);

        $r->add('GET', '!^/test/node/!', function () {
            // Bad
            throw new UnexpectedException();
        });

        $r->add('GET', '!^/test/nest/!', $subR);

        $r->go();

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

        $r->go();

        Registry::clear();
        unset($r);
    }

    public function testError()
    {

        $this->setExpectedException('Firelit\ExpectedException');

        $r = new Router(new Request());

        $r->add('POST', '/.*/', function () {
            // Bad
            throw new UnexpectedException();
        });

        $r->add('*', false, function () {
            // Should take this route
            throw new RouteToError(500);
        });

        $r->errorRoute(500, function () {
            // Good!
            throw new ExpectedException();
        });

        $r->errorRoute(404, function () {
            // Bad
            throw new UnexpectedException();
        });

        $r->go();

        Registry::clear();
        unset($r);
    }
}
