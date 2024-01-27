<?php

declare(strict_types=1);

namespace Restfull\Executing;

use Firebase\JWT\JWT;
use Restfull\Container\Instances;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
class WebService
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
    private $tokenJWT;

    /**
     * @var Instances
     */
    private $instance;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param Instances $instance
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Instances $instance, Request $request, Response $response)
    {
        if ($request->bolleanApi()) {
            $this->tokenJWT = $instance->resoverClass('Firebase' . DS_REVERVE . 'JWT' . DS_REVERSE . 'JWT');
        }
        $this->instance = $instance;
        $this->response = $response;
        $this->request = $request;
        return $this;
    }

    /**
     * @return array
     */
    public function checkAPI(): array
    {
        if ($this->tokenJWT instanceof JWT) {
            $this->decrypt(isset(getallheaders()['Authorization']) ? getallheaders()['Authorization'] : null);
            if (!$this->autentication($instance, $this->data)) {
                $response->setHttpCode(200);
                $request->bootstrap('security')->setSalt($this->data['token']);
            } else {
                $response->setHttpCode(401);
                $response->body(
                    json_encode(
                        "Não está autorizado a usar a api.",
                        JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    )
                );
            }
        }
        return ['request' => $request, 'response' => $response];
    }

    /**
     * @param string|null $token
     *
     * @return JsonWebTokens
     */
    public function decrypt(string $token = null): JsonWebTokens
    {
        if (!is_null($token)) {
            $data = $this->tokenJWT->decode(substr($token, strlen("Bearer ")), $this->key, ['HS256']);
            $this->data = $data['access'];
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function autentication(Instances $instance): bool
    {
        if (isset($this->data['user'])) {
            $options = ['fields' => ['user', 'pass'], 'conditions' => ['user' => $this->data['user']]];
            $result = $instance->resolveClass(
                ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                    ROOT_NAMESPACE[1]
                )] . DS_REVERSE . ROOT_NAMESPACE[1] . MVC[2][strtolower(ROOT_NAMESPACE[1])]
            )->tableRegistry(['main' => [['table' => 'users']]], $options)->excuteQuery(
                "all",
                false,
                $options['fields']
            );
            if (isset($result['user']) && ($result['pass'] === $this->data['pass'])) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function encrypt(array $data): string
    {
        $data = [
            'iat' => strtotime(date("Y-m-d H:i:s")),
            'exp' => strtotime(date("Y-m-d H:i:s", strtotime("+1 day"))),
            'access' => $data
        ];
        return $this->tokenJWT->encode($data, $this->key);
    }
}
