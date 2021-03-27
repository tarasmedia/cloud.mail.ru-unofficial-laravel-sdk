<?php
namespace UAM\Exceptions\Cloud\InvalidResponse;

use Exception;

class EmptyBody extends Exception
{
    public function __construct($code = 0, Exception $previous = null)
    {
        $message = 'Invalid API response: empty body.';

        parent::__construct($message, $code, $previous);
    }
}
