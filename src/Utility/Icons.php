<?php

namespace Restfull\Utility;

use Restfull\Filesystem\File;

/**
 * Class Icons
 * @package Restfull\Utility
 */
class Icons
{
    /**
     * @var array
     */
    private $icons = [
            '196x196',  // Android Chrome (M31+)
            '96x96',    // GoogleTV icon
            '32x32',    // New tab page in IE, taskbar button in Win 7+, Safari Reading List sidebar
            '16x16',    // The interweb standard for (almost) every browser
            '128x128',  // Chrome Web Store app icon &amp; Android icon (lo-res)
    ];

    /**
     * @var array
     */
    private $apple = [
            '57x57',    // Standard iOS home screen (iPod Touch, iPhone first generation to 3G)
            '114x114',  // iPhone retina touch icon (iOS6 or prior)
            '72x72',    // iPad touch icon (non-retina - iOS6 or prior)
            '144x144',  // iPad retina (iOS6 or prior)
            '60x60',    // iPhone touch icon (non-retina - iOS7)
            '120x120',  // iPhone retina touch icon (iOS7)
            '76x76',    // iPad touch icon (non-retina - iOS7)
            '152x152',  // iPad retina touch icon (iOS7)
    ];

    /**
     * @var array
     */
    private $msdos = [
            '144x144',            // IE10 Metro tile for pinned site
            '70x70',        // Win 8.1 Metro tile image (small)
            '150x150',    // Win 8.1 Metro tile image (square)
            '310x310',
    ];

    /**
     * Icons constructor.
     * @param string $icon
     */
    public function __construct(string $icon)
    {
        $this->add_image(ROOT_PATH . $icon, array_merge(array_merge($this->icons, $this->apple), $this->sizes()));
        $file = new File(ROOT_PATH . substr($icon, 0, stripos($icon, DS)) . DS . 'browserconfig.xml');
        if (!$file->exists()) {
            $file->write('<?xml version="1.0" encoding="utf-8"?>', false);
            $file->write('<browserconfig>', false);
            $file->write('<msapplication>', false);
            $file->write('<tile>', false);
            $file->write('<square70x70logo src="/ms-icon-70x70.ico"/>', false);
            $file->write('<square150x150logo src="/ms-icon-150x150.ico"/>', false);
            $file->write('<square310x310logo src="/ms-icon-310x310.ico"/>', false);
            $file->write('<TileColor>#ffffff</TileColor>', false);
            $file->write('</tile>', false);
            $file->write('</msapplication>', false);
            $file->write('</browserconfig>', false);
        }
        return $this;
    }

    /**
     * @param string $file
     * @param array $sizes
     * @return bool
     */
    public function add_image($file, $sizes = []): bool
    {
        $icon = imagecreatefromstring(file_get_contents($file));
        $size = getimagesize($file);
        if (false === $size || false === $icon) {
            return false;
        }
        foreach ($sizes as $size) {
            list($width, $height) = explode('x', $size);
            $thumb = imagecreatetruecolor($width, $height);
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $source_width = imagesx($icon);
            $source_height = imagesy($icon);
            imagecopyresampled($thumb, $icon, 0, 0, 0, 0, $width, $height, $source_width, $source_height);
            $this->_add_image_data($thumb);
        }
        return true;
    }

    /**
     * @param $icon
     */
    private function _add_image_data($icon)
    {
        $width = imagesx($icon);
        $height = imagesy($icon);
        $pixel_data = array();
        $opacity_data = array();
        $current_opacity_val = 0;
        for ($y = $height - 1; $y >= 0; $y--) {
            for ($x = 0; $x < $width; $x++) {
                $pixel_data[] = (imagecolorat($icon, $x, $y) & 0xFFFFFF) | (0xFF000000 & (((1 - (((imagecolorat(
                                                                                        $icon,
                                                                                        $x,
                                                                                        $y
                                                                                ) & 0x7F000000) >> 24) / 127)) * 255) << 24));
                $opacity = (((1 - (((imagecolorat($icon, $x, $y) & 0x7F000000) >> 24) / 127)) * 255) <= 127) ? 1 : 0;
                $current_opacity_val = ($current_opacity_val << 1) | $opacity;
                if ((($x + 1) % 32) == 0) {
                    $opacity_data[] = $current_opacity_val;
                    $current_opacity_val = 0;
                }
            }
            if (($x % 32) > 0) {
                while (($x++ % 32) > 0) {
                    $current_opacity_val = $current_opacity_val << 1;
                }
                $opacity_data[] = $current_opacity_val;
                $current_opacity_val = 0;
            }
        }
        $image_header_size = 40;
        $color_mask_size = $width * $height * 4;
        $opacity_mask_size = (ceil($width / 32) * 4) * $height;
        $data = pack('VVVvvVVVVVV', 40, $width, ($height * 2), 1, 32, 0, 0, 0, 0, 0, 0);
        foreach ($pixel_data as $color) {
            $data .= pack('V', $color);
        }
        foreach ($opacity_data as $opacity) {
            $data .= pack('N', $opacity);
        }
        $image = array(
                'width' => $width,
                'height' => $height,
                'color_palette_colors' => 0,
                'bits_per_pixel' => 32,
                'size' => $image_header_size + $color_mask_size + $opacity_mask_size,
                'data' => $data,
        );
        $this->_images[] = $image;
    }

