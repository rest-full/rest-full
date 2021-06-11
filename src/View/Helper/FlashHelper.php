<?php

namespace Restfull\View\Helper;

use Restfull\Authentication\Auth;
use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 * Class FlashHelper
 * @package Restfull\View\Helper
 */
class FlashHelper extends Helper
{

    /**
     * @var array
     */
    protected $msgTypes = [
            'e' => 'error',
            'a' => 'warning',
            's' => 'success',
            'i' => 'info',
    ];

    /**
     * @var array
     */
    protected $templater = [
            'div' => "<div%s>%s</div>",
            'span' => "<span%s>%s</span>",
            'image' => "<img src='%s'%s/>"
    ];

    /**
     * @var Auth
     */
    private $auth;

    /**
     * FlashHelper constructor.
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view, $this->templater);
        $this->auth = $this->request->auth;
        return $this;
    }

    /**
     * @param string $types
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
        if (count($flash) == 0) {
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
        $this->auth->keys('flash_messages');
        $flash = $this->auth->getAuth('flash_messages');
        if (count($flash) > 0) {
            foreach (array_keys($this->msgTypes) as $key) {
                if (in_array($key, array_keys($flash))) {
                    ob_start();
                    $flashFile = (new Instances())->namespaceClass(
                            '%s' . DS . 'Template' . DS . 'Flash' . DS . '%s.phtml',
                            [
                                    substr(str_replace('App', 'src', ROOT_APP), 0, -1),
                                    $this->msgTypes[$key]
                            ]
                    );
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
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function tag(string $content, array $options): string
    {
        $tag = str_replace('tag', $options['tag'], __METHOD__);
        unset($options['tag']);
        return $this->formatTemplate(
                $tag,
                [
                        $this->formatAttributes($options),
                        $content
                ]
        );
    }

    /**
     * @param string $img
     * @param array $options
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
            $img = $this->formatTemplate(
                    __METHOD__,
                    [
                            $urlImg,
                            $this->formatAttributes($options)
                    ]
            );
            return $this->link($img, $url, $urlattr);
        }
        return $this->formatTemplate(
                __METHOD__,
                [
                        $urlImg,
                        $this->formatAttributes($options)
                ]
        );
    }

}
