<?php

namespace Restfull\Security;

use Firebase\JWT\JWT;
use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class JsonWebTokens
 * @package Restfull\Security
 */
class JsonWebTokens
{

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $key = 'logar';

    /**
     * @var JWT
     */
    private $tokenJW;

    /**
     * @param Request $request
     * @param Response $response
     * @return array
     */
    public function checkAPI(Request $request, Response $response)
    {
        if ($request->bolleanApi()) {
            $this->tokenJW = new JWT();
            $this->decrypt(isset(getallheaders()['Authorization']) ? getallheaders()['Authorization'] : null);
            if (!$this->autentication($this->data)) {
                $response->setHttpCode(200);
                $request->bootstrap['security']->setSalt($this->data['token']);
            } else {
                $response->setHttpCode(401);
                $response->body(
                        json_encode("Não está autorizado a usar a api.", JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
            }
        }
        return ['request' => $request, 'response' => $response];
    }

    /**
     * @param string|null $token
     * @return JsonWebTokens
     */
    public function decrypt(string $token = null): JsonWebTokens
    {
        if (!is_null($token)) {
            $data = $this->tokenJW->decode(substr($token, strlen("Bearer ")), $this->key, ['HS256']);
            $this->data = $data['access'];
        }
        return $this;
    }

    /**
     * @return bool
     * @throws Exceptions
     */
    public function autentication(): bool
    {
        $instance = new Instances();
        if (isset($this->data['user'])) {
            $result = $instance->resolveClass(
                    $instance->namespaceClass(
                            "%s" . DS_REVERSE . "%s" . DS_REVERSE . "App%s",
                            [substr(ROOT_APP, -4, -1), MVC[2]['app'], MVC[2]['app']]
                    )
            )->tableRegistry(
                    ['main' => [['table' => 'users']]],
                    ['fields' => ['user', 'pass'], 'conditions' => ['user' => $this->data['user']]]
            )->excuteQuery("all", false, ['user', 'pass']);
            if (isset($result['user']) && ($result['pass'] == $this->data['pass'])) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * @param array $data
     * @return string
     */
    public function encrypt(array $data): string
    {
        $data = [
                'iat' => strtotime(date("Y-m-d H:i:s")),
                'exp' => strtotime(date("Y-m-d H:i:s", strtotime("+1 day"))),
                'access' => $data
        ];
        return $this->tokenJW->encode($data, $this->key);
    }
}
