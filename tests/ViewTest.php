<?PHP

class ViewTest extends PHPUnit_Framework_TestCase
{
    
    public function testLayoutTemplate()
    {
        $view = new Firelit\View();
        $view->setLayout('Test');
        $view->setTemplate('Temp');

        $this->assertEquals('Test', $view->layout);
        $this->assertEquals('Temp', $view->template);
    }

    public function testAssetAdder()
    {
        Firelit\View::$assetDirectory = '/assets/';
        Firelit\View::$viewFolder = __DIR__.'/';

        $view = new Firelit\View('ViewTestTemplate');

        ob_start();
        
        $view->render(); // Runs code in ViewTestTemplate.php

        $output = trim(ob_get_contents());
        ob_end_clean();

        $this->assertRegExp('!^<script(.*)</script>$!', $output);

        $this->assertRegExp('!type="text/javascript"!', $output);

        $this->assertRegExp('!src="/assets/test\.js!', $output);
    }
}
