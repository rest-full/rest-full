<?php

declare(strict_types=1);

namespace Restfull\Filesystem;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class File
{

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var Folder
     */
    protected $folder;

    /**
     * @var Instances
     */
    protected $instance;

    /**
     * @var string
     */
    protected $file = '';

    /**
     * @var string
     */
    protected $extension = '';

    /**
     * @var resource
     */
    private $handle;

    /**
     * @var array
     */
    private $datas = [];

    /**
     * @var array
     */
    private $tmp = [];

    /**
     * @param string $file
     * @param array $arq
     */
    public function __construct(Instances $instance, string $file, array $arq = [])
    {
        $path = pathinfo($file);
        $this->folder = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'Folder',
            ['folder' => $path['dirname']]
        );
        $this->file = $path['basename'];
        if (isset($path['extension'])) {
            $this->extension = $path['extension'];
        }
        if (count($arq) > 0) {
            if (!isset($this->tmp['tmp_name'])) {
                $this->tmp = $arq;
            }
        }
        $this->instance = $instance;
        return $this;
    }

    /**
     * @param string $path
     * @param bool $deleteTmp
     *
     * @return File
     */
    public function pathFile(string $path, bool $deleteTmp = true): File
    {
        if ($path != $this->pathinfo()) {
            $path = pathinfo($path);
            $this->folder->pathFolder($path['dirname']);
            $this->file = $path['basename'];
            $this->extension = $path['extension'];
            if ($deleteTmp) {
                if (isset($this->tmp) && count($this->tmp) > 0) {
                    unset($this->tmp);
                }
            }
        }
        return $this;
    }

    /**
     * @param bool $extension
     * @return string
     */
    public function pathinfo(): string
    {
        return $this->folder->path() . DS . $this->file;
    }

    /**
     * @param bool $deleteFolder
     *
     * @return File
     */
    public function delete(bool $deleteFolder = false): bool
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
            if (count($this->folder->read()['files']) === 0) {
                $this->folder->delete();
            }
        }
        return $this->exists();
    }

    /**
     * @return bool
     */
    public function exists(string $path = ''): bool
    {
        if (!empty($path)) {
            return file_exists($path);
        }
        return file_exists($this->folder->path() . DS . $this->file);
    }

    /**
     * @param bool $count
     * @param string $mode
     *
     * @return array[]
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
     *
     * @return File
     * @throws Exceptions
     */
    public function create(string $mode): File
    {
        if (!$this->folder->exists()) {
            throw new Exceptions("this Folder not exist.");
        }
        if (!isset($this->handle) || $this->handle === false) {
            if (substr($mode, 0, 1) === 'r') {
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
     * @param string|array $data
     * @param bool $close
     * @param string $mode
     *
     * @return bool
     * @throws Exceptions
     */
    public function write($data, string $mode = 'w+', bool $close = true): bool
    {
        $this->create($mode);
        $this->datas = count($this->datas) > 0 ? array_merge($this->datas, [$data]) : [$data];
        if ($close) {
            foreach ($this->datas as $write) {
                $successes[] = fwrite($this->handle, $write) !== false ? 'sucesso' : 'fracasso';
            }
            $success = in_array('fracasso', $successes) === false;
            $this->close();
            $this->datas = [];
            return $success;
        }
        return true;
    }

    /**
     * @param bool $nameAlone
     *
     * @return string
     */
    public function namePath(bool $nameAlone = false): string
    {
        $file = $this->pathinfo();
        if ($nameAlone) {
            return substr($file, strripos($file, DS));
        }
        return $file;
    }

    /**
     * @param string $path
     * @return string
     */
    public function move(string $path, string $cut = 'webroot'): string
    {
        rename($this->pathinfo(), $path);
        $path = substr($path, stripos($path, $cut) + strlen($cut));
        return $path;
    }

    /**
     * @return string
     */
    public function tmp(string $key = 'tmp_name'): string
    {
        return $this->tmp[$key];
    }

    /**
     * @return Folder
     */
    public function folder(): Folder
    {
        return $this->folder;
    }

    /**
     * @return resource
     */
    public function handle()
    {
        return $this->handle;
    }

}
