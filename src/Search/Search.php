<?php

namespace Restfull\Search;

/**
 * Class Search
 * @package Restfull\Search
 */
class Search
{

    /**
     * @var string
     */
    private $response = '';

    /**
     * @var string
     */
    private $uri = '';

    /**
     * Search constructor.
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @param array $datas
     * @param string $uriConcat
     * @return Search
     */
    public function searching(array $datas, string $uriConcat = ''): Search
    {
        if (!empty($uriConcat)) {
            $this->uri = $this->uri . "?" . $uriConcat;
        }
        $ch = curl_init($this->uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        foreach ($datas as $keyData => $valueData) {
            curl_setopt($ch, constant($keyData), $valueData);
        }
        $this->response = curl_exec($ch);
        curl_close($ch);
        return $this;
    }

    /**
     * @return string
     */
    public function answer(): string
    {
        return $this->response;
    }
}
