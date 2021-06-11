<?php

namespace Restfull\filesystem;

use Restfull\Error\Exceptions;

/**
 * Class Upload
 * @package Restfull\filesystem
 */
class Upload
{

    /**
     * @var File|Image|Midia
     */
    private $file;

    /**
     * @var Image
     */
    private $image;

    /**
     * @var InstanceClass
     */
    private $instance;

    /**
     * @var false|string
     */
    private $mimetype = '';

    /**
     * Upload constructor.
     * @param array $file
     * @param int $size
     * @throws Exceptions
     */
    public function __construct(array $file, int $size)
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exceptions('The file you tried to upload was not accepted.', 404);
        }
        if (is_array($file)) {
            if ($file['error'] != UPLOAD_ERR_OK) {
                throw new Exceptions('This file cannot upload anything.', 404);
            }
        }
        $this->mimetype = mime_content_type($file['name']);
        $this->instance = new InstanceClass();
        if (in_array(substr($this->mimetype, 0, stripos($this->mimetype, DS)), ['video', 'audio', 'image']) !== false) {
            $type = in_array(
                    substr($this->mimetype, 0, stripos($this->mimetype, DS)),
                    ['video', 'audio', 'image']
            ) !== false ? 'Media' : ucfirst(substr($this->mimetype, 0, stripos($this->mimetype, DS)));
            $this->file = $this->instance->resolveClass(
                    $this->instance->namespaceClass(
                            "%s" . DS_REVERSE . "Filesystem" . DS_REVERSE . $type,
                            [ROOT_NAMESPACE]
                    ),
                    ['file' => ROOT_PATH . 'tmp' . DS . $file['name'], 'arq' => $file]
            );
            $this->file->sizeLimtit($size);
        } else {
            $this->file = $this->instance->resolveClass(
                    $this->instance->namespaceClass(
                            "%s" . DS_REVERSE . "Filesystem" . DS_REVERSE . 'File',
                            [ROOT_NAMESPACE]
                    ),
                    ['file' => ROOT_PATH . 'tmp' . DS . $file['name'], 'arq' => $file]
            );
            $this->file->sizeLimit($size);
        }
        return $this;
    }

    /**
     * @param string $path
     * @param bool $sizeDifferent
     * @return bool
     * @throws Exceptions
     */
    public function insert(string $path, bool $sizeDifferent = false): bool
    {
        if (substr($this->minetype, 0, stripos($this->minetype, DS)) == 'image' && $sizeDifferent) {
            if (stripos($name, 'webroot') === false) {
                $path = ROOT_PATH . 'temp' . DS . $path;
            }
            $names = $this->image->createDifferentSizes($path, 0, 0, $this->file->pathinfo());
            if (count($names) > 0) {
                return true;
            }
            return false;
        }
        if (!move_uploaded_file($this->file->tmp_name(), $this->file->namePath())) {
            throw new Exceptions('The' . $this->file->namePath(true) . 'file cannot be moved.', 404);
        }
        return true;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function move(string $path): bool
    {
        if (stripos($name, 'webroot') === false) {
            $path = ROOT_PATH . 'files' . DS . $path;
        }
        rename($this->file->pathinfo(), $path);
        if ($this->file->instantiating($path)->exists()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        if ($this->file->exists()) {
            $this->file->delete();
            return true;
        }
        return false;
    }
}