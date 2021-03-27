<?php
namespace UAM\Tests\Unit\Cloud;

use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use UAM\Cloud\API as CloudAPI;
use UAM\Cloud\File;
use UAM\Exceptions\Cloud\AccessDenied\AuthExpired;
use UAM\Exceptions\Cloud\AccessDenied\InvalidCredentials;
use UAM\Exceptions\Cloud\AccessDenied\InvalidToken;
use UAM\Exceptions\Cloud\AccessDenied\NoSdcCookie;
use UAM\Exceptions\Cloud\AccessDenied\Unspecified;
use UAM\Exceptions\Cloud\InvalidResponse\EmptyBody;
use UAM\Exceptions\Cloud\InvalidResponse\ParsingError;
use UAM\Exceptions\Cloud\InvalidResponse\ResponseCodeNotOk;
use UAM\Exceptions\Cloud\MisconfiguredHttpClient\DisabledCookies;

class ApiTest extends ApiTestCase
{
    /** @test */
    public function it_throws_exception_if_cookies_are_not_enabled_for_http_client()
    {
        $this->expectException(DisabledCookies::class);

        new CloudAPI(
            $this->login,
            $this->password,
            new Client(['cookies' => false]),
            new DOMDocument()
        );
    }

    /** @test */
    public function it_returns_a_list_of_files_in_a_specific_folder()
    {
        $folder = '/Фотографии/Отпуск 2020/Видео';

        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockSuccessTokenResponse(),
            $this->mockSuccessFolderResponse($folder),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $expectedList = [
            new File('Полет.mp4', 486354507, '/Фотографии/Отпуск 2020/Видео/Полет.mp4'),
        ];

        $actualList = $cloud->getFiles($folder);

        $this->assertEquals($expectedList, $actualList);
    }

    /** @test */
    public function it_returns_a_list_of_files_in_a_specific_folder_and_its_subfolders()
    {
        $folder = '/Фотографии/Отпуск 2020';

        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockSuccessTokenResponse(),
            $this->mockSuccessFolderResponse($folder),
            $this->mockSuccessFolderResponse($folder . '/Видео'),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $expectedList = [
            new File('Полет.mp4', 486354507, '/Фотографии/Отпуск 2020/Видео/Полет.mp4'),
            new File('Берег.jpg', 723662, '/Фотографии/Отпуск 2020/Берег.jpg'),
            new File('На отдыхе.jpg', 1775137, '/Фотографии/Отпуск 2020/На отдыхе.jpg'),
        ];

        $actualList = $cloud->getFiles($folder);

        $this->assertEquals($expectedList, $actualList);
    }

    /** @test */
    public function it_makes_necessary_http_requests_in_a_specific_order()
    {
        $folders = [
            '/',
            '/Фотографии',
            '/Фотографии/Отпуск 2020',
            '/Фотографии/Отпуск 2020/Видео',
        ];

        $responses = [
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockSuccessTokenResponse(),
        ];

        foreach ($folders as $folder) {
            $responses[] = $this->mockSuccessFolderResponse($folder);
        }

        $mock = new MockHandler($responses);

        $container = [];

        $client = $this->makeHttpClient($mock, $container);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $cloud->getFiles($folders[0]);

        $expectedRequests = [
            [
                'method' => 'POST',
                'uri' => sprintf(
                    '%s/cgi-bin/auth',
                    CloudAPI::AUTH_DOMAIN
                ),
                'body' => sprintf(
                    'Login=%s&Password=%s',
                    rawurlencode($this->login),
                    rawurlencode($this->password)
                ),
            ],
            [
                'method' => 'GET',
                'uri' => sprintf(
                    '%s/sdc?from=%s',
                    CloudAPI::AUTH_DOMAIN,
                    rawurlencode(CloudAPI::CLOUD_DOMAIN . '/home')
                ),
                'body' => '',
            ],
            [
                'method' => 'GET',
                'uri' => sprintf(
                    '%s/api/v2/tokens/csrf',
                    CloudAPI::CLOUD_DOMAIN
                ),
                'body' => '',
            ],
        ];

        foreach ($folders as $folder) {
            $expectedRequests[] = [
                'method' => 'GET',
                'uri' => sprintf(
                    '%s/api/v2/folder?home=%s&token=%s',
                    CloudAPI::CLOUD_DOMAIN,
                    rawurlencode($folder),
                    $this->token
                ),
                'body' => '',
            ];
        }

        foreach ($container as $index => $transaction) {
            /** @var \GuzzleHttp\Psr7\Request $request */
            $request = $transaction['request'];

            $actualRequest = [
                'method' => $request->getMethod(),
                'uri' => (string)$request->getUri(),
                'body' => (string)$request->getBody(),
            ];

            $expectedRequest = $expectedRequests[$index];

            $this->assertEquals($expectedRequest['method'], $actualRequest['method']);
            $this->assertEquals($expectedRequest['uri'], $actualRequest['uri']);
            $this->assertEquals($expectedRequest['body'], $actualRequest['body']);
        }
    }

    /** @test */
    public function it_throws_exception_when_authorization_fails()
    {
        $mock = new MockHandler([
            $this->mockFailedAuthResponse(),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $this->expectException(InvalidCredentials::class);

        $cloud->getFiles();
    }

    /** @test */
    public function it_throws_exception_when_authorization_expires()
    {
        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockApiResponse(403, 'user'),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $this->expectException(AuthExpired::class);

        $cloud->getFiles();
    }

    /** @test */
    public function it_throws_exception_when_sdc_cookie_was_not_sent()
    {
        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockApiResponse(403, 'nosdc'),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $this->expectException(NoSdcCookie::class);

        $cloud->getFiles();
    }

    /** @test */
    public function it_throws_exception_when_request_was_made_with_invalid_token()
    {
        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockSuccessTokenResponse(),
            $this->mockApiResponse(403, 'token'),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $this->expectException(InvalidToken::class);

        $cloud->getFiles();
    }

    /** @test */
    public function it_throws_exception_when_access_was_denied_for_unspecified_reason()
    {
        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockSuccessTokenResponse(),
            $this->mockApiResponse(403, 'unspecified'),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $this->expectException(Unspecified::class);

        $cloud->getFiles();
    }

    /** @test */
    public function it_throws_exception_when_response_does_not_have_a_body_field()
    {
        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockSuccessTokenResponse(),
            $this->mockApiResponse(200, null),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $this->expectException(EmptyBody::class);

        $cloud->getFiles();
    }

    /** @test */
    public function it_throws_exception_when_response_is_not_a_valid_json()
    {
        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockSuccessTokenResponse(),
            $this->mockResponse(200, '<h1>Not A Valid JSON</h1>'),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $this->expectException(ParsingError::class);

        $cloud->getFiles();
    }

    /** @test */
    public function it_throws_exception_when_response_code_is_not_200()
    {
        $mock = new MockHandler([
            $this->mockSuccessAuthResponse(),
            $this->mockSuccessSdcResponse(),
            $this->mockSuccessTokenResponse(),
            $this->mockApiResponse(404, 'some data'),
        ]);

        $client = $this->makeHttpClient($mock, $container = []);

        $cloud = new CloudAPI(
            $this->login,
            $this->password,
            $client,
            new DOMDocument()
        );

        $this->expectException(ResponseCodeNotOk::class);

        $cloud->getFiles();
    }
}
