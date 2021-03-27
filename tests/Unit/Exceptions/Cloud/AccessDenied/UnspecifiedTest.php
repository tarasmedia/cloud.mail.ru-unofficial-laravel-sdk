<?php
namespace UAM\Tests\Unit\Exceptions\Cloud\AccessDenied;

use UAM\Exceptions\Cloud\AccessDenied\Unspecified;
use UAM\Tests\Unit\Exceptions\ExceptionsTestCase;

class UnspecifiedTest extends ExceptionsTestCase
{
    protected $exceptionClass = Unspecified::class;
    protected $exceptionMessage = 'Access denied: some error.';
    protected $acceptsMessage = true;

    /** @test */
    public function it_has_correct_message()
    {
        $this->assertItHasCorrectMessage('some error');
    }
}
