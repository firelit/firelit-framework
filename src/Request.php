<?PHP

namespace Firelit;

class Request extends Singleton
{

    // All properties accessible via magic getter method
    private $ip;
    private $proxies;
    private $host;
    private $path;
    private $method;
    private $secure;
    private $referer;
    private $protocol;
    private $cli;
    private $headers;
    private $uri;
    private $body;

    private $put;
    private $post;
    private $get;
    private $cookie;

    // If load-balanced, look for appropriate headers
    public static $loadBalanced = false;

    // Used for unit testing: Supply data insetad of 'php://input'
    public static $dataInput;
    public static $methodInput;

    // $filter should be a filtering function, if supplied, which filters a string value by reference
    public function __construct($filter = false, $bodyFormat = 'querystring')
    {

        $this->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
        $this->proxies = array();
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
        $this->path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : false;
        $this->secure = isset($_SERVER['HTTPS']) ? ($_SERVER['HTTPS'] == 'on') : false;
        $this->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : false;
        $this->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
        $this->protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : false;

        $this->cli = (php_sapi_name() == 'cli');
        if ($this->cli) {
            $this->method = 'CLI';
        }

        if (isset(static::$methodInput)) {
            $this->method = static::$methodInput;
        }

        $this->uri = ($this->cli ? false : ($this->secure ? 'https' : 'http') .'://'. $this->host . $this->path);

        if (is_callable('apache_request_headers')) {
            $this->headers = apache_request_headers();

            if (self::$loadBalanced) {
                if (isset($this->headers['X-Forwarded-For'])) {
                    $ips = $this->headers['X-Forwarded-For'];
                    $ips = explode(', ', $ips);
                    $this->ip = array_shift($ips);

                    $this->proxies = $ips;
                }

                if (isset($this->headers['X-Forwarded-Proto'])) {
                    $this->secure = ($this->headers['X-Forwarded-Proto'] == 'HTTPS');
                }
            } elseif (isset($this->headers['X-Forwarded-For'])) {
                $ips = $this->headers['X-Forwarded-For'];
                $ips = explode(', ', $ips);
                $this->proxies = $ips;
            }
        } else {
            $this->headers = array();
        }

        // Create our own global array for PUT data
        global $_PUT;
        $_PUT = array();

        if (isset(self::$dataInput)) {
            $this->body = self::$dataInput;
        } else {
            $this->body = file_get_contents("php://input");
        }

        if ($this->method == 'PUT') {
            parse_str($this->body, $_PUT);
        }

        if ($bodyFormat == 'json') {
            $this->put = array();
            $this->post = array();

            $jsonErr = JSON_ERROR_NONE;

            if ($this->method == 'PUT') {
                $this->put = json_decode($this->body, true);
                $jsonErr = json_last_error();
            } elseif ($this->method == 'POST') {
                $this->post = json_decode($this->body, true);
                $jsonErr = json_last_error();
            }

            if ($jsonErr !== JSON_ERROR_NONE) {
                switch ($jsonErr) {
                    case JSON_ERROR_DEPTH:
                        $jsonErrMsg = 'Maximum stack depth exceeded.';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $jsonErrMsg = 'Underflow or the modes mismatch.';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $jsonErrMsg = 'Unexpected control character found.';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $jsonErrMsg = 'Syntax error, malformed data.';
                        break;
                    case JSON_ERROR_UTF8:
                        $jsonErrMsg = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                        break;
                    default:
                        $jsonErrMsg = 'Generic error.';
                }

                throw new InvalidJsonException($jsonErrMsg);
            }
        } else {
            $this->post = $_POST;
            $this->put = $_PUT;
        }

        $this->get = $_GET;
        $this->cookie = $_COOKIE;

        if ($filter) {
            // Filter local copies of POST, GET & COOKIE data
            // Unset global versions to prevent access to un-filtered
            $this->filterInputs($filter);

            $_PUT = null;
            $_POST = null;
            $_GET = null;
            $_COOKIE = null;
        }
    }

    public function filterInputs($filter = false)
    {

        if ($filter == false) {
            return;
        }
        if (!is_callable($filter)) {
            throw new \Exception('Specified filter is not callable.');
        }

        $this->recurse($this->post, $filter);
        $this->recurse($this->put, $filter);
        $this->recurse($this->get, $filter);
        $this->recurse($this->cookie, $filter);
    }

    protected function recurse(&$input, &$function)
    {

        if (is_array($input)) {
            foreach ($input as $name => &$value) {
                $this->recurse($value, $function);
            }
        } else {
            $function($input);
        }
    }

    public function __get($name)
    {

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \Exception('The property "'. $name .'" is not valid.');
    }

    public function __isset($name)
    {

        return isset($this->$name);
    }
}
