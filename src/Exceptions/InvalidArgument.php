<?php
namespace UAM\Exceptions;

use Exception;

class InvalidArgument extends Exception
{
    public function __construct($name, $expected = null, $code = 0, Exception $previous = null)
    {
        $message = sprintf('Argument "%s" is invalid', $name);

        if ($expected) {
            $message .= sprintf(', should be %s', $expected);
        }

        $message .= '.';

        parent::__construct($message, $code, $previous);
    }
}
