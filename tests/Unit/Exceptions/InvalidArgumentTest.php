<?php
namespace UAM\Tests\Unit\Exceptions;

use Exception;
use UAM\Exceptions\InvalidArgument;

class InvalidArgumentTest extends ExceptionsTestCase
{
    protected $exceptionClass = InvalidArgument::class;

    /** @test */
    public function it_has_correct_message()
    {
        $this->exceptionMessage = 'Argument "foo" is invalid.';
        $this->assertItHasCorrectMessage('foo');
    }

    protected function assertItHasCorrectMessage($name, $expected = null)
    {
        $this->expectException($this->exceptionClass);

        $this->expectExceptionMessage($this->exceptionMessage);

        throw new $this->exceptionClass($name, $expected);
    }

    /** @test */
    public function it_accepts_expected_value()
    {
        $this->exceptionMessage = 'Argument "foo" is invalid, should be bar.';
        $this->assertItHasCorrectMessage('foo', 'bar');
    }

    /** @test */
    public function it_accepts_previous_exception()
    {
        $this->assertItAcceptsPreviousException();
    }

    protected function assertItAcceptsPreviousException()
    {
        $prev = $this->makePreviousException();

        /** @var Exception $exception */
        $exception = new $this->exceptionClass('', null, 0, $prev);

        $this->assertEquals($prev, $exception->getPrevious());
    }

    protected function assertItAcceptsCode()
    {
        $this->expectException($this->exceptionClass);
        $this->expectExceptionCode($this->exceptionCode);

        throw new $this->exceptionClass('', null, $this->exceptionCode);
    }
}
