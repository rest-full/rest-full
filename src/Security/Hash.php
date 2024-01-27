<?php

declare(strict_types=1);

namespace Restfull\Security;

use App\Model\AppModel;
use Restfull\Authentication\Auth;
use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
class Hash
{

    /**
     * @var string
     */
    public $salt = '';

    /**
     * @var string
     */
    private $decrypting = '0';

    /**
     * @var int
     */
    private $numberSalt = 32;

    /**
     * @var array
     */
    private $keyEncrypt = ['key' => 'd5cf705155810666f7f3', 'iv' => 'TkRUY3ExMEVuVGpi', 'level' => 3];

    /**
     * @var auth
     */
    private $auth;

    /**
     * @var array
     */
    private $http = [];

    /**
     * @var string
     */
    private $hash = '';

    /**
     * @var AppModel
     */
    private $table;

    /**
     * @var bool
     */
    private $alfanumerico = true;

    /**
     * @var int
     */
    private $idUser = 0;

    /**
     * @var bool
     */
    private $validated = false;

    /**
     * @var string
     */
    private $ip = '';

    /**
     * @var int
     */
    private $length = 7;

    /**
     * @var Instances
     */
    private $instance;

    /**
     * @var bool
     */
    private $scanDB = false;

    /**
     * @param Auth $auth
     * @param int $number
     * @param string $salt
     */
    public function __construct(Auth $auth, Instances $instance, int $number, string $salt)
    {
        $this->auth = $auth;
        if ($this->numberSalt != $number) {
            $this->numberSalt = $number;
        }
        $this->salt = $salt;
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return int
     */
    public function levelEncrypt(): int
    {
        return $this->keyEncrypt['level'];
    }

    /**
     * @param int $level
     * @param bool $changeablePassword
     * @param array $changeRequestEncryptionKeys
     *
     * @return Hash
     */
    public function changeConfig(
        int $level,
        bool $changeablePassword = false,
        array $changeRequestEncryptionKeys = []
    ): Hash {
        if (!$this->auth->check('hash')) {
            if ($this->keyEncrypt['level'] != $level) {
                $this->keyEncrypt['level'] = $level;
                $this->auth->write('hash', ['level' => $level]);
            }
        } else {
            $levelHash = $this->auth->getData('hash');
            if ($levelHash['level'] != $this->keyEncrypt['level']) {
                $this->keyEncrypt['level'] = $levelHash['level'];
            } elseif ($levelHash['level'] != $level) {
                $this->keyEncrypt['level'] = $level;
                $this->auth->write('hash', ['level' => $level]);
            }
        }
        if ($changeablePassword) {
            $this->alfanumerico = !$this->alfanumerico;
            if (!$this->alfanumerico) {
                $this->keyEncrypt = [
                    'key' => 'd5cf705155810666f7f3',
                    'iv' => 'TkRUY3ExMEVuVGpi',
                    'level' => $this->keyEncrypt['level']
                ];
            } else {
                list($this->keyEncrypt['key'], $this->keyEncrypt['iv'], $this->keyEncrypt['level']) = explode(
                    '|',
                    base64_decode($this->auth->getData('keyEncrypt')[$this->ip][$this->idUser]['cryption'])
                );
            }
        }
        if (count($changeRequestEncryptionKeys) != 0) {
            if (count($changeRequestEncryptionKeys) != 3) {
                throw new Exceptions(
                    'Any of the keys are missing: general, internal or linkInternalWithExternalAccess.', 404
                );
            }
            foreach ($changeRequestEncryptionKeys as $key => $value) {
                $this->http['request']->encryptionKeys[$key] = $value;
            }
            $datas = $this->auth->getData('keyEncrypt')[$this->ip][$this->idUser];
            $datas['valids'] = $this->http['request']->encryptionKeys;
            $this->auth->write('keyEncrypt', [$this->ip => [$this->idUser => $datas]]);
        }
        return $this;
    }

    /**
     * @param Request|null $request
     * @param Response|null $response
     *
     * @return Hash
     */
    public function http(Request $request = null, Response $response = null): Hash
    {
        $this->http = ['request' => $request, 'response' => $response];
        $this->table = $this->instance->resolveClass(
            ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                ROOT_NAMESPACE[1]
            )] . DS_REVERSE . ROOT_NAMESPACE[1] . MVC[2][strtolower(ROOT_NAMESPACE[1])],
            ['instance' => $this->instance, 'http' => $this->http]
        );
        return $this;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function encrypt(string $path): string
    {
        if ($this->keyEncrypt['level'] >= 1) {
            if ($this->alfanumerico) {
                $caracters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                $crypt = '';
                for ($a = 0; $a < strlen($path); $a++) {
                    $alfanumeric = $caracters[rand(1, 35)];
                    $crypt .= stripos($crypt, $alfanumeric) !== false ? strtoupper($alfanumeric) : $alfanumeric;
                    $crypt .= $path[$a];
                }
            } else {
                $crypt = $path;
            }
            if ($this->keyEncrypt['level'] >= 2) {
                $crypt = openssl_encrypt($crypt, 'AES-128-CBC', $this->keyEncrypt['key'], 0, $this->keyEncrypt['iv']);
                $crypt = str_replace(DS, '_', str_replace('+', '|', $crypt));
                if ($this->keyEncrypt['level'] === 3) {
                    $crypt = str_replace(DS, '_', base64_encode($crypt));
                }
            }
            return $crypt;
        }
        return $path;
    }

