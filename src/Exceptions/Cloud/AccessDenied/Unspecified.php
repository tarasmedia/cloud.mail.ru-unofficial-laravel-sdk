<?php
namespace UAM\Exceptions\Cloud\AccessDenied;

use Exception;

class Unspecified extends Exception
{
    public function __construct($message = 'unspecified reason', $code = 0, Exception $previous = null)
    {
        $message = sprintf('Access denied: %s.', $message);

        parent::__construct($message, $code, $previous);
    }
}
