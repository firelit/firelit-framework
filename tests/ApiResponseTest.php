<?PHP

namespace Firelit;

class ApiResponseTest extends \PHPUnit_Framework_TestCase
{

    public function testTemplate()
    {

        ob_start();

        $resp = ApiResponse::init('JSON', false);

        $resp->setTemplate(array('res' => true, 'message' => 'Peter picked a pack of pickels'));
        $resp->respond(array(), false);

        unset($resp);
        ApiResponse::destruct();

        $res = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('{"res":true,"message":"Peter picked a pack of pickels"}', $res);
    }

    public function testCancel()
    {

        ob_start();

        $resp = ApiResponse::init('JSON', false);

        $resp->setTemplate(array('res' => true, 'message' => 'Peter picked a pack of pickels'));

        $resp->cancel();

        echo '<HTML>';

        unset($resp);
        ApiResponse::destruct();

        $res = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('<HTML>', $res);
    }

    public function testJsonCallbackWrap()
    {

        ob_start();

        $resp = ApiResponse::init('JSON', false);

        $resp->setJsonCallbackWrap('test_function');

        $resp->setTemplate(array('res' => false, 'message' => 'Peter picked a pack of pickels'));
        $resp->respond(array(), false);

        unset($resp);
        ApiResponse::destruct();

        $res = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('test_function({"res":false,"message":"Peter picked a pack of pickels"});', $res);
    }


    public function testApiCallback()
    {

        ob_start();

        $resp = ApiResponse::init('JSON', false);

        $resp->setApiCallback(function (&$response) {

            unset($response['data']);
            $response['new'] = true;
        });

        $resp->setTemplate(array('res' => true, 'message' => 'Peter picked a pack of pickels', 'data' => 'erase me'));
        $resp->respond(array(), false);

        unset($resp);
        ApiResponse::destruct();

        $res = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('{"res":true,"message":"Peter picked a pack of pickels","new":true}', $res);
    }
}
