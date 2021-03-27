<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\InvalidResponse;

use UAM\Exceptions\Cloud\InvalidResponse\EmptyBody;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class EmptyBodyTest extends ExceptionsTestCase
{
    protected $exceptionClass = EmptyBody::class;
    protected $exceptionMessage = 'Invalid API response: empty body.';
    protected $acceptsMessage = false;
}
