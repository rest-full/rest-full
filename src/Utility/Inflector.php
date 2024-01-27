<?php

declare(strict_types=1);

namespace Restfull\Utility;

/**
 *
 */
class Inflector
{

    /**
     * @var array
     */
    public $singularCache = [];

    /**
     * @var array
     */
    public $pluralCache = [];

    /**
     * @var array
     */
    private $plural = [];

    /**
     * @var array
     */
    private $singular = [];

    /**
     * @var array
     */
    private $irregular = [];

    /**
     * @var array
     */
    private $uncountable = [];

    /**
     *
     */
    public function __construct()
    {
        $this->uncountable = [
            'sheep' => true,
            'fish' => true,
            'deer' => true,
            'series' => true,
            'species' => true,
            'money' => true,
            'rice' => true,
            'information' => true,
            'equipment' => true,
            'jeans' => true,
            'police' => true
        ];
        $this->irregular = [
            'zombie' => 'zombies',
            'move' => 'moves',
            'foot' => 'feet',
            'goose' => 'geese',
            'sex' => 'sexes',
            'child' => 'children',
            'man' => 'men',
            'tooth' => 'teeth',
            'person' => 'people'
        ];
        $this->plural = [
            '/(quiz)$/i' => "$1zes",
            '/^(oxen)$/i' => "$1",
            '/^(ox)$/i' => "$1en",
            '/([m|l])ice$/i' => "$1ice",
            '/([m|l])ouse$/i' => "$1ice",
            '/(matr|vert|ind)ix|ex$/i' => "$1ices",
            '/(x|ch|ss|sh)$/i' => "$1es",
            '/([^aeiouy]|qu)y$/i' => "$1ies",
            '/(hive)$/i' => "$1s",
            '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
            '/(shea|lea|loa|thie)f$/i' => "$1ves",
            '/sis$/i' => "ses",
            '/([ti])a$/i' => "$1a",
            '/([ti])um$/i' => "$1a",
            '/(buffal|tomat|potat|ech|her|vet)o$/i' => "$1oes",
            '/(bu)s$/i' => "$1ses",
            '/(alias|status)$/i' => "$1es",
            '/(octop|vir)i$/i' => "$1i",
            '/(octop|vir)us$/i' => "$1i",
            '/(ax|test)is$/i' => "$1es",
            '/(us)$/i' => "$1es",
            '/s$/i' => "s",
            '/$/' => "s"
        ];
        $this->singular = [
            '/(ss)$/i' => "$1",
            '/(database)s$/i' => "$1",
            '/(quiz)zes$/i' => "$1",
            '/(matr)ices$/i' => "$1ix",
            '/(vert|ind)ices$/i' => "$1ex",
            '/^(ox)en$/i' => "$1",
            '/(alias|status)(es)?$/i' => "$1",
            '/(octop|vir)i$/i' => "$1us",
            '/^(a)x[ie]s$/i' => "$1xis",
            '/(cris|ax|test)es$/i' => "$1is",
            '/(cris|ax|test)is$/i' => "$1is",
            '/(shoe|foe)s$/i' => "$1",
            '/(bus)es$/i' => "$1",
            '/^(toe)s$/i' => "$1",
            '/(o)es$/i' => "$1",
            '/([m|l])ice$/i' => "$1ouse",
            '/(x|ch|ss|sh)es$/i' => "$1",
            '/(m)ovies$/i' => "$1ovie",
            '/(s)eries$/i' => "$1eries",
            '/([^aeiouy]|qu)ies$/i' => "$1y",
            '/([lr])ves$/i' => "$1f",
            '/(tive)s$/i' => "$1",
            '/(hive)s$/i' => "$1",
            '/(li|wi|kni)ves$/i' => "$1fe",
            '/([^f])ves$/i' => "$1fe",
            '/(shea|loa|lea|thie)ves$/i' => "$1f",
            '/(^analy)(sis|ses)$/i' => "$1sis",
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)(sis|ses)$/i' => "$1$2sis",
            '/([ti])a$/i' => "$1um",
            '/(n)ews$/i' => "$1ews",
            '/(h|bl)ouses$/i' => "$1ouse",
            '/(corpse)s$/i' => "$1",
            '/(use)s$/i' => "$1",
            '/s$/i' => ""
        ];
        return $this;
    }

    /**
     * @param string $key
     * @param string $data
     *
     * @return Inflector
     */
    public function Chaces(string $key, string $data): Inflector
    {
        $this->singularCache[$key] = $data;
        $this->pluralCache[$data] = $key;
        return $this;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function singularize(string $text): string
    {
        if (!$text) {
            return '';
        }
        if (!isset($this->singularCache[$text])) {
            if (isset($this->uncountable[strtolower($text)])) {
                $this->singularCache[$text] = $text;
                return $text;
            }
            foreach ($this->irregular as $result => $pattern) {
                $pattern = '/' . $pattern . '$/i';
                if (preg_match($pattern, $text)) {
                    $this->singularCache[$text] = preg_replace($pattern, $result, $text);
                    return $this->singularCache[$text];
                }
            }
            foreach ($this->singular as $pattern => $result) {
                if (preg_match($pattern, $text)) {
                    $this->singularCache[$text] = preg_replace($pattern, $result, $text);
                    return $this->singularCache[$text];
                }
            }
            $this->singularCache[$text] = $text;
        }
        return $this->singularCache[$text];
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function pluralize(string $text): string
    {
        if (!$text) {
            return '';
        }
        if (!isset($this->pluralCache[$text])) {
            if (isset($this->uncountable[$text])) {
                $this->pluralCache[$text] = $text;
                return $text;
            }
            foreach ($this->irregular as $pattern => $result) {
                $pattern = '/' . $pattern . '$/i';
                if (preg_match($pattern, $text)) {
                    $this->pluralCache[$text] = preg_replace($pattern, $result, $text);
                    return $this->pluralCache[$text];
                }
            }
            foreach ($this->plural as $pattern => $result) {
                if (preg_match($pattern, $text)) {
                    $this->pluralCache[$text] = preg_replace($pattern, $result, $text);
                    return $this->pluralCache[$text];
                }
            }
            $this->pluralCache[$text] = $text;
        }
        return $this->pluralCache[$text];
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function toUpper(string $text): string
    {
        return mb_strtoupper($text);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function toLower(string $text): string
    {
        return mb_strtolower($text);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function toUcword(string $text): string
    {
        $caracters = [
            ' de ',
            ' do ',
            ' da ',
            ' a ',
            ' ae ',
            ' ai ',
            ' ao ',
            ' au ',
            ' ei ',
            ' eu ',
            ' ou ',
            ' ne ',
            ' no ',
            ' na ',
            ' em '
        ];
        $text = ucwords($text);
        $count = count($caracters);
        for ($a = 0; $a < $count; $a++) {
            if (stripos($text, ucwords($caracters[$a])) !== false) {
                $text = str_replace(ucwords($caracters[$a]), $caracters[$a], $text);
            }
        }
        return $text;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function toUcfirst(string $text): string
    {
        $caracters = [' de ', ' do ', ' da ', ' a ', ' ae ', ' ai ', ' ao ', ' au ', ' ei ', ' eu ', ' ou '];
        $text = ucfirst($text);
        $count = count($caracters);
        for ($a = 0; $a < $count; $a++) {
            if (stripos($text, ucfirst($caracters[$a])) !== false) {
                $text = str_replace(ucfirst($caracters[$a]), $caracters[$a], $text);
            }
        }
        return $text;
    }

}
