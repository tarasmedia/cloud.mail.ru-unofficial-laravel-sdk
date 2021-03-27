<?php
namespace UAM\Exceptions\Cloud\AccessDenied;

use Exception;

class NoSdcCookie extends Unspecified
{
    public function __construct($code = 0, Exception $previous = null)
    {
        $message = 'SDC cookie was not sent';

        parent::__construct($message, $code, $previous);
    }
}
