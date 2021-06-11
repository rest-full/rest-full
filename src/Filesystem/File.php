<?php

namespace Restfull\Filesystem;

use Restfull\Error\Exceptions;

/**
 * Class File
 * @package Restfull\Filesystem
 */
class File
{

    /**
     * @var Folder
     */
    protected $folder;
    /**
     * @var array
     */
    protected $tmp = [];
    /**
     * @var int
     */
    protected $sizetmp = 100000000;
    /**
     * @var string
     */
    protected $file = '';
    /**
     * @var resource
     */
    private $handle;
    /**
     * @var string
     */
    private $extension = '';

    /**
     * File constructor.
     * @param string $file
     * @param array $arq
     */
    public function __construct(string $file, array $arq = [])
    {
        $path = pathinfo($file);
        $this->folder = new Folder($path['dirname']);
        if (!isset($path['extension'])) {
            $path['extension'] = 'php';
        }
        $this->file = $path['basename'];
        $this->extension = $path['extension'];
        if (count($arq) > 0) {
            $this->tmpFile($arq);
        }
        return $this;
    }

    /**
     * @param array $files
     */
    public function tmpFile(array $files)
    {
        $valid = false;
        if (!empty($this->image)) {
            if (in_array('array', array_map('gettype', $files))) {
                for ($a = 0; $a < count($files); $a++) {
                    foreach ($files[$a] as $value) {
                        if ($this->image == $value) {
                            $valid = true;
                            break;
                        }
                    }
                }
                $file = $file[$a];
            } else {
                foreach ($files as $value) {
                    if ($this->image == $value) {
                        $valid = true;
                        break;
                    }
                }
                $file = $files;
            }
            $this->tmp = $file['tmp_name'];
        }
    }

    /**
     * @param string $file
     * @return File
     */
    public function instantiating(string $file): File
    {
        $path = pathinfo($file);
        $this->folder = new Folder($path['dirname']);
        if (!isset($path['extension'])) {
            $path['extension'] = 'php';
        }
        $this->file = $path['basename'];
        $this->extension = $path['extension'];
        return $this;
    }

    /**
     * @param int $size
     * @return File
     * @throws Exceptions
     */
    public function sizeLimit(int $size)
    {
        if ($size != $this->sizetmp) {
            $this->sizetmp = $size;
        }
        $this->valid();
        return $this;
    }

    /**
     * @return File
     * @throws Exceptions
     */
    public function valid(): File
    {
        if ($this->tmp['size'] > $this->sizetmp) {
            throw new Exceptions($this->tmp['name'] . 'file size exceeded the allowed.', 404);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function pathinfo(): string
    {
        return $this->folder->path() . DS . $this->file;
    }

    /**
     * @param bool $deleteFolder
     * @return File
     */
    public function delete(bool $deleteFolder = false): File
    {
        if ($this->exists() && is_file($this->folder->path() . DS . $this->file) && !$this->handle) {
            $path = stripos($_SERVER['WINDIR'], 'WINDOWS') !== false ? str_replace(
                            DS,
                            DS_REVERSE,
                            $this->folder->path()
                    ) . DS_REVERSE : $this->folder->path() . DS;
            $file = $path . $this->file;
            unlink($file);
        }
        if ($deleteFolder) {
            if (count($this->folder->read()['file']) == 0) {
                $this->folder->delete();
            }
        }
        $this->exist = $this->exists();
        return $this;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->folder->path() . DS . $this->file);
    }

    /**
     * @param bool $count
     * @param string $mode
     * @return array
     * @throws Exceptions
     */
    public function read(bool $count = false, string $mode = 'r+'): array
    {
        if (!isset($this->handle)) {
            $this->create($mode);
        }
        $reading = [];
        if ($this->exists()) {
            while ($read = fgets($this->handle)) {
                $reading[] = $read;
            }
            $this->close();
        }
        $read = ['content' => $reading];
        if ($count) {
            $read = array_merge($read, ['count' => count($reading) - 1]);
        }
        return $read;
    }

    /**
     * @param string $mode
     * @return File
     * @throws Exceptions
     */
    public function create(string $mode): File
    {
        if (!$this->folder->exists()) {
            throw new Exceptions("this Folder not exist.");
        }
        if (!isset($this->handle) || $this->handle === false) {
            if (substr($mode, 0, 1) == 'r') {
                if ($this->exists()) {
                    $this->handle = fopen($this->folder->path() . DS . $this->file, $mode);
                }
            } else {
                $this->handle = fopen($this->folder->path() . DS . $this->file, $mode);
            }
        }
        return $this;
    }

    /**
     * @return File
     */
    public function close(): File
    {
        fclose($this->handle);
        $this->handle = null;
        return $this;
    }

    /**
     * @param string $data
     * @param string $mode
     * @return bool
     * @throws Exceptions
     */
    public function write(string $data, bool $close = true, string $mode = 'w+'): bool
    {
        $this->create($mode);
        if (is_array($data)) {
            for ($a = 0; $a < count($data); $a++) {
                $resp[] = fwrite($this->handle, $data[$a]) !== false ? 'verdade' : 'falso';
            }
            $count = 0;
            for ($a = 0; $a < count($resp); $a++) {
                if ($resp[$a] == 'verdade') {
                    $count++;
                }
            }
            $success = count($resp) - 1 == $count ? true : false;
        } else {
            $success = fwrite($this->handle, $data) !== false ? true : false;
        }
        if ($close) {
            $this->close();
        }
        return $success;
    }

    /**
     * @return string
     */
    public function tmp_name(): string
    {
        return $this->tmp['tmp_name'];
    }

    /**
     * @param bool $nameAlone
     * @return string
     */
    public function namePath(bool $nameAlone = false): string
    {
        if ($nameAlone) {
            return substr($this->file, strripos($this->file, DS));
        }
        return $this->file;
    }
}