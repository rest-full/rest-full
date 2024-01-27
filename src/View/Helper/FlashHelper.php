<?php

declare(strict_types=1);

namespace Restfull\View\Helper;

use Restfull\Authentication\Auth;
use Restfull\Error\Exceptions;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 *
 */
class FlashHelper extends Helper
{

    /**
     * @var array
     */
    protected $msgTypes = ['e' => 'error', 'a' => 'warning', 's' => 'success', 'i' => 'info',];

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct(
            $view,
            ['others' => "<%s>%s</%s>", 'span' => "<span%s>%s</span>", 'image' => "<img src='%s'%s/>"]
        );
        $this->auth = $this->request->bootstrap('auth');
        return $this;
    }

    /**
     * @param string $types
     *
     * @return array
     */
    public function display(string $types): array
    {
        $output = [];
        if (!$this->auth->check('flash_messages')) {
            return $output;
        }
        if (is_null($types) || !$types || (is_array($types) && empty($types))) {
            $types = array_keys($this->msgTypes);
        } elseif (is_array($types) && !empty($types)) {
            $theTypes = $types;
            $types = [];
            foreach ($theTypes as $type) {
                $types[] = strtolower($type[0]);
            }
        } else {
            $types = [strtolower($types[0])];
        }
        $flash = $this->auth->getAuth('flash_messages');
        foreach ($types as $type) {
            if (!isset($flash[$type]) || empty($flash[$type])) {
                continue;
            }
            foreach ($flash[$type] as $msgData) {
                $output[] = $msgData;
            }
            unset($flash[$type]);
        }
        if (count($flash) === 0) {
            $this->auth->destroy('flash_messages', ['valid' => false]);
        }
        return $output;
    }

    /**
     * @return string
     * @throws Exceptions
     */
    public function render(): string
    {
        $content = '';
        $flash = $this->auth->getSession('flash_messages');
        if (count($flash) > 0) {
            foreach (array_keys($this->msgTypes) as $key) {
                if (in_array($key, array_keys($flash))) {
                    ob_start();
                    $flashFile = substr(
                            RESTFULL,
                            0,
                            -1
                        ) . DS . 'Template' . DS . 'Flash' . DS . $this->msgTypes[$key] . '.phtml';
                    include_once $flashFile;
                    if (empty($content)) {
                        $content = ob_get_contents();
                    }
                    ob_clean();
                }
            }
            return $content;
        }
        return '';
    }

    /**
     * @param string $content
     * @param string $name
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function tag(string $content, string $name, array $options = []): string
    {
        return $this->formatTemplate(
            str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'others', __METHOD__),
            [$name . " " . $this->formatAttributes($options), $content, $name]
        );
    }

    /**
     * @param string $img
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function image(string $img, array $options = []): string
    {
        if (!isset($options['id'])) {
            $options['id'] = $img;
        }
        $urlImg = $img;
        if (in_array(substr($img, 0, 5), ['https', 'http:']) === false) {
            $urlImg = DS . ".." . $this->route . $img;
        }
        if (isset($options['link'])) {
            $url = $options['link']['url'];
            $urlattr = $options['link'];
            unset($options['link'], $urlattr['url']);
            $img = $this->formatTemplate(__METHOD__, [$urlImg, $this->formatAttributes($options)]);
            return $this->link($img, $url, $urlattr);
        }
        return $this->formatTemplate(__METHOD__, [$urlImg, $this->formatAttributes($options)]);
    }
}