    /**
     * @return array
     */
    private function sizes(): array
    {
        $a = 0;
        $msSizes = [];
        foreach ($this->msdos as $item) {
            $msSizes[] = str_replace(['mstile-', '.png'], [null], $item);
            $a++;
        }
        return $msSizes;
    }

    /**
     * @param string $file
     * @return bool|array
     */
    public function icons($file)
    {
        $newFile = $file;
        $files = $written = [];
        $sizes = array_merge(array_merge($this->icons, $this->apple), $this->sizes());
        for ($a = 0; $a < count($sizes); $a++) {
            $file = new File(str_replace('.ico', '_' . $sizes[$a] . '.ico', $newFile));
            if (!$file->exists()) {
                $written[] = $file->write($this->_get_ico_data());
            } else {
                $written[] = true;
            }
            $files[] = DS . $file->pathinfo();
        }
        $count = 0;
        foreach ($written as $result) {
            if ($result) {
                $count++;
            }
        }
        if (count($sizes) != $count) {
            return false;
        }
        return $this->typeOptions($files);
    }

    /**
     * @return false|string
     */
    private function _get_ico_data()
    {
        if (!is_array($this->_images) || empty($this->_images)) {
            return false;
        }
        $data = pack('vvv', 0, 1, count($this->_images));
        $pixel_data = '';
        $icon_dir_entry_size = 16;
        $offset = 6 + ($icon_dir_entry_size * count($this->_images));
        foreach ($this->_images as $image) {
            $data .= pack(
                    'CCCCvvVV',
                    $image['width'],
                    $image['height'],
                    $image['color_palette_colors'],
                    0,
                    1,
                    $image['bits_per_pixel'],
                    $image['size'],
                    $offset
            );
            $pixel_data .= $image['data'];
            $offset += $image['size'];
        }
        $data .= $pixel_data;
        unset($pixel_data);
        return $data;
    }

    /**
     * @param array $files
     * @return array
     */
    private function typeOptions(array $files)
    {
        $options = [];
        $count = 0;
        foreach ($files as $file) {
            $a = substr($file, stripos($file, '_') + 1);
            $size = substr(
                    substr($file, stripos($file, '_') + 1),
                    0,
                    stripos(substr($file, stripos($file, '_') + 1), '.')
            );
            if ($count == 1 && in_array(substr($size, 0, stripos($size, 'x')), ['144', '150', '310', '70']) !== false) {
                $name = ['TileImage', 'square70x70logo', 'square150x150logo', 'square310x310logo'];
                $options[] = [
                        'name' => 'msapplication-' . $name[array_search($size, $this->msdos)],
                        'content' => $file
                ];
            } else {
                if (in_array(
                                substr($size, 0, stripos($size, 'x')),
                                ['196', '96', '32', '16', '64', '128']
                        ) !== false) {
                    $options[] = ['rel' => 'icon', 'size' => $size, 'type' => 'image/x-icon', 'url' => $file];
                } else {
                    $options[] = ['rel' => 'apple-touch-icon', 'size' => $size, 'url' => $file];
                }
            }
            if (substr($size, 0, stripos($size, 'x')) == '144') {
                $count = 1;
            }
        }
        $options[] = ['name' => 'msapplication-TileColor', 'content' => '#FFFFFF'];
        $options[] = ['name' => 'msapplication-config', 'content' => '/favicons/browserconfig.xml'];
        return $options;
    }
}


