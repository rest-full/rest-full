<?php

declare(strict_types=1);

namespace Restfull\Filesystem;

use DirectoryIterator;

/**
 *
 */
class Folder
{

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var array
     */
    private $info = [];

    /**
     * @var bool
     */
    private $exist = false;

    /**
     * @param string|null $folder
     */
    public function __construct(string $folder = null)
    {
        if (!isset($folder)) {
            $this->path = ROOT_PATH;
        } else {
            $this->path = $folder;
            $this->pathFolder($folder);
        }
        return $this;
    }

    /**
     * @param string $folder
     *
     * @return Folder
     */
    public function pathFolder(string $folder): Folder
    {
        if ($this->path != $folder) {
            $this->path = $folder;
            if (stripos(
                    $folder,
                    substr(substr(ROOT, 0, -1), strripos(substr(ROOT, 0, -1), DS_REVERSE) + 1)
                ) === false) {
                $path = '';
                if (substr($folder, 0, strlen("Restfull")) === "Restfull") {
                    $path = RESTFULL;
                    $folder = substr($folder, strlen("Restfull") + 1);
                } else {
                    if (stripos($folder, 'webroot') === false) {
                        $folder = ROOT_PATH . $folder;
                    }
                }
                $this->path = $path . $folder;
            }
        }
        return $this;
    }

    /**
     * @param string|null $path
     * @param int $mode
     *
     * @return bool
     */
    public function create(string $path = null, int $mode = 0755): bool
    {
        if (is_null($path)) {
            $path = $this->path();
        } else {
            $path = $this->path() . $path;
        }
        if (!is_dir($path)) {
            if (count($this->info) === 0) {
                $this->info($path);
            }
            $create = true;
            if (stripos($this->info['basename'], DS)) {
                $create = $this->create($this->info['basename']);
            }
            if ($create) {
                if ($this->exists()) {
                    umask(0);
                    $success = mkdir($this->info['filename'], $mode, true) !== false ? true : false;
                    umask(0);
                }
                return $success;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return Folder
     */
    public function info(string $path): Folder
    {
        $this->info = pathinfo($path);
        return $this;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * @param string|null $path
     *
     * @return Folder
     */
    public function delete(string $path = null): Folder
    {
        if (is_null($path)) {
            $path = $this->path();
        } else {
            if (is_dir($path) && empty($path)) {
                $path = $this->path() . DS . $path;
            }
        }
        $filesDirs = $this->read($path);
        $this->info($path);
        if (count($filesDirs['diretory']) === 0 && count($filesDirs['files']) === 0) {
            rmdir($this->info['filename']);
        }
        if ($this->exists()) {
            if (isset($filesDirs['diretory'])) {
                $count = count($filesDirs['diretory']);
                for ($a = 0; $a < $count; $a++) {
                    $this->delete($filesDirs['diretory'][$a]);
                }
            }
            $count = count($filesDirs['files']);
            if ($count > 0) {
                for ($a = 0; $a < $count; $a++) {
                    $this->delete($filesDirs['files'][$a]);
                }
            }
            if (count($filesDirs['diretory']) === 0 && count($filesDirs['files'] === 0)) {
                rmdir($this->info['filename']);
            }
        }
        $this->exist = $this->exists();
        return $this;
    }

    /**
     * @param string|null $path
     *
     * @return array[]
     */
    public function read(string $path = null): array
    {
        $dirs = $files = [];
        if (is_null($path)) {
            $path = $this->path();
        } else {
            if (is_dir($this->path() . DS . $path) && !empty($path)) {
                $path = $this->path() . DS . $path;
            }
        }
        $read = new DirectoryIterator($path);
        foreach ($read as $item) {
            if ($item->isDot()) {
                continue;
            }
            $reading = $item->getFilename();
            if ($reading != 'empty') {
                if ($item->isDir()) {
                    $dirs[] = $reading;
                } else {
                    $files[] = $reading;
                }
            }
        }
        if (count($dirs) > 0 && count($files) > 0) {
            return ['diretory' => $dirs, 'files' => $files];
        } elseif (count($dirs) > 0 || count($files) > 0) {
            if (count($files) === 0) {
                return ['diretory' => $dirs];
            } elseif (count($dirs) === 0) {
                return ['files' => $files];
            }
        }
        return ['diretory' => [], 'files' => []];
    }

    /**
     * @return bool
     */
    public function issets(): bool
    {
        return $this->exist;
    }

}
