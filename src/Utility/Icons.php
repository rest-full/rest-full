<?php

declare(strict_types=1);

namespace Restfull\Utility;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class Icons
{

    /**
     * @var array
     */
    private $sizes = [];

    /**
     * @var array
     */
    private $imageMime = ['image/jpeg' => 'jpg', 'image/jpeg' => 'jpeg', 'image/png' => 'png'];

    /**
     * @var array
     */
    private $images = [];

    /**
     * @param string $icon
     *
     * @throws Exceptions
     */
    public function __construct(Instances $instance, string $icon)
    {
        $this->sizes = [
            'android' => ['196x196', '96x96', '32x32', '16x16', '128x128'],
            'ios' => ['57x57', '114x114', '72x72', '144x144', '60x60', '120x120', '76x76', '152x152'],
            'msdos' => ['70x70', '150x150', '310x310']
        ];
        $icon = ROOT_PATH . $icon;
        $exists = [];
        foreach ($this->sizes as $key => $sizes) {
            $count = count($sizes);
            for ($a = 0; $a < $count; $a++) {
                $file = $instance->resolveClass(
                    ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'Image',
                    ['instance' => $instance, 'file' => $icon]
                );
                $this->images[$key][] = $file;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function addIco(): array
    {
        foreach ($this->images as $key => $image) {
            $count = count($image);
            for ($a = 0; $a < $count; $a++) {
                $file = pathinfo($image[$a]->pathinfo());
                $file['basename'] = substr(
                        $file['basename'],
                        0,
                        stripos($file['basename'], '.')
                    ) . '_' . $this->sizes[$key][$a] . '.' . $file['extension'];
                if (!$image[$a]->exists($file['dirname'] . DS . $file['basename'])) {
                    $image[$a]->createDifferentSizes($this->sizes[$key][$a], $file['dirname'] . DS . $file['basename']);
                }
                $names[] = $file['dirname'] . DS . $file['basename'];
            }
        }
        return $names;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    public function typeOptions(string $file): array
    {
        $options = [];
        $size = substr(substr($file, stripos($file, '_') + 1), 0, stripos(substr($file, stripos($file, '_') + 1), '.'));
        if (in_array(substr($size, 0, stripos($size, 'x')), ['144', '150', '310', '70']) !== false) {
            $name = ['square70x70logo', 'square150x150logo', 'square310x310logo'];
            $options = [
                'name' => 'msapplication-' . $name[array_search($size, $this->sizes['msdos'])],
                'content' => $file
            ];
        } else {
            if (in_array(substr($size, 0, stripos($size, 'x')), ['196', '96', '32', '16', '64', '128']) !== false) {
                $options = [
                    'rel' => 'icon',
                    'size' => $size,
                    'type' => array_search(pathinfo($file)['extension'], $this->imageMime),
                    'url' => $file
                ];
            } else {
                $options = ['rel' => 'apple-touch-icon', 'size' => $size, 'url' => $file];
            }
        }
        return $options;
    }

}
