<?php

declare(strict_types=1);

namespace Restfull\Filesystem;

use Restfull\Error\Exceptions;

/**
 *
 */
class Media extends File
{

    /**
     * @var array
     */
    private $videoMime = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];

    /**
     * @var array
     */
    private $audioMime = ['audio/mpeg' => 'mp3', 'audio/webm' => 'webm'];

    /**
     * @param string $type
     *
     * @return bool
     * @throws Exceptions
     */
    public function valid(string $type): bool
    {
        if ($type === 'video') {
            if (!in_array(mime_content_type($this->file), array_keys($this->videoMime))) {
                throw new Exceptions("This video has no accepted extension. Extensions accepted: MP4, WebM.", 404);
            }
            return true;
        } else {
            if (!in_array(mime_content_type($this->file), array_keys($this->audioMime))) {
                throw new Exceptions("This audio has no accepted extension. Extensions accepted: MP3, WebM.", 404);
            }
            return true;
        }
        return false;
    }

}
