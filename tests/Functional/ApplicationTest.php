<?php
namespace UAM\Tests\Functional;

class ApplicationTest extends ApplicationTestCase
{
    /** @before */
    public function preparePlayground()
    {
        $this->clearPlayground();

        mkdir($this->playgroundPath('downloads'), 0777);
        mkdir($this->playgroundPath('not_writable'), 0555);
        file_put_contents($this->playgroundPath('is_file'), '');
    }

    /** @after */
    public function clearPlayground()
    {
        $this->removeDirectory($this->playgroundPath('downloads'));
        $this->removeDirectory($this->playgroundPath('not_writable'));
        $this->removeFile($this->playgroundPath('is_file'));
    }

    /** @test */
    public function it_fails_if_required_arguments_were_not_passed()
    {
        $runs = [
            [
                'expected_status' => 1,
                'expected_output' => 'Argument "login" is required.',
                'arguments' => ['-n' => null],
            ],
            [
                'expected_status' => 1,
                'expected_output' => 'The "--login" option requires a value.',
                'arguments' => ['-n' => null, '-l' => null],
            ],
            [
                'expected_status' => 1,
                'expected_output' => 'Argument "password" is required.',
                'arguments' => ['-n' => null, '-l' => 'login'],
            ],
            [
                'expected_status' => 1,
                'expected_output' => 'The "--password" option requires a value.',
                'arguments' => ['-n' => null, '-p' => null],
            ],
            [
                'expected_status' => 1,
                'expected_output' => 'Argument "target" is required.',
                'arguments' => ['-n' => null, '-l' => 'login', '-p' => 'password'],
            ],
            [
                'expected_status' => 1,
                'expected_output' => 'The "--target" option requires a value.',
                'arguments' => ['-n' => null, '-l' => 'login', '-p' => 'password', '-t' => null],
            ],
        ];

        foreach ($runs as $run) {
            $this->runApplicationAndAssertResult(
                $run['arguments'],
                $run['expected_output'],
                $run['expected_status']
            );
        }
    }

    /** @test */
    public function it_fails_if_argument_target_is_not_a_directory()
    {
        $expectedOutput = 'Argument "target" is invalid, should be existing directory.';
        $expectedStatus = 1;

        $options = [
            '-n' => null,
            '-l' => 'login',
            '-p' => 'password',
            '-t' => $this->playgroundPath('not_exists'),
            '-m' => 10,
        ];

        $this->runApplicationAndAssertResult($options, $expectedOutput, $expectedStatus);

        $file = $this->playgroundPath('is_file');

        $options = [
            '-n' => null,
            '-l' => 'login',
            '-p' => 'password',
            '-t' => $file,
            '-m' => 10,
        ];

        $this->runApplicationAndAssertResult($options, $expectedOutput, $expectedStatus);
    }

    /** @test */
    public function it_fails_if_argument_target_is_not_writable()
    {
        $expectedOutput = 'Argument "target" is invalid, should be writable.';
        $expectedStatus = 1;

        $dir = $this->playgroundPath('not_writable');

        $options = [
            '-n' => null,
            '-l' => 'login',
            '-p' => 'password',
            '-t' => $dir,
            '-m' => 10,
        ];

        $this->runApplicationAndAssertResult($options, $expectedOutput, $expectedStatus);
    }

    /** @test */
    public function it_fails_if_credentials_are_invalid()
    {
        $expectedOutput = 'Auth attempt failed. Probably, incorrect login or password.';
        $expectedStatus = 1;

        $options = [
            '-n' => null,
            '-l' => 'nobody@nowhere.nope',
            '-p' => 'DEFINITELY NOT A PASSWORD',
            '-t' => $this->playgroundPath('downloads'),
            '-m' => 10,
        ];

        $this->runApplicationAndAssertResult($options, $expectedOutput, $expectedStatus);
    }

    /** @test */
    public function it_shows_a_list_of_files_if_credentials_are_valid()
    {
        $expectedOutput = <<<RES
+--------------------------+------------------+----------+
| Folder                   | Name             | Size     |
+--------------------------+------------------+----------+
| /Фотографии/Отпуск 2020/ | Берег.jpg        | 707 KB   |
| /Фотографии/Отпуск 2020/ | На отдыхе.jpg    | 1.7 MB   |
| /Фотографии/             | Горное озеро.jpg | 1,005 KB |
| /Фотографии/             | Долина реки.jpg  | 1.2 MB   |
| /Фотографии/             | Чистая вода.jpg  | 917 KB   |
+--------------------------+------------------+----------+
RES;

        $expectedStatus = 0;

        $options = [
            '-n' => null,
            '-l' => getenv('MAILRU_LOGIN'),
            '-p' => getenv('MAILRU_PASSWORD'),
            '-t' => $this->playgroundPath('downloads'),
            '-s' => '/',
            '-m' => 10,
        ];

        $this->runApplicationAndAssertResult($options, $expectedOutput, $expectedStatus);
    }

    /** @test */
    public function it_has_a_default_value_for_source_option()
    {
        $expectedOutput = <<<RES
+--------------------------+------------------+----------+
| Folder                   | Name             | Size     |
+--------------------------+------------------+----------+
| /Фотографии/Отпуск 2020/ | Берег.jpg        | 707 KB   |
| /Фотографии/Отпуск 2020/ | На отдыхе.jpg    | 1.7 MB   |
| /Фотографии/             | Горное озеро.jpg | 1,005 KB |
| /Фотографии/             | Долина реки.jpg  | 1.2 MB   |
| /Фотографии/             | Чистая вода.jpg  | 917 KB   |
+--------------------------+------------------+----------+
RES;

        $expectedStatus = 0;

        $options = [
            '-n' => null,
            '-l' => getenv('MAILRU_LOGIN'),
            '-p' => getenv('MAILRU_PASSWORD'),
            '-t' => $this->playgroundPath('downloads'),
            '-m' => 10,
        ];

        $this->runApplicationAndAssertResult($options, $expectedOutput, $expectedStatus);
    }

    /** @test */
    public function it_can_list_content_of_a_subdirectory()
    {
        $expectedOutput = <<<RES
+--------------------------+---------------+--------+
| Folder                   | Name          | Size   |
+--------------------------+---------------+--------+
| /Фотографии/Отпуск 2020/ | Берег.jpg     | 707 KB |
| /Фотографии/Отпуск 2020/ | На отдыхе.jpg | 1.7 MB |
+--------------------------+---------------+--------+
RES;

        $expectedStatus = 0;

        $options = [
            '-n' => null,
            '-l' => getenv('MAILRU_LOGIN'),
            '-p' => getenv('MAILRU_PASSWORD'),
            '-t' => $this->playgroundPath('downloads'),
            '-s' => '/Фотографии/Отпуск 2020',
            '-m' => 10,
        ];

        $this->runApplicationAndAssertResult($options, $expectedOutput, $expectedStatus);
    }
}
