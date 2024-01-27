<?php

declare(strict_types=1);

namespace Restfull\Filesystem;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class Upload
{

    /**
     * @var int
     */
    protected $sizetmp = 0;

    /**
     * @var File|Image|Media
     */
    private $file;

    /**
     * @var string
     */
    private $mimetype = '';

    /**
     * @param array $file
     * @param int $size
     *
     * @throws Exceptions
     */
    public function __construct(Instances $instance, array $file, int $size = 100000000)
    {
        $this->mimetype = $file['type'];
        if (in_array(substr($this->mimetype, 0, stripos($this->mimetype, DS)), ['video', 'audio', 'image']) !== false) {
            $type = in_array(substr($this->mimetype, 0, stripos($this->mimetype, DS)), ['video', 'audio']
            ) !== false ? 'Media' : ucfirst(substr($this->mimetype, 0, stripos($this->mimetype, DS)));
            $this->file = $instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . $type,
                ['instance' => $instance, 'file' => ROOT_PATH . 'temp' . DS . $file['name'], 'arq' => $file]
            );
            if ($this->file->valid($type)) {
                $this->sizeLimit($size);
            }
        } else {
            $this->file = $instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'File',
                ['instance' => $instance, 'file' => ROOT_PATH . 'temp' . DS . $file['name'], 'arq' => $file]
            );
            $this->sizeLimit($size);
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exceptions(
                'The file you tried to upload was not accepted.', 404
            );
        }
        return $this;
    }

    /**
     * @param int $size
     *
     * @return Upload
     */
    public function sizeLimit(int $size): Upload
    {
        if ($size < $this->file->tmp('size')) {
            throw new Exceptions('Allowed limit exceeded.', 404);
        }
        $this->sizetmp = $size;
        return $this;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function tmp(string $key = 'tmp_name'): string
    {
        return $this->file->tmp($key);
    }

    /**
     * @return bool
     * @throws Exceptions
     */
    public function insert(): bool
    {
        $type = $this->file->tmp('type');
        if (substr($type, 0, stripos($type, DS)) === 'image' && $this->sizetmp !== 100000000) {
            $names = $this->image->createDifferentSizes($path, 0, 0, $this->file->pathinfo());
            if (count($names) > 0) {
                return true;
            }
            return false;
        }
        if (!move_uploaded_file($this->file->tmp(), $this->file->namePath())) {
            throw new Exceptions('The' . $this->file->namePath(true) . 'file cannot be moved.', 404);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->file->exists();
    }

    /**
     * @param bool $folder
     *
     * @return bool
     */
    public function delete(bool $folder = false): bool
    {
        return $this->file->delete($folder);
    }

    /**
     * @param array $positions
     * @param int $width
     * @param int $height
     * @param string $path
     * @param bool $rotation
     *
     * @return Upload
     */
    public function cut(array $positions, int $width, int $height, string $path, bool $rotation = false): Upload
    {
        $this->file->resize($this->file->calculating($width, $height), [$path], 'cut');
        return $this;
    }

    /**
     * @param string $path
     *
     * @return Upload
     */
    public function rotation(string $imageTmp, string $path): Upload
    {
        if (($this->file->tmp('type') ?? mime_content_type($this->filename())) === 'image/png') {
            $this->file->convertFromPngToJpg($imageTmp, $path)->pathFile($path);
            return $this;
        }
        list($width, $height) = $this->file->size($imageTmp);
        $this->file->resize($this->file->calculating($width, $height, true), [$imageTmp, $path], 'rotation');
        return $this;
    }

    /**
     * @return string
     */
    public function filename(): string
    {
        return $this->file->pathinfo();
    }

}
