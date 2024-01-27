<?php

declare(strict_types=1);

namespace Restfull\view\Helper;

use Restfull\Error\Exceptions;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 *
 */
class OptimizerHelper extends Helper
{

    /**
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct(
            $view,
            ['meta' => "<meta%s/>", 'link' => "<link href='%s'%s>", 'title' => "<title>%s</title>"]
        );
        return $this;
    }

    /**
     * @param string $title
     * @param string $description
     * @param string $url
     * @param string $image
     * @param bool $follow
     *
     * @return string
     * @throws Exceptions
     */
    public function optimize(
        string $title,
        string $image,
        string $description = '',
        string $url = '',
        bool $follow = true
    ): string {
        $optimize[] = $this->formatTemplate(
            str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'title', __METHOD__),
            [$title]
        );
        foreach ($this->favicon($image) as $icon) {
            if (isset($icon['url'])) {
                $newUrl = $icon['url'];
                unset($icon['url']);
                $optimize[] = $this->formatTemplate(
                    str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'link', __METHOD__),
                    [$newUrl, $this->formatAttributes($icon)]
                );
            } else {
                $optimize[] = $this->formatTemplate(
                    str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'meta', __METHOD__),
                    [$this->formatAttributes($icon)]
                );
            }
        }
        if (!$this->request->bootstrap('hash')->valideDecrypt($this->request->url)->validationResult()) {
            foreach (
                [
                    'name' => ['description'],
                    'canonical' => ['url'],
                    'property' => ['title', 'description', 'url', 'image'],
                    'names' => ['title', 'description', 'url', 'image'],
                    'itemprop' => ['title', 'description', 'url', 'image']
                ] as $key => $values
            ) {
                $newKey = $key;
                if ($key === 'names') {
                    $newKey = 'name';
                }
                $count = count($values);
                for ($a = 0; $a < $count; $a++) {
                    $valid = true;
                    if (in_array($key, ['name', 'canonical']) !== false && in_array($values[$a], ['description', 'url']
                        ) !== false) {
                        $valid = !empty(${$values[$a]});
                    }
                    if ($valid) {
                        $optimize[] = $newKey === 'canonical' ? $this->formatTemplate(
                            str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'link', __METHOD__),
                            [${$values[$a]}, $this->formatAttributes(['rel' => $newKey])]
                        ) : $this->formatTemplate(
                            str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'meta', __METHOD__),
                            [
                                $this->formatAttributes(
                                    $this->options([$newKey => $values[$a], 'content' => ${$values[$a]}],
                                        in_array($key, ['property', 'names']
                                        ) !== false ? ($key === 'property' ? ['og:'] : ['twitter:']) : [])
                                )
                            ]
                        );
                    }
                }
            }
            $optimize[] = $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'meta', __METHOD__),
                [
                    $this->formatAttributes(
                        $this->options(
                            ['name' => 'robots', 'content' => ($follow ? 'index, follow' : 'noindex, nofollow')]
                        )
                    )
                ]
            );
        }
        return implode('', $optimize);
    }

    /**
     * @param array $options
     * @param array $prefix
     * @param bool $face
     *
     * @return array
     */
    private function options(array $options, array $prefix = [], bool $face = false)
    {
        $keys = array_keys($options);
        $count = count($keys);
        for ($a = 0; $a < $count; $a++) {
            if ($a === 0) {
                if (count($prefix) === 1) {
                    $options[$keys[$a]] = $prefix[0] . $options[$keys[$a]];
                } elseif (count($prefix) > 1) {
                    $options[$keys[$a]] = in_array($options[$keys[$a]], ['app_id', 'admins']
                    ) !== false ? $prefix[0] . $options[$keys[$a]] : ($face ? $prefix[1] . $options[$keys[$a]] : $options[$keys[$a]]);
                }
            }
        }
        return $options;
    }

    /**
     * @param string $name
     * @param string $locate
     *
     * @return string
     * @throws Exceptions
     */
    public function graphics(string $name, string $locate = 'pt_br'): string
    {
        $options = [
            ['property' => 'site_name', 'content' => $name],
            ['property' => 'type', 'content' => 'article'],
            ['property' => 'locale', 'content' => $locate]
        ];
        foreach ($options as $values) {
            $graphics[] = $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'meta', __METHOD__),
                [$this->formatAttributes($this->options($values, ['og:']))]
            );
        }
        return implode("", $graphics);
    }

    /**
     * @param string $app
     * @param string $author
     * @param string $pagePublisher
     * @param string|null $admin
     *
     * @return string
     * @throws Exceptions
     */
    public function facebook(string $app, string $author, string $pagePublisher, string $admin = null): string
    {
        $options = [
            ['property' => 'app_id', 'content' => $app],
            ['property' => 'author', 'content' => 'https://www.facebook.com/' . $author],
            ['property' => 'publisher', 'content' => 'https://www.facebook.com/' . $pagePublisher]
        ];
        if (!is_null($admin)) {
            $options[] = ['property' => 'admins', 'content' => $admin];
        }
        foreach ($options as $key => $values) {
            $facebook[] = $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'meta', __METHOD__),
                [$this->formatAttributes($this->options($values, ['fb:', 'article:'], true))]
            );
        }
        return implode("", $facebook);
    }

    /**
     * @param string $creator
     * @param string $site
     * @param string $domain
     * @param string|null $card
     *
     * @return string
     * @throws Exceptions
     */
    public function twitter(string $creator, string $site, string $domain, string $card = null): string
    {
        $options = [
            ['name' => 'site', 'content' => $site],
            ['name' => 'creator', 'content' => $creator],
            ['name' => 'domain', 'content' => $domain]
        ];
        if (!is_null($card)) {
            $options[] = ['name' => 'card', 'content' => $card];
        }
        foreach ($options as $values) {
            $twitter[] = $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'meta', __METHOD__),
                [$this->formatAttributes($this->options($values, ['twitter:']))]
            );
        }
        return implode("", $twitter);
    }

}
