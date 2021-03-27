<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\AccessDenied;

use UAM\Exceptions\Cloud\AccessDenied\AuthExpired;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class AuthExpiredTest extends ExceptionsTestCase
{
    protected $exceptionClass = AuthExpired::class;
    protected $exceptionMessage = 'Access denied: probably, authorization has expired.';
    protected $acceptsMessage = false;
}
