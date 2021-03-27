<?php
namespace UAM\Exceptions\Cloud\AccessDenied;

use Exception;

class InvalidCredentials extends Exception
{
    public function __construct($code = 0, Exception $previous = null)
    {
        $message = 'Auth attempt failed. Probably, incorrect login or password.';

        parent::__construct($message, $code, $previous);
    }
}
