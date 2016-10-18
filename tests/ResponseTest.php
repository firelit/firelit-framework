<?PHP

namespace Firelit;

class ResponseTest extends \PHPUnit_Framework_TestCase
{

    public function testBufferClear()
    {

        ob_start();

        $r = Response::init();

        echo 'Should be cleared';

        $r->clearBuffer();
        $r->endBuffer();

        unset($r);
        Response::destruct();

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('', $output);
    }

    public function testBufferFlush()
    {

        ob_start();

        $r = Response::init();
        $r->setCallback(function (&$out) {
            $out = preg_replace('/not/', 'NOT', $out);
        });

        echo 'Should not be cleared';

        unset($r);
        Response::destruct();

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('Should NOT be cleared', $output);
    }
}
