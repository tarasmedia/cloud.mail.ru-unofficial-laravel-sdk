<?php
namespace UAM\Tests\Unit\Cloud;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use UAM\Tests\TestCase;

class ApiTestCase extends TestCase
{
    /** @var string */
    protected $password;

    /** @var string */
    protected $login;

    /** @var string */
    protected $token = 'awsE5Zm3CHu3Ut2z3QhkMkg4cgWoy3xu';

    public function setUp()
    {
        parent::setUp();

        $this->login = getenv('MAILRU_LOGIN');
        $this->password = getenv('MAILRU_PASSWORD');
    }

    /**
     * @return Response
     */
    protected function mockSuccessAuthResponse()
    {
        return new Response(200, [], $this->getSuccessAuthResponseBody());
    }

    /**
     * @return string
     */
    protected function getSuccessAuthResponseBody()
    {
        $template = <<<HTML
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="utf-8"/>
                    <title>Входящие - %s - Почта Mail.Ru</title>
                </head>
                <body></body>
            </html>
HTML;

        return sprintf($template, $this->login);
    }

    /**
     * @return Response
     */
    protected function mockSuccessSdcResponse()
    {
        return new Response;
    }

    /**
     * @return Response
     */
    protected function mockSuccessTokenResponse()
    {
        return new Response(200, [], $this->getSuccessTokenResponseBody());
    }

    /**
     * @return array
     */
    protected function getSuccessTokenResponseBody()
    {
        $data = [
            'status' => 200,
            'body' => [
                'token' => $this->token,
            ],
        ];

        return json_encode($data);
    }

    /**
     * @param string $folder
     * @return Response
     */
    protected function mockSuccessFolderResponse($folder)
    {
        return new Response(200, [], $this->getSuccessFolderResponseBody($folder));
    }

    /**
     * @param $folder
     * @return array
     */
    protected function getSuccessFolderResponseBody($folder)
    {
        $folders = [
            '/' => [
                [
                    'count' => [
                        'folders' => 1,
                        'files' => 3,
                    ],
                    'tree' => '363831373562653330303030',
                    'name' => 'Фотографии',
                    'grev' => 17,
                    'size' => 492119223,
                    'kind' => 'folder',
                    'rev' => 16,
                    'type' => 'folder',
                    'home' => '/Фотографии',
                ],
                [
                    'mtime' => 1456774311,
                    'virus_scan' => 'pass',
                    'name' => 'Полет.mp4',
                    'size' => 486354507,
                    'hash' => 'C2AD142BDF1E4F9FD50E06026BCA578198BFC36E',
                    'kind' => 'file',
                    'type' => 'file',
                    'home' => '/Полет.mp4',
                ],
            ],
            '/Фотографии' => [
                [
                    'count' => [
                        'folders' => 1,
                        'files' => 2,
                    ],
                    'tree' => '363831373562653330303030',
                    'name' => 'Отпуск 2020',
                    'grev' => 17,
                    'size' => 488853306,
                    'kind' => 'folder',
                    'rev' => 16,
                    'type' => 'folder',
                    'home' => '/Фотографии/Отпуск 2020',
                ],
                [
                    'mtime' => 1456774311,
                    'virus_scan' => 'pass',
                    'name' => 'Горное озеро.jpg',
                    'size' => 1028940,
                    'hash' => 'FC2wBFEBBF99E1B9518BED1E3B0FDBCF934276A4A',
                    'kind' => 'file',
                    'type' => 'file',
                    'home' => '/Фотографии/Горное озеро.jpg',
                ],
                [
                    'mtime' => 1456774311,
                    'virus_scan' => 'pass',
                    'name' => 'Долина реки.jpg',
                    'size' => 1297836,
                    'hash' => 'F6EFA34BFFD20A300F440B9C88245DA048DEEE89',
                    'kind' => 'file',
                    'type' => 'file',
                    'home' => '/Фотографии/Долина реки.jpg',
                ],
                [
                    'mtime' => 1456774311,
                    'virus_scan' => 'pass',
                    'name' => 'Чистая вода.jpg',
                    'size' => 939141,
                    'hash' => '8B57D61B651F8AF2696E756CB310F320DC7838B9',
                    'kind' => 'file',
                    'type' => 'file',
                    'home' => '/Фотографии/Чистая вода.jpg',
                ],
            ],
            '/Фотографии/Отпуск 2020' => [
                [
                    'count' => [
                        'folders' => 0,
                        'files' => 1,
                    ],
                    'tree' => '363831373562653331303030',
                    'name' => 'Видео',
                    'grev' => 17,
                    'size' => 486354507,
                    'kind' => 'shared',
                    'rev' => 16,
                    'type' => 'folder',
                    'home' => '/Фотографии/Отпуск 2020/Видео',
                ],
                [
                    'mtime' => 1456774311,
                    'virus_scan' => 'pass',
                    'name' => 'Берег.jpg',
                    'size' => 723662,
                    'hash' => '9101AB34F2348576EE9225AECF4DC17674D1EB17',
                    'kind' => 'file',
                    'weblink' => 'AtKb/1yr7VtVV5',
                    'type' => 'file',
                    'home' => '/Фотографии/Отпуск 2020/Берег.jpg',
                ],
                [
                    'mtime' => 1456774311,
                    'virus_scan' => 'pass',
                    'name' => 'На отдыхе.jpg',
                    'size' => 1775137,
                    'hash' => '1D1EC8CBB4A1128148108D5932EC76C42256F543',
                    'kind' => 'file',
                    'weblink' => 'KLr4/zRhptT2Kz',
                    'type' => 'file',
                    'home' => '/Фотографии/Отпуск 2020/На отдыхе.jpg',
                ],
            ],
            '/Фотографии/Отпуск 2020/Видео' => [
                [
                    'mtime' => 1456817065,
                    'virus_scan' => 'pass',
                    'name' => 'Полет.mp4',
                    'size' => 486354507,
                    'hash' => 'C2AD142BDF1E4F9FD50E06026BCA578198BFC36E',
                    'kind' => 'file',
                    'type' => 'file',
                    'home' => '/Фотографии/Отпуск 2020/Видео/Полет.mp4',
                ],
            ]
        ];

        $data = [
            'status' => 200,
            'body' => [
                'list' => $folders[$folder],
            ],
        ];

        return json_encode($data);
    }

    /**
     * @param $mock
     * @param array $container
     * @return Client
     */
    protected function makeHttpClient($mock, array &$container)
    {
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($container));

        return new Client([
            'handler' => $handler,
            'cookies' => true,
        ]);
    }

    protected function mockFailedAuthResponse()
    {
        return new Response(200, [], $this->getFailedAuthResponseBody());
    }

    protected function getFailedAuthResponseBody()
    {
        $template = <<<HTML
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="utf-8"/>
                    <title>Вход - Почта Mail.Ru</title>
                </head>
                <body></body>
            </html>
HTML;

        return sprintf($template, $this->login);
    }

    protected function mockApiResponse($status, $body)
    {
        return new Response($status, [], $this->getApiResponseBody($status, $body));
    }

    protected function getApiResponseBody($status, $body)
    {
        $data = [
            'status' => $status,
        ];

        if (!is_null($body)) {
            $data['body'] = $body;
        }

        return json_encode($data);
    }

    /**
     * @param int $status
     * @param string $body
     * @return Response
     */
    protected function mockResponse($status, $body)
    {
        return new Response($status, [], $body);
    }
}
