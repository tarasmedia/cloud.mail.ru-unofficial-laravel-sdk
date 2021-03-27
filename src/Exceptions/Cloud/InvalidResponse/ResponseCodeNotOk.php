<?php
namespace UAM\Exceptions\Cloud\InvalidResponse;

use Exception;

class ResponseCodeNotOk extends Exception
{
    public function __construct($responseCode = 0, $code = 0, Exception $previous = null)
    {
        $message = sprintf('Invalid API response status code: %s.', $responseCode);

        parent::__construct($message, $code, $previous);
    }
}
