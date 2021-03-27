<?php
namespace UAM\Tests\Unit\Exceptions;

use UAM\Exceptions\RequiredArgument;

class RequiredArgumentTest extends ExceptionsTestCase
{
    protected $exceptionClass = RequiredArgument::class;
    protected $exceptionMessage = 'Argument "foo" is required.';
    protected $acceptsMessage = true;

    /** @test */
    public function it_has_correct_message()
    {
        $this->assertItHasCorrectMessage('foo');
    }
}
