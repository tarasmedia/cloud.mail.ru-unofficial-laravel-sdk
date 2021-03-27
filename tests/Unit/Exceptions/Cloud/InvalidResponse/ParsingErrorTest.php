<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\InvalidResponse;

use UAM\Exceptions\Cloud\InvalidResponse\ParsingError;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class ParsingErrorTest extends ExceptionsTestCase
{
    protected $exceptionClass = ParsingError::class;
    protected $exceptionMessage = 'Could not parse API response: unexpected character.';
    protected $acceptsMessage = false;

    /** @test */
    public function it_has_correct_message()
    {
        json_decode('<h1>Not A Valid JSON</h1>');

        $this->assertItHasCorrectMessage();
    }
}
