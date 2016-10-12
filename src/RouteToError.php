<?php

namespace Firelit;

class RouteToError extends \Exception
{

    public function __construct($code, $message = null)
    {
        parent::__construct($message, $code);
    }
}
