<?php
namespace UAM\Exceptions\Cloud\AccessDenied;

use Exception;

class InvalidToken extends Unspecified
{
    public function __construct($code = 0, Exception $previous = null)
    {
        $message = 'CSRF token is invalid';

        parent::__construct($message, $code, $previous);
    }
}
