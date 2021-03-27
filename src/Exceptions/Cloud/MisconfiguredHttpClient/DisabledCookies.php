<?php
namespace UAM\Exceptions\Cloud\MisconfiguredHttpClient;

use Exception;

class DisabledCookies extends Exception
{
    public function __construct($code = 0, Exception $previous = null)
    {
        $message = 'Cookies must be enabled for http client.';

        parent::__construct($message, $code, $previous);
    }
}
