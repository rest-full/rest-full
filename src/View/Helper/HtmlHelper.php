<?php

namespace Restfull\View\Helper;

use Restfull\Error\Exceptions;
use Restfull\Utility\Icons;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 * Class HtmlHelper
 * @package Restfull\View\Helper
 */
class HtmlHelper extends Helper
{

    /**
     * @var array
     */
    protected $templater = [
            'meta' => "<meta%s/>",
            'link' => "<a href='%s'%s>%s</a>",
            'image' => "<img src='%s'%s/>",
            'table' => "<table%s>%s</table>",
            'thead' => "<thead%s>%s</thead>",
            'tbody' => "<tbody%s>%s</tbody>",
            'tfoot' => "<tfoot%s>%s</tfoot>",
            'head' => "<th%s>%s</th>",
            'column' => "<td%s>%s</td>",
            'row' => "<tr%s>%s</tr>",
            'ul' => "<ul%s>%s</ul>",
            'ol' => "<ol%s>%s</ol>",
            'li' => "<li%s>%s</li>",
            'css' => "<link href='%s'%s/>",
            'icon' => "<link href='%s'%s/>",
            'script' => "<script src='%s'%s></script>",
            'div' => "<div%s>%s</div>",
            'span' => "<span%s>%s</span>",
            'header' => "<header%s>%s</header>",
            'main' => "<main%s>%s</main>",
            'footer' => "<footer%s>%s</footer>",
            'source' => "<source media='%s' srcset='%s'>",
            'others' => "<%s>%s</%s>"
    ];

    /**
     * @var array
     */
    private $block = [];

