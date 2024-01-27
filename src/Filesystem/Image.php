<?php

declare(strict_types=1);

namespace Restfull\filesystem;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class Image extends File
{
    /**
     * @var array
     */
    protected $imageMime = ['image/jpeg' => 'jpg', 'image/jpeg' => 'jpeg', 'image/png' => 'png'];
    /**
     * @var resource
     */
    private $image;
    /**
     * @var string
     */
    private $type = '';

    /**
     * @param string $file
     * @param array|null $arq
     */
    public function __construct(Instances $instance, string $file, array $arq = null)
    {
        if (isset($arq)) {
            parent::__construct($instance, $file, $arq);
            return $this;
        }
        parent::__construct($instance, $file);
        return $this;
    }

    /**
     * @param string $type
     *
     * @return bool
     * @throws Exceptions
     */
    public function valid(string $type): bool
    {
        if ($type === 'Image') {
            if (in_array($this->tmp('type'), array_keys($this->imageMime)) === false) {
                throw new Exceptions(
                    "This image has no accepted extension. Extensions accepted: JPG, JPEG or PNG.", 404
                );
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $sizes
     *
     * @return Image
     */
    public function createDifferentSizes(string $sizes, string $imageTmp): Image
    {
        $this->resize(
            $this->calculating(substr($sizes, 0, stripos($sizes, 'x')), substr($sizes, stripos($sizes, 'x') + 1)),
            [$imageTmp],
            'ico'
        );
        return $this;
    }

    /**
     * @param array $size
     * @param string $imageTmp
     * @param string $path
     * @param bool $activeRotetion
     * @param bool $activeCut
     * @param string $type
     *
     * @return Image
     */
    public function resize(array $size, array $paths, string $type = 'outher'): Image
    {
        if (!isset($this->image)) {
            $this->createImage($this->extension, $paths[0]);
        }
        if ($type === 'rotation') {
            $this->rotation($paths[0]);
        }
        $thumb = $this->createImageNew($size);
        $path = $paths[1] ?? $paths[0];
        if ($type === 'cut') {
            $path = str_replace("/", '/cut', str_replace('../', '', $path));
        }
        if ($type === 'ico') {
            $file = pathinfo($path);
            $file['basename'] = substr(
                    $file['basename'],
                    0,
                    stripos($file['basename'], '.')
                ) . '_' . $size['dst_w'] . 'x' . $size['dst_h'] . '.' . $file['extension'];
            $path = $file['dirname'] . DS . $file['basename'];
        }
        if (!$this->folder->exists()) {
            $this->folder->create();
        }
        $this->createImage($this->extension, $path, $thumb);
        return $this;
    }

    /**
     * @param string $type
     * @param string $path
     * @param mixed $thumb
     *
     * @return bool|resource
     */
    private function createImage(string $type, string $path, $thumb = '')
    {
        if (!empty($thumb)) {
            if ($type === 'png') {
                return imagepng($thumb, $path);
            }
            return imagejpeg($thumb, $path);
        }
        if ($type === 'png') {
            return imagecreatefrompng($path);
        }
        return imagecreatefromjpeg($path);
    }

    /**
     * @param string $tmp_name
     *
     * @return Image
     */
    private function rotation(string $tmp_name): Image
    {
        if (isset(exif_read_data($tmp_name)['Orientation'])) {
            switch (exif_read_data($tmp_name)['Orientation']) {
                case 1:
                case 2:
                    $this->image = imagerotate($this->image, 0, 0);
                    break;
                case 3:
                case 4:
                    $this->image = imagerotate($this->image, 180, 0);
                    break;
                case 5:
                case 6:
                    $this->image = imagerotate($this->image, -90, 0);
                    break;
                case 8:
                case 7:
                    $this->image = imagerotate($this->image, 90, 0);
                    break;
            }
        }
        return $this;
    }

    /**
     * @param array $size
     *
     * @return resource
     */
    private function createImageNew(array $size)
    {
        $thumb = imagecreatetruecolor($size['dst_w'], $size['dst_h']);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled(
            $thumb,
            $this->image,
            $size['dst_x'],
            $size['dst_y'],
            $size['src_x'],
            $size['src_y'],
            $size['dst_w'],
            $size['dst_h'],
            $size['src_w'],
            $size['src_h']
        );
        return $thumb;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return array
     */
    public function calculating(int $width, int $height, bool $tmp = false): array
    {
        $path = $this->pathinfo();
        if ($tmp) {
            $path = $this->tmp();
        }
        list($srcw, $srch) = getimagesize($path);
        $srcx = 0;
        $srcy = 0;
        $imgx = $srcx / $width;
        $imgy = $srcy / $height;
        if ($imgx > $imgy) {
            $srcx = round($width - (($width / $imgx) * $imgy));
            $srcw = round(($width / $imgx) * $imgy);
        } elseif ($imgy > $imgx) {
            $srch = round(($height / $imgy) * $imgx);
            $srcy = round($height - (($height / $imgy) * $imgx));
        }
        return [
            'dst_x' => 0,
            'dst_y' => 0,
            'src_x' => (int)$srcx,
            'src_y' => (int)$srcy,
            'dst_w' => $width,
            'dst_h' => $height,
            'src_w' => (int)$srcw,
            'src_h' => (int)$srch
        ];
    }

    /**
     * @param string $imageTmp
     * @param string $path
     *
     * @return Image
     */
    public function convertFromPngToJpg(string $imageTmp, string $path): Image
    {
        $this->createImage($this->extension, $imageTmp);
        list($width, $height) = $this->size($image);
        $thumb = $this->createImageNew(
            $this->calculating($width, $height)
        );
        $this->createImage('jpg', $path, $thumb);
        $this->destroyImages([$this->image, $thumb]);
        return $this;
    }

    /**
     * @param string $imageTmp
     *
     * @return array
     */
    public function size(string $imageTmp): array
    {
        $this->image = $this->createImage($this->extension, $imageTmp);
        return [imagesx($this->image), imagesy($this->image)];
    }

    /**
     * @param array $images
     *
     * @return Image
     */
    private function destroyImages(array $images, bool $resource = true): Image
    {
        if ($resource) {
            foreach ($images as $image) {
                imagedestroy($image);
            }
            return $this;
        }
        foreach ($images as $image) {
            unlink($image);
        }
        return $this;
    }
}
