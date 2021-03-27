<?php
namespace UAM\Exceptions\Cloud\InvalidResponse;

use Exception;

class ParsingError extends Exception
{
    public function __construct($code = 0, Exception $previous = null)
    {
        $message = sprintf('Could not parse API response: %s.', json_last_error_msg());

        parent::__construct($message, $code, $previous);
    }
}
