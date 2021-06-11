<?php


namespace Restfull\Filesystem;


use Restfull\Error\Exceptions;

/**
 * Class Midia
 * @package Restfull\Filesystem
 */
class Media extends File
{
    /**
     * @var array
     */
    private $videoMime = ['video/mp4', 'video/webm'];

    /**
     * @var array
     */
    private $audioMime = ['audio/mpeg', 'audio/webm'];

    /**
     * Media constructor.
     * @param string $midia
     * @param array $arq
     * @throws Exceptions
     */
    public function __construct(string $midia, array $arq)
    {
        if (!empty($midia)) {
            if (!in_array(mime_content_type($midia), $this->videoMime)) {
                throw new Exceptions(
                        "This video has no accepted extension. Extensions accepted: MP4, WebM.", 404
                );
            } elseif (!in_array(mime_content_type($midia), $this->audioMime)) {
                throw new Exceptions(
                        "This audio has no accepted extension. Extensions accepted: MP3, WebM.", 404
                );
            }
        }
        parent::__contruct($midia, $arq);
        return $this;
    }
}