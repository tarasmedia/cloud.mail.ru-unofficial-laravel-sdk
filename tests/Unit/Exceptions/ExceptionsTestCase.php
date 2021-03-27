<?php
namespace UAM\Tests\Unit\Exceptions;

use Exception;
use UAM\Tests\TestCase;

class ExceptionsTestCase extends TestCase
{
    /** @var string */
    protected $exceptionClass;

    /** @var string */
    protected $exceptionMessage;

    /** @var string */
    protected $exceptionCode = 42;

    /** @var bool */
    protected $acceptsMessage = false;

    /** @test */
    public function it_has_correct_message()
    {
        $this->assertItHasCorrectMessage();
    }

    protected function assertItHasCorrectMessage($message = null)
    {
        $this->expectException($this->exceptionClass);

        $this->expectExceptionMessage($this->exceptionMessage);

        if ($this->acceptsMessage) {
            throw new $this->exceptionClass($message);
        }

        throw new $this->exceptionClass;
    }

    /** @test */
    public function it_accepts_code()
    {
        $this->assertItAcceptsCode();
    }

    protected function assertItAcceptsCode()
    {
        $this->expectException($this->exceptionClass);
        $this->expectExceptionCode($this->exceptionCode);

        if ($this->acceptsMessage) {
            throw new $this->exceptionClass('', $this->exceptionCode);
        }

        throw new $this->exceptionClass($this->exceptionCode);
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
        if ($this->acceptsMessage) {
            $exception = new $this->exceptionClass('', 0, $prev);
        } else {
            $exception = new $this->exceptionClass(0, $prev);
        }

        $this->assertEquals($prev, $exception->getPrevious());
    }

    /**
     * @param string $message
     * @param int|null $code
     * @return Exception
     */
    protected function makePreviousException($message = 'Previous Exception', $code = null)
    {
        if (is_null($code)) {
            $code = $this->exceptionCode;
        }

        return new Exception($message, $code);
    }
}
