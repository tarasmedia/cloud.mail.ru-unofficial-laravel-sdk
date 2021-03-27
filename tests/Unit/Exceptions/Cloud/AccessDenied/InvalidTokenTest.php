<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\AccessDenied;

use UAM\Exceptions\Cloud\AccessDenied\InvalidToken;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class InvalidTokenTest extends ExceptionsTestCase
{
    protected $exceptionClass = InvalidToken::class;
    protected $exceptionMessage = 'Access denied: CSRF token is invalid.';
    protected $acceptsMessage = false;
}
