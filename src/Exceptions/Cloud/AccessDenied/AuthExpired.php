<?php
namespace UAM\Exceptions\Cloud\AccessDenied;

use Exception;

class AuthExpired extends Unspecified
{
    public function __construct($code = 0, Exception $previous = null)
    {
        $message = 'probably, authorization has expired';

        parent::__construct($message, $code, $previous);
    }
}
