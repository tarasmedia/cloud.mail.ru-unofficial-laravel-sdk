<?php
namespace UAM\Cloud;

use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use UAM\Exceptions\Cloud\AccessDenied\AuthExpired;
use UAM\Exceptions\Cloud\AccessDenied\InvalidCredentials;
use UAM\Exceptions\Cloud\AccessDenied\InvalidToken;
use UAM\Exceptions\Cloud\AccessDenied\NoSdcCookie;
use UAM\Exceptions\Cloud\AccessDenied\Unspecified;
use UAM\Exceptions\Cloud\InvalidResponse\EmptyBody;
use UAM\Exceptions\Cloud\InvalidResponse\ParsingError;
use UAM\Exceptions\Cloud\InvalidResponse\ResponseCodeNotOk;
use UAM\Exceptions\Cloud\MisconfiguredHttpClient\DisabledCookies;

/**
 * Class CloudAPI
 * @package UAM
 */
class API
{
    /**
     * @var string
     */
    const AUTH_DOMAIN = 'https://auth.mail.ru';

    /**
     * @var string
     */
    const CLOUD_DOMAIN = 'https://cloud.mail.ru';

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $password;

    /**
     * @var Client
     */
    private $http;

    /**
     * @var DOMDocument
     */
    private $dom;

    /**
     * @var string
     */
    private $token;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param string $login
     * @param string $password
     * @param Client $http
     * @param DOMDocument $dom
     * @param LoggerInterface $logger
     * @throws DisabledCookies
     */
    public function __construct($login, $password, Client $http, DOMDocument $dom, LoggerInterface $logger = null)
    {
        $cookiesAreDisabled = !$http->getConfig('cookies');

        if ($cookiesAreDisabled) {
            throw new DisabledCookies;
        }

        $this->login = $login;
        $this->password = $password;
        $this->http = $http;
        $this->dom = $dom;
        $this->logger = $logger;
    }

    /**
     * @param string $folder
     * @return File[]
     */
    public function getFiles($folder = '/')
    {
        $response = $this->executeApiMethod(
            'folder',
            [
                'home' => $folder,
            ]
        );

        $files = [];

        foreach ($response['list'] as $item) {
            if ($item['type'] === 'folder') {
                $files = array_merge($files, $this->getFiles($item['home']));
            }

            if ($item['type'] === 'file') {
                $files[] = new File($item['name'], $item['size'], $item['home']);
            }
        }

        return $files;
    }

    /**
     * @param $method
     * @param array $params
     * @return array
     */
    private function executeApiMethod($method, array $params = [])
    {
        if ($this->methodRequiresToken($method)) {
            $params['token'] = $this->getToken();
        }

        $url = static::CLOUD_DOMAIN . '/api/v2/' . $method;

        $content = $this->http
            ->get(
                $url,
                [
                    'query' => $params,
                    'http_errors' => false,
                ]
            )
            ->getBody();

        $response = json_decode((string)$content, true);

        $this->validateApiResponse($response);

        return $response['body'];
    }

    /**
     * @param $method
     * @return bool
     */
    private function methodRequiresToken($method)
    {
        return $method !== 'tokens/csrf';
    }

    /**
     * @return string
     */
    private function getToken()
    {
        if ($this->shouldRequestToken()) {
            $this->token = $this->requestToken();
        }

        return $this->token;
    }

    /**
     * @return bool
     */
    private function shouldRequestToken()
    {
        return is_null($this->token);
    }

    /**
     * @return string
     */
    private function requestToken()
    {
        $this->auth();
        $this->ensureSdcCookie();

        $response = $this->executeApiMethod('tokens/csrf');

        return $response['token'];
    }

    /**
     * @throws InvalidCredentials
     */
    private function auth()
    {
        $expectedTitle = sprintf('Входящие - %s - Почта Mail.Ru', $this->login);

        $authResponse = $this->http->post(
            static::AUTH_DOMAIN . '/cgi-bin/auth',
            [
                'form_params' => [
                    'Login' => $this->login,
                    'Password' => $this->password,
                ]
            ]
        );

        try {
            // http://php.net/manual/en/domdocument.loadhtml.php#95463
            libxml_use_internal_errors(true);

            $this->dom->loadHTML($authResponse->getBody());

            $actualTitle = $this->dom->getElementsByTagName('title')->item(0)->textContent;
        } catch (\Exception $e) {
            throw new InvalidCredentials;
        }

        if ($actualTitle !== $expectedTitle) {
            throw new InvalidCredentials;
        }
    }

    /**
     *
     */
    private function ensureSdcCookie()
    {
        $this->http->get(
            static::AUTH_DOMAIN . '/sdc',
            [
                'query' => [
                    'from' => static::CLOUD_DOMAIN . '/home',
                ],
            ]
        );
    }

    /**
     * @param mixed $response
     * @throws AuthExpired
     * @throws EmptyBody
     * @throws InvalidToken
     * @throws NoSdcCookie
     * @throws ParsingError
     * @throws ResponseCodeNotOk
     * @throws Unspecified
     */
    private function validateApiResponse($response)
    {
        if (is_null($response) && json_last_error() !== JSON_ERROR_NONE) {
            throw new ParsingError;
        }

        if (!is_array($response)) {
            $response = [];
        }

        $status = array_key_exists('status', $response) ? (int)$response['status'] : 0;

        if (!array_key_exists('body', $response)) {
            throw new EmptyBody;
        }

        $body = $response['body'];

        if ($status === 403) {
            switch ($body) {
                case 'user':
                    throw new AuthExpired;

                case 'nosdc':
                    throw new NoSdcCookie;

                case 'token':
                    throw new InvalidToken;

                default:
                    throw new Unspecified($body);
            }
        }

        if ($status !== 200) {
            throw new ResponseCodeNotOk($status);
        }
    }

    public function download(File $file, $destination, $rewrite = false)
    {
        $originalPath = $destination . '/' . $file->getPath();

        if (!$rewrite && file_exists($originalPath) && filesize($originalPath) === $file->getSize()) {
            return;
        }

        $temporaryFilePath = $this->getTemporaryFilePath($file, $destination);

        $this->ensureDirectoryStructureFor($temporaryFilePath);

        $this->http->get(
            $this->getDownloadUrl($file),
            [
                RequestOptions::SINK => $temporaryFilePath
            ]
        );

        rename($temporaryFilePath, $originalPath);
    }

    /**
     * @param File $file
     * @param $destination
     * @return string
     */
    protected function getTemporaryFilePath(File $file, $destination)
    {
        $temporarySuffix = '.part';

        return $destination . '/' . $file->getPath() . $temporarySuffix;
    }

    private function ensureDirectoryStructureFor($path)
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    /**
     * @param File $file
     * @return string
     */
    protected function getDownloadUrl(File $file)
    {
        $node = $this->getDownloadNode();

        return $node . $file->getPath();
    }

    private function getDownloadNode()
    {
        $response = $this->executeApiMethod('dispatcher');

        $nodes = array_column($response['get'], 'url');

        return $nodes[mt_rand(0, count($nodes) - 1)];
    }
}
