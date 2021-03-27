<?php
namespace UAM\Tests\Functional;

use Symfony\Component\Console\Tester\ApplicationTester;
use UAM\Application;
use UAM\Tests\TestCase;

class ApplicationTestCase extends TestCase
{
    /** @var ApplicationTester */
    protected $tester;

    public function setUp()
    {
        parent::setUp();

        $application = new Application();
        $application->setAutoExit(false);

        $this->tester = new ApplicationTester($application);
    }

    protected function runApplicationAndAssertResult(array $options, $expectedOutput, $expectedStatus)
    {
        $result = $this->runApplication($options);

        $this->assertEquals($expectedStatus, $result['status']);

        foreach ((array)$expectedOutput as $string) {
            $this->assertContains($string, $result['output']);
        }
    }

    protected function runApplication(array $options)
    {
        $this->tester->run($options);

        return [
            'output' => $this->tester->getDisplay(),
            'status' => $this->tester->getStatusCode(),
        ];
    }
}
