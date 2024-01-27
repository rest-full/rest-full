<?php

declare(strict_types=1);

namespace Restfull\Utility;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Search\Search;

/**
 *
 */
class translation
{

    /**
     * @var array
     */
    protected $lenguage = [];
    /**
     * @var Search
     */
    private $search;

    /**
     * @throws Exceptions
     */
    public function __construct(Instances $instance)
    {
        $this->search = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Search' . DS_REVERSE . 'Search',
            ['uri' => 'https://translate.google.com/translate_a/single']
        );
        return $this;
    }

    /**
     * @param string $text
     *
     * @return string
     * @throws Exceptions
     */
    public function translate(string $text): string
    {
        $fields = [
            'sl' => urlencode($this->lenguage[0]),
            'tl' => urlencode($this->lenguage[1]),
            'q' => urlencode($text)
        ];
        if (strlen($fields['q']) >= 5000) {
            throw new Exceptions('Maximum number of characters exceeded: 5000', 404);
        }
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        $result = $this->search->searching(
            [
                'CURLOPT_CUSTOMREQUEST' => 'POST',
                'CURLOPT_POSTFIELDS' => $fields_string,
                'CURLOPT_ENCODING' => 'UTF-8',
                'CURLOPT_SSL_VERIFYPEER' => false,
                'CURLOPT_SSL_VERIFYHOST' => false,
                'CURLOPT_CONNECTTIMEOUT' => 15,
                'CURLOPT_USERAGENT' => 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1'
            ],
            'client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=uk-RU&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e'
        )->answer();
        if (is_null($result)) {
            $result = $this->translate($text);
        }
        return $this->getSentencesFromObject($result);
    }

    /**
     * @param string $json
     *
     * @return string
     * @throws Exceptions
     */
    protected function getSentencesFromObject(string $arr): string
    {
        $sentences = '';
        if (isset($arr['sentences'])) {
            foreach ($arr['sentences'] as $s) {
                $sentences .= isset($s['trans']) ? $s['trans'] : '';
            }
        }
        return $sentences;
    }
}