    /**
     * HtmlHelper constructor.
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view, $this->templater);
        return $this;
    }

    /**
     * @param string $type
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function heads(string $type, array $options = []): string
    {
        $typetag = str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), $type, __METHOD__);
        if (in_array($type, ['css', 'icon', 'script'])) {
            if ($type == 'icon') {
                return $this->icon($options['icons']);
            }
            $url = (stripos(
                            $options['url'],
                            '://'
                    ) !== false) ? $options['url'] : DS . ".." . $this->route . $options['url'];
            unset($options['url']);
            if (isset($options['options'])) {
                $options = $options['options'];
            }
            return $this->formatTemplate(
                    $typetag,
                    [
                            $url,
                            $this->formatAttributes($options)
                    ]
            );
        }
        return $this->formatTemplate(
                $typetag,
                [
                        $this->formatAttributes($options)
                ]
        );
    }

    /**
     * @param string $icon
     * @return string
     * @throws Exceptions
     */
    private function icon(string $icon): string
    {
        foreach ((new Icons($icon))->icons(str_replace('.png', '.ico', $icon)) as $icon) {
            if (isset($icon['url'])) {
                $url = (stripos(
                                $icon['url'],
                                '://'
                        ) !== false) ? $icon['url'] : DS . ".." . $this->route . $icon['url'];
                unset($icon['url']);
                $out[] = $this->formatTemplate(
                        str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'icon', __METHOD__),
                        [
                                $url,
                                $this->formatAttributes($icon)
                        ]
                );
            } else {
                $out[] = $this->formatTemplate(
                        str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'meta', __METHOD__),
                        [
                                $this->formatAttributes($icon)
                        ]
                );
            }
        }
        return implode('', $out);
    }

    /**
     * @param string $img
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function image(string $img, array $options = []): string
    {
        if (!isset($options['id'])) {
            $options['id'] = $img;
        }
        $urlImg = $img;
        if (in_array(substr($img, 0, 5), ['https', 'http:']) === false) {
            $urlImg = DS . ".." . $this->route . $img;
        }
        if (isset($options['link'])) {
            $url = $options['link']['url'];
            $urlattr = $options['link'];
            unset($options['link'], $urlattr['url']);
            $img = $this->formatTemplate(
                    __METHOD__,
                    [
                            $urlImg,
                            $this->formatAttributes($options)
                    ]
            );
            return $this->link($img, $url, $urlattr);
        }
        return $this->formatTemplate(
                __METHOD__,
                [
                        $urlImg,
                        $this->formatAttributes($options)
                ]
        );
    }

    /**
     * @param string $name
     * @param string $url
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function link(string $name, string $url, array $options = []): string
    {
        $url = $this->view->encryptLinks(
                $url,
                [
                        'number' => $options['number'] ?? 3,
                        'encrypt' => $options['encrypt'] ?? ['internal' => false, 'general' => false]
                ],
                $options['urlParams'] ?? ''
        );
        unset($options['number'], $options['urlParam'], $options['encrypt']);
        if ($url != "#" && in_array(substr($url, 0, 6), ['mailto', 'http:/', 'https:']) === false) {
            if (!isset($options['id'])) {
                $options['id'] = substr($url, strripos($url, DS) + 1);
            }
            if (is_bool($options['id'])) {
                unset($options['id']);
            }
            if (stripos($url, $this->request->server['REQUEST_SCHEME']) === false) {
                if (!empty($this->request->base)) {
                    $url = DS . ".." . $this->request->base . DS . $url;
                } else {
                    $url = DS . ".." . DS . $url;
                }
            }
        }
        if (isset($options['class']) && stripos($options['class'], 'disabled')) {
            $url = '';
        }
        if (isset($options['div'])) {
            $div = $options['div'];
            unset($options['div']);
            $link = $this->formatTemplate(
                    __METHOD__,
                    [
                            $url,
                            $this->formatAttributes($options),
                            $name
                    ]
            );
            return $this->tag($link, array_merge(['tag' => 'div'], $div));
        }
        return $this->formatTemplate(
                __METHOD__,
                [
                        $url,
                        $this->formatAttributes($options),
                        $name
                ]
        );
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function tag(string $content, array $options = []): string
    {
        if (isset($options['tag'])) {
            $tag = $options['tag'];
            unset($options['tag']);
            if (isset($options['link'])) {
                $link = $options['link'];
                unset($options['link']);
                $url = $link['url'];
                unset($link['url']);
            }
            $tag = $this->formatTemplate(
                    str_replace(
                            substr(__METHOD__, stripos(__METHOD__, "::") + 2),
                            $tag,
                            __METHOD__
                    ),
                    [
                            $this->formatAttributes($options),
                            $content
                    ]
            );
            if (isset($link)) {
                $tag = $this->link($tag, $url, $link);
            }
            return $tag;
        }
        $tag = $options['element'];
        unset($options['element']);
        return $this->formatTemplate(
                str_replace(
                        substr(__METHOD__, stripos(__METHOD__, "::") + 2),
                        'others',
                        __METHOD__
                ),
                [
                        $tag . " " . $this->formatAttributes($options),
                        $content,
                        $tag
                ]
        );
    }

    /**
     * @param string $img
     * @param int $width
     * @param int|null $height
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function picture(string $img, int $width, int $height = null, array $options = []): string
    {
        $source = [];
        $height = $height !== 0 ? $height : round($width / 16 * 9);
        $aspectRatio = $width / $height;
        switch ($width) {
            case"1200":
                $count = 4;
                $maxWidth = ['', '576', '768', '1024'];
                break;
            case"1024":
                $count = 3;
                $maxWidth = ['', '576', '768'];
                break;
            case"768":
                $count = 2;
                $maxWidth = ['', '576'];
                break;
            case"576":
                $count = 1;
                $maxWidth = [''];
                break;
            default:
                $count = 5;
                $maxWidth = ['', '576', '768', '1024', '1200'];
        }
        $src = substr($img, stripos($img, 'src') + 5);
        $src = explode(".", substr($src, 0, stripos($src, '"')));
        for ($a = 0; $a < $count; $a++) {
            if ($a == ($count - 1)) {
                break;
            }
            $size = ($a == 0) ? $width . "x" . $height : $maxWidth[($count - 1) - $a] . "x" . round(
                            $maxWidth[($count - 1) - $a] / $aspectRatio
                    );
            $source[] = $this->formatTemplate(
                    str_replace('picture', 'source', __METHOD__),
                    [
                            "(max-width: " . $maxWidth[$count - $a] . "px)",
                            $src[0] . "-" . $size . "." . $src[1]
                    ]
            );
        }
        return $this->tag(implode("", $source) . $img, ['element' => 'picture']);
    }

    /**
     * @param array $list
     * @param array $options
     * @param bool $number
     * @return string
     * @throws Exceptions
     */
    public function listing(array $list, array $options = [], bool $number = false): string
    {
        $createDiv = false;
        if (isset($options['div'])) {
            $div = $options['div'];
            unset($options['div']);
            $createDiv = true;
        }
        for ($a = 0; $a < count($list); $a++) {
            if (!isset($options['li'][$a]['id'])) {
                $options['li'][$a]['id'] = trim(str_replace(" ", "_", $list[$a]));
            } elseif (!$options['li'][$a]['id']) {
                unset($options['li'][$a]['id']);
            }
            if (isset($options['li'][$a])) {
                $li = $options['li'][$a];
                unset($options['li'][$a]);
                if (isset($li['link'])) {
                    $url = $li['link']['url'];
                    unset($li['link']['url']);
                    $link = $this->link($list[$a], $url, isset($li['link']) ? $li['link'] : []);
                    unset($li['link']);
                } else {
                    $link = $list[$a];
                }
                if (isset($li['content'])) {
                    $link .= $li['content'];
                    unset($li['content']);
                }
                $list[$a] = $this->formatTemplate(
                        str_replace('listing', 'li', __METHOD__),
                        [
                                $this->formatAttributes($li),
                                $link
                        ]
                );
            } else {
                $list[$a] = $this->formatTemplate(
                        str_replace('listing', 'li', __METHOD__),
                        [
                                $this->formatAttributes($li),
                                $list[$a]
                        ]
                );
            }
        }

        if (isset($options['li'])) {
            unset($options['li']);
        }

        if ($number) {
            if ($createDiv) {
                $listing = $this->formatTemplate(
                        str_replace('listing', 'ol', __METHOD__),
                        [
                                $this->formatAttributes($options),
                                implode("", $list)
                        ]
                );

                return $this->tag($listing, array_merge(['tag' => 'div'], $div));
            }
            return $this->formatTemplate(
                    str_replace('listing', 'ol', __METHOD__),
                    [
                            $this->formatAttributes($options),
                            implode("", $list)
                    ]
            );
        } else {
            if ($createDiv) {
                $listing = $this->formatTemplate(
                        str_replace('listing', 'ul', __METHOD__),
                        [
                                $this->formatAttributes($options),
                                implode("", $list)
                        ]
                );
                return $this->tag($listing, array_merge(['tag' => 'div'], $div));
            }
            return $this->formatTemplate(
                    str_replace('listing', 'ul', __METHOD__),
                    [
                            $this->formatAttributes($options),
                            implode("", $list)
                    ]
            );
        }
    }

    /**
     * @param array $data
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function table(array $data, array $options = []): string
    {
        $div = false;
        if (isset($options['div'])) {
            $div = true;
            $option = $options['div'];
            unset($options['div']);
        }
        $content = '';
        if (in_array('thead', array_keys($data))) {
            $thead = [
                    'cols' => count($data['thead'][0]),
                    'rows' => count($data['thead'])
            ];
            $thead = isset($options['thead']) ? $this->rowsCols(
                    'thead',
                    $thead,
                    $data['thead'],
                    $options['thead']
            ) : $this->rowsCols('thead', $thead, $data['thead']);
            $content .= isset($options['thead']) ? $this->tableElements(
                    'thead',
                    $thead,
                    $options['thead']
            ) : $this->tableElements('thead', $thead);
            unset($options['thead']);
        }
        if (is_object($data['tbody'])) {
            $data['tbody'] = $this->ordenation($data['tbody'], 'keys');
        }
        foreach ($data['tbody'] as $key => $value) {
            $tbody[$key] = $value;
        }
        if (isset($tbody)) {
            $tbody = isset($options['tbody']) ? $this->rowsCols(
                    'tbody',
                    ['cols' => count($tbody[0]), 'rows' => count($tbody)],
                    $tbody,
                    $options['tbody']
            ) : $this->rowsCols(
                    'tbody',
                    ['cols' => count($tbody[0]), 'rows' => count($tbody)],
                    $tbody
            );
            $content .= isset($options['tbody']) ? $this->tableElements(
                    'tbody',
                    $tbody,
                    $options['tbody']
            ) : $this->tableElements('tbody', $tbody);
        }
        unset($options['tbody']);
        if (in_array("tfoot", array_keys($data))) {
            $tfoot = [
                    'cols' => count($data['tfoot'][0]),
                    'rows' => count($data['tfoot'])
            ];
            $tfoot = isset($options['tfoot']) ? $this->rowsCols(
                    'tfoot',
                    $tfoot,
                    $data['tfoot'],
                    $options['tfoot']
            ) : $this->rowsCols('tfoot', $tfoot, $data['tfoot']);
            $content .= isset($options['tfoot']) ? $this->tableElements(
                    'tfoot',
                    $tfoot,
                    $options['tfoot']
            ) : $this->tableElements('tfoot', $tfoot);
            unset($options['tfoot']);
        }
        if ($div) {
            $table = $this->formatTemplate(
                    __METHOD__,
                    [
                            $this->formatAttributes($options),
                            $content
                    ]
            );
            return $this->tag($table, array_merge(['tag' => 'div'], $option));
        }
        return $this->formatTemplate(
                __METHOD__,
                [
                        $this->formatAttributes($options),
                        $content
                ]
        );
    }

    /**
     * @param string $name
     * @param array $counts
     * @param array $data
     * @param array $options
     * @return array
     * @throws Exceptions
     */
    private function rowsCols(string $name, array $counts, array $data, array $options = []): array
    {
        for ($a = 0; $a < $counts['rows']; $a++) {
            $difCol = false;
            if (count($data[$a]) != $counts['cols']) {
                $difCol = true;
                $counts['cols'] = count($data[$a]);
            }
            for ($b = 0; $b < $counts['cols']; $b++) {
                $col[$b] = $this->formatTemplate(
                        str_replace(
                                substr(__METHOD__, stripos(__METHOD__, "::") + 2),
                                (in_array($name, ['tbody', 'tfoot']) ? 'column' : substr($name, 1)),
                                __METHOD__
                        ),
                        [
                                $this->formatAttributes(
                                        isset($options[$a . " x " . $b]) ? $options[$a . " x " . $b] : []
                                ),
                                $data[$a][$b]
                        ]
                );
            }
            if ($difCol && (count($col) - count($data[$a])) > 0) {
                for ($b = (count($col) - count($data[$a])); $b <= count($col); $b++) {
                    if (isset($col[$b])) {
                        unset($col[$b]);
                    }
                }
            }
            $row[$a] = $this->formatTemplate(
                    str_replace(
                            substr(__METHOD__, stripos(__METHOD__, "::") + 2),
                            'row',
                            __METHOD__
                    ),
                    [
                            $this->formatAttributes(isset($options[$a . " x " . $b]) ? $options[$a . " x " . $b] : []),
                            implode("", $col)
                    ]
            );
        }
        return $row;
    }

    /**
     * @param string $name
     * @param array $data
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    private function tableElements(string $name, array $data, array $options = []): string
    {
        return $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), $name, __METHOD__),
                [
                        $this->formatAttributes(isset($options[substr($name, 1)]) ? $options[substr($name, 1)] : []),
                        implode("", $data)
                ]
        );
    }

    /**
     * @param string|null $type
     * @return string
     */
    public function typeDocumentHtml(string $type = null): string
    {
        $document = "<!DOCTYPE html";
        if (!is_null($type)) {
            $document .= ' ' . $type;
        }
        return $document . ">";
    }

}
