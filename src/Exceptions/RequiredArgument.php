<?php
namespace UAM\Exceptions;

use Exception;

class RequiredArgument extends Exception
{
    public function __construct($name, $code = 0, Exception $previous = null)
    {
        $message = sprintf('Argument "%s" is required.', $name);

        parent::__construct($message, $code, $previous);
    }
}
