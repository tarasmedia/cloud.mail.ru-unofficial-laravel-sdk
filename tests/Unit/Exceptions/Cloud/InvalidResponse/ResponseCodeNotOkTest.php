<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\InvalidResponse;

use UAM\Exceptions\Cloud\InvalidResponse\ResponseCodeNotOk;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class ResponseCodeNotOkTest extends ExceptionsTestCase
{
    protected $exceptionClass = ResponseCodeNotOk::class;
    protected $exceptionMessage = 'Invalid API response status code: 418.';
    protected $acceptsMessage = true;

    /** @test */
    public function it_has_correct_message()
    {
        $this->assertItHasCorrectMessage(418);
    }
}
