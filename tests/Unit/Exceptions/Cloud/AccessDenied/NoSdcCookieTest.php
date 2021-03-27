<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\AccessDenied;

use UAM\Exceptions\Cloud\AccessDenied\NoSdcCookie;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class NoSdcCookieTest extends ExceptionsTestCase
{
    protected $exceptionClass = NoSdcCookie::class;
    protected $exceptionMessage = 'Access denied: SDC cookie was not sent.';
    protected $acceptsMessage = false;
}
