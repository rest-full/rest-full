<?php

namespace Restfull\view\helper;

use Restfull\Error\Exceptions;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 * Class OptimizerHelper
 * @package Restfull\view\helper
 */
class OptimizerHelper extends Helper
{

    /**
     * @var array
     */
    private $templater = [
            'meta' => "<meta%s/>",
            'link' => "<link rel='%s' href='%s'>"
    ];

    /**
     * OptimizerHelper constructor.
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view, $this->templater);
        return $this;
    }

    /**
     * @param array $tag
     * @return string
     * @throws Exceptions
     */
    public function graphics(array $tag): string
    {
        if (!isset($tag['locale'])) {
            $tag['locale'] = 'pt_BR';
        }
        if (!isset($tag['type'])) {
            $tag['type'] = 'article';
        }
        foreach ($tag as $key => $value) {
            $metaData['og:' . $key] = $value;
            $graphics[] = $this->formatTemplate(
                    'meta',
                    [$this->formatAttributes($ometaData, substr(__METHOD__, stripos(__METHOD__, "::") + 2))]
            );
        }
        return implode("", $graphics);
    }

    /**
     * @param array $tag
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function facebook(array $tag, array $options = []): string
    {
        foreach ($tag as $value) {
            $optionsValue = $options[$value];
            unset($options[$value]);
            $facebook[] = $this->formatTemplate(
                    'meta',
                    [$this->formatAttributes($optionsValue, substr(__METHOD__, stripos(__METHOD__, "::") + 2))]
            );
        }
        return implode("", $facebook);
    }

    /**
     * @param array $tag
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function publisher(array $tag, array $options = []): string
    {
        $publishers = ['facebook' => 'meta', 'google' => 'link'];
        foreach ($tag as $value) {
            if (in_array($value, $publishers)) {
                if ($value == "google") {
                    $rel = $options[$value]['rel'];
                    $url = $options[$value]['url'];
                    unset($options['rel'], $options['url']);
                    $publisher[] = $this->formatTemplate($publishers[$value], [$rel, $url]);
                } else {
                    $publisher[] = $this->formatTemplate(
                            $publishers[$value],
                            [
                                    $this->formatAttributes(
                                            $options[$value],
                                            substr(__METHOD__, stripos(__METHOD__, "::") + 2)
                                    )
                            ]
                    );
                }
            } else {
                $publisher[] = '';
            }
        }
        return implode("", $publisher);
    }

    /**
     * @param array $tag
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function twitter(array $tag, array $options = []): string
    {
        foreach ($tag as $value) {
            $optionsValue = $options[$value];
            unset($options[$value]);
            $twitter[] = $this->formatTemplate(
                    'meta',
                    [$this->formatAttributes($optionsValue, substr(__METHOD__, stripos(__METHOD__, "::") + 2))]
            );
        }
        return implode("", $twitter);
    }

    /**
     * @param array $tag
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function optimizer(array $tag, array $options = []): string
    {
        $meta = ['graphics', 'twitter'];
        foreach ($tag as $value) {
            for ($a = 0; $a < count($meta); $a++) {
                $optimizer[] = $this->$meta[$a]([$value], $options);
            }
            $optimizer[] = $this->formatTemplate(
                    'meta',
                    [$this->formatAttributes($options[$value], substr(__METHOD__, stripos(__METHOD__, "::") + 2))]
            );
        }
        return implode("", $optimizer);
    }
}