    /**
     * @return bool
     */
    public function alfanumero(): bool
    {
        return $this->alfanumerico;
    }

    /**
     * @param string $url
     * @param int $id
     * @param string $methodUsedUrl
     * @return string
     * @throws Exceptions
     */
    public function shortenDB(string $url, array $options): string
    {
        if ($this->scanDB) {
            $sets = explode('|', "abcdfghjkmnpqrstvwxyz|ABCDFGHJKLMNPQRSTVWXYZ|0123456789");
            $all = '';
            $randString = '';
            foreach ($sets as $set) {
                $newUrl .= $set[array_rand(str_split($set))];
                $all .= $set;
            }
            $all = str_split($all);
            for ($i = 0; $i < $this->length - count($sets); $i++) {
                $newUrl .= $all[array_rand($all)];
            }
            $newUrl = str_shuffle($newUrl);
            $this->table->executionTypeForQuery('create');
            $options['shorten'] = $newUrl;
            $options['expanse'] = $url;
            $options['ip'] = $this->ip;
            $options['datetime'] = date('Y-m-d H:i:s');
            $this->table->executeTheQueryAfterItIsAssembled(
                [['fields' => array_keys($options), 'conditions' => $options, 'table' => ['table' => 'shortenurl']]]
            );
            return $newUrl;
        }
        return $url;
    }

    /**
     * @param string $url
     * @return string
     * @throws Exceptions
     */
    public function expanseDB(string $url): string
    {
        $this->encryptionKeysRequest();
        $resultInfoTable = $this->table->scannigTheMetadataOfTheseTables(
            ['main' => ['table' => 'shortenurl'], 'join' => ['shortenurl' => [['table' => 'securities']]]]
        );
        if (count($resultInfoTable) > 0) {
            $this->table = $this->table->startClassTableRegistory($resultInfoTable, 'shortenurl');
            $this->scanDB = !$this->scanDB;
            list($count, $result) = $this->openssl($url);
            $this->checkUrlNotEncrypt($result->normalsize);
            $conditions = ['securities.ip & ' => $this->ip];
            if ($count > 0) {
                $truncate = false;
                $this->http['request']->shorten = true;
                $ajax = false;
                if ($this->http['request']->ajax && ((strlen(
                                $result->methodused
                            ) == 4 && $result->methodused == 'ajax') || stripos(
                            $result->methodused,
                            'ajax'
                        ) !== false)) {
                    $ajax = !$ajax;
                }
                $this->table->executionTypeForQuery('update');
                foreach (['nao', 'sim'] as $value) {
                    $this->table->executeTheQueryAfterItIsAssembled(
                        [
                            [
                                'fields' => ['current' => $value],
                                'conditions' => ['shorten' . ($value === 'nao' ? ' +- ' : ' & ') => $url],
                                'table' => ['table' => 'shortenurl']
                            ]
                        ]
                    );
                }
                if (strtotime(date('Y-m-d H:i:s') . ' - 15 minutes') < strtotime(date('Y-m-d H:i:s'))) {
                    $truncate = !$truncate;
                    if ($ajax) {
                        $options['conditions']['methodused & '] = $result->methodused;
                    }
                }
                $this->deleteByTimeOrIdentifier(
                    array_merge($options, ['conditions' => $conditions]) ?? ['conditions' => $conditions],
                    $truncate,
                    $this->idUser
                );
                return $this->valideDecrypt()->decrypt();
            }
            $this->deleteByTimeOrIdentifier(['conditions' => $conditions], false, $this->idUser);
            return $this->valideDecrypt()->decrypt();
        }
        return $url;
    }

