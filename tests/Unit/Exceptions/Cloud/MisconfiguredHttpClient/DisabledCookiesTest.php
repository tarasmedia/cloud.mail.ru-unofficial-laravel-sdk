<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\InvalidResponse;

use UAM\Exceptions\Cloud\MisconfiguredHttpClient\DisabledCookies;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class DisabledCookiesTest extends ExceptionsTestCase
{
    protected $exceptionClass = DisabledCookies::class;
    protected $exceptionMessage = 'Cookies must be enabled for http client.';
    protected $acceptsMessage = false;
}
