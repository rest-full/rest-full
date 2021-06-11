<?php

namespace Restfull\filesystem;

use Restfull\Error\Exceptions;

/**
 * Class Image
 * @package Restfull\filesystem
 */
class Image extends File
{

    /**
     * @var array
     */
    private $imageMime = ['image/jpeg', 'image/png', 'image/vnd.microsoft.icon'];

    /**
     * @var array
     */
    private $maxWidth = [576, 768, 1024, 1200];

    /**
     * @var array
     */
    private $size = [];

    /**
     * Image constructor.
     * @param string $image
     * @param array|null $arq
     * @throws Exceptions
     */
    public function __construct(string $image = '', array $arq = null)
    {
        if (!empty($image)) {
            if (in_array(mime_content_type($image), $this->imageMime) === false) {
                throw new Exceptions(
                        "This image has no accepted extension. Extensions accepted: JPG, JPEG, PNG or ICON.", 404
                );
            }
        }
        if (isset($arq)) {
            parent::__contruct($image, $arq);
            return $this;
        }
        parent::__construct($image);
        return $this;
    }

    /**
     * @param array $sizes
     * @return Image
     */
    public function maxWidth(array $sizes): image
    {
        foreach ($sizes as $size) {
            $max[] = stripos($size, 'x') !== false ? substr($size, 0, stripos($size, 'x')) : $size;
        }
        sort($max);
        $this->maxWidth = $max;
        return $this;
    }

    /**
     * @param string $name
     * @param int $width
     * @param int $height
     * @param string $folder
     * @return array
     */
    public function createDifferentSizes(string $name, int $width = 0, int $height = 0, string $folder): array
    {
        $widthPath = $width != 0 ? $width : imagesx($this->file);
        $heightPath = $height != 0 ? $height : imagesy($this->file);
        for ($a = 0; $a < count($this->maxWidth); $a++) {
            if ($this->maxWidth[$a] <= $widthPath) {
                $newWidth[] = $this->maxWidth[$a];
            }
        }
        $names = [];
        for ($a = 0; $a < count($newWidth); $a++) {
            $newHeight = ($newWidth[$a] * $heightPath) / $widthPath;
            if (!file_exists($folder . DS . $name . '_' . $newWidth[$a] . 'x' . $newHeight . '.png')) {
                $this->calculating($newWidth[$a], $newHeight);
                $names[] = $this->resize($folder . DS . $name . '_' . $newWidth[$a] . 'x' . $newHeight);
            }
        }
        return $names;
    }

    /**
     * @param int $width
     * @param int $height
     * @return Image
     */
    private function calculating(int $width, int $height): Image
    {
        list($srcw, $srch) = getimagesize($this->folder->path() . DS . $this->file);
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
        $this->size = [
                'dst_x' => 0,
                'dst_y' => 0,
                'src_x' => (int)$srcx,
                'src_y' => (int)$srcy,
                'dst_w' => $width,
                'dst_h' => $height,
                'src_w' => (int)$srcw,
                'src_h' => (int)$srch
        ];
        return $this;
    }

    /**
     * @param string $name
     * @return string
     */
    private function resize(string $name): string
    {
        $file = substr($name, 0, strripos($name, DS)) . DS . $this->file;
        $extension = 'png';
        $folder = substr($name, strlen(ROOT_PATH));
        if (mime_content_type($file) != 'image/vnd.microsoft.icon') {
            $extension = (mime_content_type($file) != 'image/vnd.microsoft.icon' ? substr(
                    mime_content_type($file),
                    stripos(
                            mime_content_type($file),
                            DS
                    ) + 1
            ) : 'png');
        }
        $a = file_exists($file);
        $image = $this->createImage($extension, $file);
        $thumb = imagecreatetruecolor($this->size['dst_w'], $this->size['dst_h']);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled(
                $thumb,
                $image,
                $this->size['dst_x'],
                $this->size['dst_y'],
                $this->size['src_x'],
                $this->size['src_y'],
                $this->size['dst_w'],
                $this->size['dst_h'],
                $this->size['src_w'],
                $this->size['src_h']
        );
        $this->createImage($extension, $name . '.' . $extension, $thumb);
        return $name;
    }

    /**
     * @param string $type
     * @param string $path
     * @param string $thumb
     * @return bool|\GdImage|resource
     */
    public function createImage(string $type, string $path, $thumb = '')
    {
        $path = str_replace('.ico', '.' . $type, str_replace(DS_REVERSE, DS, $path));
        if (!empty($thumb)) {
            if ($type == 'png') {
                return imagepng($thumb, $path);
            }
            return imagejpeg($thumb, $path);
        }
        if ($type == 'png') {
            return imagecreatefrompng($path);
        }
        return imagecreatefromjpeg($path);
    }

    /**
     * @return string
     */
    public function pathinfo(): string
    {
        return $this->file;
    }

    /**
     * @param string $tmp
     * @return Image
     */
    public function rotation(string $tmp): Image
    {
        if (isset(exif_read_data($this->tmp)['Orientation'])) {
            switch (exif_read_data($this->tmp)['Orientation']) {
                case 8:
                    $this->tmp = imagerotate($this->tmp, 90, 0);
                    break;
                case 3:
                    $this->tmp = imagerotate($this->tmp, 180, 0);
                    break;
                case 6:
                    $this->tmp = imagerotate($this->tmp, -90, 0);
                    break;
                default:
                    $this->tmp = imagerotate($this->tmp, 0, 0);
            }
        }
        return $this;
    }

    /**
     * @param array $positions
     * @param int $width
     * @param int $height
     * @param bool $tmp
     * @return $this
     * @throws Exceptions
     */
    public function cut(array $positions, int $width, int $height, bool $tmp = false): Image
    {
        if (!isset($positions['size'])) {
            throw new Exceptions('Image size does not exist.', 404);
        }
        $image = $tmp ? $this->tmp : $this->file;
        if (!isset($positions['count'])) {
            $positions['count'] = [0, 0, 0, 0];
        }
        $type = mime_content_type($image);
        $function = "imagecreatefrom" . substr($type, stripos($type, DS) + 1);
        imagecopyresampled(
                imagecreatetruecolor($width, $height),
                $function($image),
                0,
                0,
                $positions['size'][0] + $positions['count'][0],
                $positions['size'][1] + $positions['count'][1],
                $width,
                $height,
                $positions['size'][2] + $positions['count'][2],
                $positions['size'][3] + $positions['count'][3]
        );
        $this->file = str_replace("/", '/cut', str_replace('../', '', $this->file));
        $function = 'image' . substr($type, stripos($type, DS) + 1);
        $function(imagecreatetruecolor($width, $height), $this->file);
        return $this;
    }

    /**
     * @return Folder
     */
    public function folder(): Folder
    {
        return $this->folder;
    }
}