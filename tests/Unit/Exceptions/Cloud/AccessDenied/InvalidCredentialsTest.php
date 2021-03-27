<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\AccessDenied;

use UAM\Exceptions\Cloud\AccessDenied\InvalidCredentials;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class InvalidCredentialsTest extends ExceptionsTestCase
{
    protected $exceptionClass = InvalidCredentials::class;
    protected $exceptionMessage = 'Auth attempt failed. Probably, incorrect login or password.';
    protected $acceptsMessage = false;
}
