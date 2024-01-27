<?php

declare(strict_types=1);

namespace Restfull\View\Helper;

use Restfull\Error\Exceptions;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 *
 */
class HtmlHelper extends Helper
{

    /**
     * @var array
     */
    private $transferBlock = [];

    /**
     * @var array
     */
    private $block = [];

    /**
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view, [
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
            'header' => "<header%s>%s</header>",
            'main' => "<main%s>%s</main>",
            'footer' => "<footer%s>%s</footer>",
            'source' => "<source media='%s' srcset='%s'>",
            'others' => "<%s>%s</%s>"
        ]);
        return $this;
    }

    /**
     * @param string $type
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function heads(string $type, array $options = []): string
    {
        $typetag = str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), $type, __METHOD__);
        if (in_array($type, ['css', 'script'])) {
            $url = (stripos(
                    $options['url'],
                    '://'
                ) !== false) ? $options['url'] : (stripos(
                    URL,
                    'localhost'
                ) !== false ? URL : '/..') . $this->route . $options['url'];
            unset($options['url']);
            if (isset($options['options'])) {
                $options = $options['options'];
            }
            return $this->formatTemplate($typetag, [$url, $this->formatAttributes($options)]);
        }
        return $this->formatTemplate($typetag, [$this->formatAttributes($options)]);
    }

    /**
     * @param string $img
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function image(string $img, array $options = []): string
    {
        if (stripos($img, 'temp') !== false) {
            $route = $this->route;
            $this->route = substr($this->route, 0, strripos($this->route, DS)) . DS;
        }
        if (!empty(substr($this->route, strripos($this->route, DS) + 1))) {
            $this->route .= DS;
        }
        $img = stripos($img, $this->route) !== false ? DS . ".." . substr(
                $img,
                stripos($img, $this->route)
            ) : DS . ".." . $this->route . $img;
        if (!isset($options['id'])) {
            $options['id'] = $img;
        }
        $urlImg = $img;
        if (isset($route)) {
            $this->route = $route;
        }
        if (isset($options['link'])) {
            $url = $options['link']['url'];
            $urlattr = $options['link'];
            unset($options['link'], $urlattr['url']);
            $img = $this->formatTemplate(__METHOD__, [$urlImg, $this->formatAttributes($options)]);
            return $this->link($img, $url, $urlattr);
        }
        return $this->formatTemplate(__METHOD__, [$urlImg, $this->formatAttributes($options)]);
    }

    /**
     * @param string $name
     * @param string $url
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function link(string $name, string $url, array $options = []): string
    {
        $url = $this->view->encryptLinks(
            $url,
            $options['prefix'] ?? 'app',
            (isset($options['urlParams']) ? explode('|', $options['urlParams']) : [''])
        );
        unset($options['urlParams']);
        if ($url != "#" && in_array(substr($url, 0, 6), ['mailto', 'http:/', 'https:']) === false) {
            if (!isset($options['id'])) {
                $options['id'] = substr($url, strripos($url, DS) + 1);
            }
            if (is_bool($options['id'])) {
                unset($options['id']);
            }
            if (stripos($url, $this->request->server['REQUEST_SCHEME']) === false) {
                $baseUrl = URL . DS;
                if (!empty($this->request->base)) {
                    $baseUrl .= $this->request->base . DS;
                }
                if (strlen(URL) === strlen($baseUrl)) {
                    $baseUrl .= DS;
                }
                $url = $baseUrl . $url;
            }
        }
        if (isset($options['class']) && stripos($options['class'], 'disabled')) {
            $url = '';
        }
        if (empty($name)) {
            return $url;
        }
        if (isset($options['div'])) {
            $div = $options['div'];
            unset($options['div']);
            $link = $this->formatTemplate(__METHOD__, [$url, $this->formatAttributes($options), $name]);
            return $this->tag($link, 'div', $div);
        }
        return $this->formatTemplate(__METHOD__, [$url, $this->formatAttributes($options), $name]);
    }

    /**
     * @param string $content
     * @param string $name
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function tag(string $content, string $name, array $options = []): string
    {
        if (isset($options['link'])) {
            $link = $options['link'];
            unset($options['link']);
        }
        if (isset($options['data-url'])) {
            $options['data-url'] = $this->view->encryptLinks(
                $options['data-url'],
                $options['data-prefix'] ?? 'app',
                isset($options['data-urlParams']) ? explode('|', $options['data-urlParams']) : ['']
            );
            unset($options['data-urlParams'], $options['data-prefix']);
        }
        if (isset($options['disabled']) && $options['disabled']) {
            if (isset($link)) {
                $link['url'] = '#';
                $link['class'] .= ' btn-disabled';
            } else {
                $options['data-url'] = '#';
                $options['class'] .= ' btn-disabled';
            }
        }
        $tag = $this->formatTemplate(
            str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'others', __METHOD__),
            [$name . " " . $this->formatAttributes($options), $content, $name]
        );
        if (isset($link)) {
            $url = $link['url'];
            unset($link['url']);
            return $this->link($tag, $url, $link);
        }
        return $tag;
    }

    /**
     * @param string $img
     * @param int $width
     * @param int|null $height
     * @param array $options
     *
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
            if ($a === ($count - 1)) {
                break;
            }
            $size = ($a === 0) ? $width . "x" . $height : $maxWidth[($count - 1) - $a] . "x" . round(
                    $maxWidth[($count - 1) - $a] / $aspectRatio
                );
            $source[] = $this->formatTemplate(
                str_replace('picture', 'source', __METHOD__),
                ["(max-width: " . $maxWidth[$count - $a] . "px)", $src[0] . "-" . $size . "." . $src[1]]
            );
        }
        return $this->tag(implode("", $source) . $img, 'picture');
    }

    /**
     * @param array $list
     * @param array $options
     * @param bool $number
     *
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
        $count = count($list);
        for ($a = 0; $a < $count; $a++) {
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
                    $link = $this->link(
                        $list[$a],
                        $url,
                        isset($li['link']) ? $li['link'] : []
                    );
                    unset($li['link']);
                } else {
                    if (isset($li['content'])) {
                        $link .= $li['content'];
                        unset($li['content']);
                    } else {
                        $link = $list[$a];
                    }
                }
                if (isset($li['data-url'])) {
                    $baseUrl = URL . DS;
                    if (!empty($this->request->base)) {
                        $baseUrl .= $this->request->base . DS;
                    }
                    $li['data-url'] = $baseUrl . $this->view->encryptLinks(
                            $li['data-url'],
                            $li['data-prefix'] ?? 'app',
                            isset($li['data-urlParams']) ? explode('|', $li['data-urlParams']) : ['']
                        );
                    unset($li['data-urlParams'], $li['data-prefix'], $baseUrl);
                }
                $list[$a] = $this->formatTemplate(
                    str_replace('listing', 'li', __METHOD__),
                    [$this->formatAttributes($li), $link]
                );
            } else {
                $list[$a] = $this->formatTemplate(
                    str_replace('listing', 'li', __METHOD__),
                    [$this->formatAttributes($li), $list[$a]]
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
                    [$this->formatAttributes($options), implode("", $list)]
                );
                return $this->tag($listing, 'div', $div);
            }
            return $this->formatTemplate(
                str_replace('listing', 'ol', __METHOD__),
                [$this->formatAttributes($options), implode("", $list)]
            );
        }
        if ($createDiv) {
            $listing = $this->formatTemplate(
                str_replace('listing', 'ul', __METHOD__),
                [$this->formatAttributes($options), implode("", $list)]
            );
            return $this->tag($listing, 'div', $div);
        }
        return $this->formatTemplate(
            str_replace('listing', 'ul', __METHOD__),
            [$this->formatAttributes($options), implode("", $list)]
        );
    }

    /**
     * @param array $data
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function table(array $data, array $options = []): string
    {
        $div = false;
        $option = [];
        if (isset($options['div'])) {
            $div = true;
            $option = $options['div'];
            unset($options['div']);
        }
        $content = [
            'thead' => $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'thead', __METHOD__),
                ['', '']
            ),
            'tbody' => $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'tbody', __METHOD__),
                ['', '']
            ),
            'tfoot' => $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'tfoot', __METHOD__),
                ['', '']
            )
        ];
        if (is_object($data['tbody']['content'])) {
            $data['tbody']['content'] = json_decode(json_encode($data['tbody']['content']), true);
        }
        unset($options['tbody']['order']);
        foreach (array_keys($content) as $key) {
            $create = in_array($key, ['thead', 'tfoot']) !== false ? in_array($key, array_keys($data)) !== false : true;
            if ($create) {
                if (!isset($counts)) {
                    $counts = ['rows' => count($data[$key]['content']), 'cols' => count($data[$key]['content'][0])];
                } else {
                    $counts['rows'] = count($data[$key]['content']);
                }
                $rowsCols = [];
                if (count($data[$key]['content']) > 0) {
                    $rowsCols = $this->rowsCols(
                        $key,
                        $counts,
                        $data[$key]['content'],
                        $options[$key]['align'] ?? ($data[$key]['align'] ?? [])
                    );
                }
                if (isset($options[$key]['align'])) {
                    unset($options[$key]['align']);
                }
                $content[$key] = $this->formatTemplate(
                    str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), $key, __METHOD__),
                    [$this->formatAttributes($options[$key] ?? []), implode('', $rowsCols)]
                );
                unset($options[$key]);
            }
        }
        if ($div) {
            return $this->tag(
                $this->formatTemplate(__METHOD__, [$this->formatAttributes($options), implode('', $content)]),
                'div',
                $option
            );
        }
        return $this->formatTemplate(__METHOD__, [$this->formatAttributes($options), implode('', $content)]);
    }

    /**
     * @param string $name
     * @param array $counts
     * @param array $data
     * @param array $options
     *
     * @return array
     * @throws Exceptions
     */
    public function rowsCols(string $name, array $counts, array $data, array $options = []): array
    {
        $row = [];
        $newName = $name === 'tbody' || $name === 'tfoot' ? 'column' : 'head';
        for ($a = 0; $a < $counts['rows']; $a++) {
            $count = $a;
            for ($b = 0; $b < $counts['cols']; $b++) {
                $col[$b] = $this->formatTemplate(
                    str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), $newName, __METHOD__),
                    [
                        $this->formatAttributes($options[$newName === 'column' ? 'td' : 'th'][$a . " x " . $b] ?? []),
                        $data[$a][$b]
                    ]
                );
            }
            $row[$a] = $this->formatTemplate(
                str_replace(substr(__METHOD__, stripos(__METHOD__, "::") + 2), 'row', __METHOD__),
                [$this->formatAttributes($options['tr'][$a] ?? []), implode("", $col)]
            );
        }
        return $row;
    }

    /**
     * @param string|null $content
     * @param array $options
     *
     * @return mixed
     */
    public function transfersBlock(string $content = null, array $options = ['tag' => 'script'])
    {
        $tag = $options['tag'];
        unset($options['tag']);
        if (!is_null($content)) {
            if ($tag === 'script') {
                $formatTag = true;
                $content = str_replace(";", ";" . PHP_EOL, $content);
                if (strripos($content, ";") !== false) {
                    $content = substr($content, 0, strripos($content, ";") + 1);
                }
                if (!empty($this->transferBlock[$tag])) {
                    $content .= str_replace(['<script >', '</script>'], '', $this->transferBlock[$tag]);
                }
            } else {
                $formatTag = count($options) > 0;
                if (!empty($this->transferBlock[$tag])) {
                    $content .= $this->transferBlock[$tag];
                }
            }
            $this->transferBlock[$tag] = $formatTag ? $this->tag($content, $tag, $options) : $content;
            return $this;
        }
        return isset($this->transferBlock[$tag]) ? $this->transferBlock[$tag] : (count($options) > 0 ? $this->tag(
            '',
            $tag,
            $options
        ) : '');
    }

    /**
     * @param string|null $type
     *
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