    /**
     * @return Hash
     */
    private function encryptionKeysRequest(): Hash
    {
        if (count($this->http['request']->encryptionKeys) == 0) {
            $this->http['request']->encryptionKeys = [
                'general' => false,
                'internal' => true,
                'linkInternalWithExternalAccess' => false,
                'status' => 'desconnect'
            ];
        }
        return $this;
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws Exceptions
     */
    private function openssl(string $uri): array
    {
        $this->ip = $this->http['request']->server['REMOTE_ADDR'];
        $this->idUser = $this->auth->check('user') ? $this->auth->getData()['id'] : 0;
        $options = [
            'fields' => ['methodused', 'normalsize', 'status', 'cryption'],
            'join' => [
                [
                    'table' => 'securities',
                    'type' => 'inner',
                    'conditions' => 'securities.id = shortenurl.idSecurity'
                ],
                ['table' => 'users', 'type' => 'inner', 'conditions' => 'users.id = shortenurl.idUser']
            ],
            'conditions' => ['securities.ip & ' => $this->ip],
            'table' => ['table' => 'shortenurl']
        ];
        if (!empty($uri)) {
            $options['conditions'] = array_merge($options['conditions'], ['shortenurl.shorten & ' => $uri]);
        }
        if ($this->idUser !== 0) {
            $options['conditions'] = array_merge($options['conditions'], ['shortenurl.idUser & ' => $this->idUser]);
        }
        $this->table->executionTypeForQuery('first');
        $result = $this->table->executeTheQueryAfterItIsAssembled([$options]);
        $count = 2;
        if (!isset($result->cryption)) {
            $count = 0;
            $options = [
                'fields' => ['cryption'],
                'conditions' => ['ip & ' => $this->ip],
                'table' => ['table' => 'securities']
            ];
            $registory = $this->table->startClassTableRegistory(
                $this->table->scannigTheMetadataOfTheseTables(
                    ['main' => ['table' => 'securities'], 'join' => ['securities' => [['table' => null]]]]
                ),
                'securities'
            );
            $result->cryption = $registory->executeTheQueryAfterItIsAssembled([$options])->cryption;
            if (is_null($result->cryption) || empty($result->cryption)) {
                $this->keyEncrypt['key'] = substr(bin2hex(random_bytes($this->numberSalt)), 0, 16);
                $this->keyEncrypt['iv'] = substr(
                    base64_encode(crypt((string)openssl_cipher_iv_length('AES-128-CBC'), $this->salt)),
                    0,
                    16
                );
                $this->keyEncrypt['level'] = 3;
                $post = [
                    'ip' => $this->ip,
                    'cryption' => base64_encode(
                        $this->keyEncrypt['key'] . '|' . $this->keyEncrypt['iv'] . '|' . $this->keyEncrypt['level']
                    ),
                    'status' => 'desconnect'
                ];
                $this->table->executionTypeForQuery('create');
                $this->table->executeTheQueryAfterItIsAssembled(
                    [['fields' => array_keys($post), 'conditions' => $post, 'table' => ['table' => 'securities']]]
                );
                $keysEncryption = [
                    'valids' => $this->http['request']->encryptionKeys,
                    'cryption' => $post['cryption'],
                    'status' => 'desconnect',
                    'strtotime' => strtotime(date('Y-m-d H:i:s'))
                ];
                $result->cryption = $post['cryption'];
            } else {
                $keysEncryption = [
                    'valids' => $this->http['request']->encryptionKeys,
                    'cryption' => $result->cryption,
                    'status' => 'desconnect',
                    'strtotime' => strtotime(date('Y-m-d H:i:s'))
                ];
            }
            $result->methodused = 'normal';
            $result->normalsize = !empty($uri) ? $uri : '';
        }
        if ($this->auth->check('keysEncryption')) {
            $keysEncryptionAuth = $this->auth->getData('keysEncryption');
            if (array_key_exists($this->idUser, $keysEncryptionAuth[$this->ip]) !== false) {
                $keysEncryption = $keysEncryptionAuth[$this->ip][$this->idUser];
            }
            if ($keysEncryption['status'] === 'connect' && $this->idUser === 0) {
                $keysEncryption['cryption'] = $result->cryption;
            }
            $keyEncrypt = explode('|', base64_decode($result->cryption));
            if ($this->keyEncrypt['key'] !== $keyEncrypt[0] && $this->keyEncrypt['iv'] !== $keyEncrypt[1] && $this->keyEncrypt['level'] !== $keyEncrypt[2]) {
                list($this->keyEncrypt['key'], $this->keyEncrypt['iv'], $this->keyEncrypt['level']) = $keyEncrypt;
            }
            $this->http['request']->encryptionKeys = $keysEncryption['valids'];
            $keysEncryption['strtotime'] = strtotime(date('Y-m-d H:i:s'));
        }
        $keysEncryptionAuth[$this->ip][$this->idUser] = $keysEncryption;
        $this->auth->write('keysEncryption', $keysEncryptionAuth);
        return [$count, $result];
    }

    /**
     * @param string $url
     * @return void
     */
    private function checkUrlNotEncrypt(string $url): void
    {
        $keysEncryptionAuth = $this->auth->getData('keysEncryption');
        list($keyEncrytption['key'], $keyEncrytption['iv'], $keyEncrytption['level']) = explode(
            '|',
            base64_decode($keysEncryptionAuth[$this->ip][$this->idUser]['cryption'])
        );
        if ($keyEncrytption['level'] !== '0') {
            $uri = $url;
            if ($uri == '') {
                $uri = 'main' . DS . 'index';
            } elseif (substr_count($uri, DS) > 1) {
                $uri = explode(DS, $uri);
                $uri = $uri[0] . DS . $uri[1];
            }
            $this->validated = in_array(
                    $uri,
                    array_merge(
                        ['main/index', 'main/login', 'main/logging', 'main/logout'],
                        $keyEncrytption[$this->ip][$this->idUser]['routes'] ?? []
                    )
                ) === false;
        }
        $change = false;
        if (!$this->http['request']->encryptionKeys['general']) {
            if ($this->decrypt($url) === 'main/recovery') {
                $change = !$change;
                $keysEncryptionAuth[$this->ip][$this->idUser]['valids']['linkInternalWithExternalAccess'] = $this->http['request']->encryptionKewys['linkInternalWithExternalAccess'] = true;
            }
        }
        if ($change) {
            $this->auth->write('keysEncryption', $keysEncryptionAuth);
        }
        $this->hash = $url;
        return;
    }

    /**
     * @param string $crypt
     *
     * @return string
     */
    public function decrypt(string $crypt = ''): string
    {
        if (empty($crypt) && !empty($this->hash)) {
            $url = $this->hash;
            $this->hash = '';
            return $url;
        }
        if ($this->validated) {
            if ($this->keyEncrypt['level'] >= 1) {
                if ($this->keyEncrypt['level'] >= 2) {
                    if ($this->keyEncrypt['level'] === 3) {
                        $crypt = str_replace('_', DS, base64_decode($crypt));
                    }
                    $crypt = str_replace('_', DS, str_replace('|', '+', $crypt));
                    $crypt = openssl_decrypt(
                        $crypt,
                        'AES-128-CBC',
                        $this->keyEncrypt['key'],
                        0,
                        $this->keyEncrypt['iv']
                    );
                }
                if ($this->alfanumerico) {
                    $path = '';
                    for ($a = 1; $a < strlen($crypt); $a = $a + 2) {
                        $path .= $crypt[$a];
                    }
                    return $path;
                }
                return $crypt;
            }
            return $crypt;
        }
        return $crypt;
    }

    /**
     * @param int $id
     *
     * @return Hash
     * @throws Exceptions
     */
    public function deleteByTimeOrIdentifier(array $options, bool $timeDelete, int $id = 0): Hash
    {
        $this->table->executionTypeForQuery('delete');
        $options['join'] = [
            [
                'table' => 'securities',
                'type' => 'inner',
                'conditions' => 'shortenurl.idSecurity = securities.id'
            ]
        ];
        if (!isset($options['conditions']['securities.ip & '])) {
            $options['conditions'] = ['securities.ip & ' => $this->ip];
        }
        if (!$timeDelete) {
            $options['conditions']['securities.status & '] = 'desconnect';
            $options['conditions']['shortenurl.datetime -& '] = date(
                'Y-m-d H:i:s',
                strtotime(date('Y-m-d H:i:s') . ' - 15 minutes')
            );
            $options['conditions']['shortenurl.current & '] = 'nao';
        } else {
            $options['conditions']['shortenurl.datetime -& '] = date(
                'Y-m-d H:i:s',
                strtotime(date('Y-m-d H:i:s') . ' - 15 minutes')
            );
            $options['conditions']['shortenurl.current & '] = 'nao';
        }
        if ($id !== 0) {
            $options['conditions']['shortenurl.idUser & '] = $id;
        }
        $this->deletingOrChangeKeysOfTheHash(
            date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' - 15 minutes')),
            $options['conditions']['securities.status & '] ?? ''
        );
        $this->table->executeTheQueryAfterItIsAssembled(
            [array_merge($options, ['table' => ['table' => 'shortenurl']])]
        );
        return $this;
    }

    /**
     * @param string $dateTime
     * @param string $status
     * @return Hash
     */
    private function deletingOrChangeKeysOfTheHash(string $dateTime, string $status): Hash
    {
        $datetime = date('Y-m-d H:i:s');
        $keysEncryptionAuth = $this->auth->getData('keysEncryption');
        if (!empty($status)) {
            if ($status === 'desconnect') {
                $values = $keysEncryptionAuth[$this->ip][0];
                if ($keysEncryptionAuth[$this->ip][$this->idUser]['strtotime'] < strtotime($datetime)) {
                    unset($keysEncryptionAuth[$this->ip][$this->idUser]);
                }
                $keysEncryptionAuth[$this->ip][0]['strtotime'] = strtotime($datetime);
            } else {
                if ($status !== $keysEncryptionAuth[$this->ip][$this->idUser]['status']) {
                    $keysEncryptionAuth[$this->ip][$this->idUser]['status'] = $status;
                }
                $keysEncryptionAuth[$this->ip][$this->idUser]['strtotime'] = strtotime($datetime);
            }
            $keyEncrypt = explode('|', base64_decode($keysEncryptionAuth[$this->ip][$this->idUser]['cryption']));
            if ($this->keyEncrypt['key'] !== $keyEncrypt[0] && $this->keyEncrypt['iv'] !== $keyEncrypt[1] && $this->keyEncrypt['level'] !== $keyEncrypt[2]) {
                list($this->keyEncrypt['key'], $this->keyEncrypt['iv'], $this->keyEncrypt['level']) = $keyEncrypt;
            }
            $this->http['request']->encryptionKeys = $keysEncryptionAuth[$this->ip][$this->idUser]['valids'];
            $this->auth->write('keysEncryption', $keysEncryptionAuth);
            return $this;
        }
        foreach (array_keys($keysEncryptionAuth[$this->ip]) as $key) {
            if ($key !== '0') {
                if ($values['strtotime'] < strtotime($datatime)) {
                    unset($keysEncryptionAuth[$this->ip][$key]);
                }
            }
        }
        $this->auth->write('keysEncryption', $keysEncryptionAuth);
        return $this;
    }

    /**
     * @param string $data
     *
     * @return bool
     */
    public function valideDecrypt(string $data = ''): Hash
    {
        if (!empty($data)) {
            $this->hash = $data;
        }
        $nivel = false;
        if ($this->keyEncrypt['level'] === '3') {
            $nivel = !$nivel;
            if ($this->validBase64()) {
                if ($this->valideOpenssl()) {
                    if ($this->valideScrambledText()) {
                        return $this;
                    }
                }
            }
        }
        if ($this->keyEncrypt['level'] === '2') {
            $nivel = !$nivel;
            if ($this->valideOpenssl()) {
                if ($this->valideScrambledText()) {
                    return $this;
                }
            }
        }
        if (!$nivel) {
            if ($this->valideScrambledText()) {
                return $this;
            }
        }
        return $this;
    }

    /**
     * @param string $data
     *
     * @return bool
     * @throws Exceptions
     */
    private function validBase64(): bool
    {
        if ($this->validated) {
            $result = true;
            if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $this->hash) === false) {
                return !$result;
            }
            $decrypt = base64_decode($this->hash);
            if ($decrypt === false) {
                return !$result;
            }
            if (base64_encode($decrypt) !== $this->hash) {
                return !$result;
            }
            $this->hash = $decrypt;
            return $result;
        }
        return false;
    }

    /**
     * @param string $data
     *
     * @return bool
     * @throws Exceptions
     */
    private function valideOpenssl(): bool
    {
        if ($this->validated) {
            $this->hash = openssl_decrypt(
                str_replace('_', DS, str_replace('|', '+', $this->hash)),
                'AES-128-CBC',
                $this->keyEncrypt['key'],
                0,
                $this->keyEncrypt['iv']
            );
            if ($this->hash !== false) {
                return true;
            }
            $this->hash = $this->http['request']->url;
            return false;
        }
        return false;
    }

    /**
     * @param string $data
     *
     * @return bool
     */
    private function valideScrambledText(): bool
    {
        if ($this->validated) {
            if ($this->alfanumerico) {
                $url = '';
                for ($a = 1; $a < strlen($this->hash); $a = $a + 2) {
                    $url .= $this->hash[$a];
                }
                $this->hash = $url;
            }
            if (count($this->auth->getSession('route')) > 0) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * @return $this
     */
    public function timeExpiredHasChangeStatus(): hash
    {
        $status = 'desconnect';
        $this->table->executionTypeForQuery('update');
        $this->validated = !$this->validated;
        $this->http['request']->encryptionKeys['status'] = $status;
        $this->table->executeTheQueryAfterItIsAssembled(
            [
                [
                    'fields' => ['status' => $status],
                    'conditions' => ['ip & ' => $this->ip],
                    'table' => ['table' => 'securities']
                ]
            ]
        );
        $this->deletingOrChangeKeysOfTheHash(
            date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' - 15 minutes')),
            ''
        );
        return $this;
    }

    /**
     * @return bool
     */
    public
    function validationResult(): bool
    {
        return $this->validated;
    }

    /**
     * @param string $shorten
     * @param int $id
     * @return bool
     * @throws Exceptions
     */
    public function checkShortenDB(string $shorten, int $id): bool
    {
        $this->table->executionTypeForQuery('count');
        return $this->table->executeTheQueryAfterItIsAssembled(
                [
                    [
                        'fields' => ['count(shortenurl.id) as count'],
                        'conditions' => ['idUser & ' => $id, 'shorten & ' => $shorten],
                        'table' => ['table' => 'shortenurl']
                    ]
                ]
            )->count > 0;
    }

    /**
     * @param string $status
     * @return Hash
     * @throws Exceptions
     */
    public function updateSecurity(string $status = 'desconnect'): Hash
    {
        $this->table->executionTypeForQuery('update');
        $this->validated = !$this->validated;
        $this->http['request']->encryptionKeys['status'] = $status;
        $this->table->executeTheQueryAfterItIsAssembled(
            [
                [
                    'fields' => ['status' => $status],
                    'conditions' => ['ip & ' => $this->ip],
                    'table' => ['table' => 'securities']
                ]
            ]
        );
        $this->deletingOrChangeKeysOfTheHash(
            date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' - 15 minutes')),
            $status
        );
        return $this;
    }
}
