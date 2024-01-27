<?php

declare(strict_types=1);

namespace Restfull\Utility;

use ErrorException;
use Restfull\Container\Instances;

/**
 *
 */
class Translator extends translation
{

    /**
     * @var Inflector
     */
    public $inflector;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param string $lenguage
     * @param Instances $instances
     */
    public function __construct(string $lenguage, Instances $instance)
    {
        $this->lenguage = ['en', $lenguage];
        $this->inflector = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Utility' . DS_REVERSE . 'Inflector'
        );
        parent::__construct($instance);
        return $this;
    }

    /**
     * @param string $text
     * @param string $convert
     *
     * @return string
     */
    public function plural(string $text, array $options = []): string
    {
        if (isset($this->inflector->pluralCache[$text])) {
            if (isset($options['convert']) && !empty($options['convert'])) {
                return $this->convert($this->inflector->pluralCache[$text], $options['convert']);
            }
            return $this->inflector->pluralCache[$text];
        }
        if (isset($options['translator'])) {
            if (isset($options['convert']) && !empty($options['convert'])) {
                $convert = 'to' . ucfirst($convert);
                return $this->translation($this->inflector->pluralize($text), $options['convert']);
            }
            return $this->translation($this->inflector->pluralize($text));
        }
        if (isset($options['convert']) && !empty($options['convert'])) {
            return $this->convert($this->inflector->pluralize($text), $options['convert']);
        }
        return $this->inflector->pluralize($text);
    }

    /**
     * @param string $text
     * @param string $convert
     * @return string
     */
    public function convert(string $text, string $convert): string
    {
        if (substr($convert, 0, 2) != 'to') {
            $convert = 'to' . ucfirst($convert);
        }
        return $this->inflector->{$convert}($text);
    }

    /**
     * @param string $text
     * @param string $convert
     *
     * @return string
     * @throws ErrorException
     */
    public function translation(string $text, string $convert = ''): string
    {
        if (isset($this->cache[$text])) {
            $text = $this->cache[$text];
        } else {
            $oldText = $text;
            $text = $this->translate($text);
            $this->cache[$oldText] = $text;
        }
        if (!empty($convert)) {
            return $this->convert($text, $convert);
        }
        return $text;
    }

    /**
     * @param string $text
     * @param string $convert
     *
     * @return string
     * @throws ErrorException
     */
    public function singular(string $text, array $options = []): string
    {
        if (isset($this->inflector->singularCache[$text])) {
            if (isset($options['convert']) && !empty($options['convert'])) {
                return $this->convert($this->inflector->singularCache[$text], $options['convert']);
            }
            return $this->inflector->singularCache[$text];
        }
        if (isset($options['translator'])) {
            if (isset($options['convert']) && !empty($options['convert'])) {
                $options['convert'] = 'to' . ucfirst($options['convert']);
                return $this->translation($this->inflector->singularize($text), $options['convert']);
            }
            return $this->translation($this->inflector->singularize($text));
        }
        if (isset($options['convert']) && !empty($options['convert'])) {
            return $this->convert($this->inflector->singularize($text), $options['convert']);
        }
        return $this->inflector->singularize($text);
    }

    /**
     * @param string $key
     * @param string $plural
     * @param string $singular
     *
     * @return Translator
     */
    public function caches(string $key, string $plural, string $singular): Translator
    {
        $this->inflector->singularCache[$key] = $singular;
        $this->inflector->pluralCache[$key] = $plural;
        return $this;
    }

    /**
     * @return Translator
     */
    public function changeLenguage(): Translator
    {
        $this->lenguage = array_reverse($this->lenguage);
        return $this;
    }

    /**
     * @param array $texts
     * @param string $convert
     *
     * @return array
     * @throws ErrorException
     */
    public function translations(array $texts, string $convert = ''): array
    {
        if (isset($this->cache[$texts])) {
            $texts = $this->cache[$texts];
        } else {
            foreach ($texts as $key => $value) {
                $text = !empty($convert) ? $this->translation($value, $convert) : $this->translation($text);
                $texts[$key] = $text;
            }
            $this->cache = $texts;
        }
        return $texts;
    }

}
