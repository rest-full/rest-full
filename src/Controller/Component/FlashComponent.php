<?php

declare(strict_types=1);

namespace Restfull\Controller\Component;

use DataTime;
use Restfull\Controller\BaseController;
use Restfull\Controller\Component;

/**
 *
 */
class FlashComponent extends Component
{

    /**
     * @var array
     */
    private $msgTypes = ['e' => 'error', 'a' => 'warning', 's' => 'success', 'i' => 'info'];

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @param BaseController $controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller);
        $this->auth = $this->request->bootstrap('auth');
        $flash = $this->auth->getAuth('flash_messages');
        if (!$this->auth->check('flash_messages')) {
            $this->auth->write('flash_messages', []);
        }
        if (count($flash) > 0) {
            $this->auth->write('flash_messages', $flash);
        }
        return $this;
    }

    /**
     * @param string $menssagem
     * @param string|null $url
     *
     * @return FlashComponent
     */
    public function error(string $menssagem, string $url = null): FlashComponent
    {
        $this->add($menssagem, 'e', $url);
        return $this;
    }

    /**
     * @param string $message
     * @param string $type
     * @param string|null $redirectUrl
     *
     * @return FlashComponent
     */
    public function add(string $message, string $type, string $redirectUrl = null): FlashComponent
    {
        if (strlen(trim($type)) > 1) {
            $type = strtolower($type[0]);
        }
        if (!$this->auth->check('flash_messages')) {
            $flash = $this->auth->getAuth('flash_messages');
            if (isset($flash[$type])) {
                if (!$this->sweep($flash[$type], substr($message, 19))) {
                    $flash[$type][] = $message;
                }
            } else {
                $flash[$type][] = $message;
            }
            $this->auth->write('flash_messages', $flash, ['valid' => false]);
            if (!is_null($redirectUrl)) {
                header('Location: ' . $redirectUrl);
            }
        }
        return $this;
    }

    /**
     * @param array $flash
     * @param string $message
     *
     * @return bool
     */
    public function sweep(array $flash, string $message): bool
    {
        $resp = false;
        foreach ($flash as $msg) {
            if (substr($msg, 19) === $message) {
                $resp = true;
                break;
            }
        }
        return $resp;
    }

    /**
     * @param array $menssagens
     *
     * @return FlashComponent
     */
    public function errors(array $menssagens): FlashComponent
    {
        $this->adds($menssagens, 'e');
        return $this;
    }

    /**
     * @param string $message
     * @param string $type
     *
     * @return FlashComponent
     */
    public function adds(string $message, string $type): FlashComponent
    {
        if (strlen(trim($type)) > 1) {
            $type = strtolower($type[0]);
        }
        if (!$this->auth->check('flash_messages')) {
            $flash = $this->auth->getAuth('flash_messages');
            $this->auth->write('flash_messages', array_merge($flash, [$type => $menssagens]), ['valid' => false]);
        }
        return $this;
    }

    /**
     * @param array $menssagens
     *
     * @return FlashComponent
     */
    public function infos(array $menssagens): FlashComponent
    {
        $this->adds($menssagens, 'i');
        return $this;
    }

    /**
     * @param array $menssagens
     *
     * @return FlashComponent
     */
    public function warnings(array $menssagens): FlashComponent
    {
        $this->adds($menssagens, 'w');
        return $this;
    }

    /**
     * @param array $menssagens
     *
     * @return FlashComponent
     */
    public function successes(array $menssagens): FlashComponent
    {
        $this->adds($menssagens, 's');
        return $this;
    }

    /**
     * @param string $menssagem
     * @param string|null $url
     *
     * @return FlashComponent
     */
    public function info(string $menssagem, string $url = null): FlashComponent
    {
        $this->add($menssagem, 'i', $url);
        return $this;
    }

    /**
     * @param string $menssagem
     * @param string|null $url
     *
     * @return FlashComponent
     */
    public function warning(string $menssagem, string $url = null): FlashComponent
    {
        $this->add($menssagem, 'w', $url);
        return $this;
    }

    /**
     * @param string $menssagem
     * @param string|null $url
     *
     * @return FlashComponent
     */
    public function success(string $menssagem, string $url = null): FlashComponent
    {
        $this->add($menssagem, 's', $url);
        return $this;
    }

    /**
     * @return array
     */
    public function returnOfAllText(): array
    {
        $flashs = $this->auth->getAuth('flash_messages');
        $this->destroy();
        return $flashs;
    }

    /**
     * @return FlashComponent
     */
    public function destroy(): FlashComponent
    {
        $this->auth->write('flash_messages', [], ['valid' => false]);
        return $this;
    }

    /**
     * @return array
     */
    public function msgTypes(): array
    {
        return $this->msgTypes;
    }

}
