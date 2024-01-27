<?php

declare(strict_types=1);

namespace Restfull\View\Helper;

use Restfull\Error\Exceptions;
use Restfull\Htmltopdf\HtmlToPdf;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 *
 */
class PdfHelper extends Helper
{

    /**
     * @var HtmlToPdf
     */
    private $pdf;

    /**
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view, [
            'meta' => "<meta%s/>",
            'css' => "<link href='%s'%s/>",
            'icon' => "<link href='%s'%s/>",
            'script' => "<script src='%s'%s></script>",
            'table' => "<table%s>%s</table>",
            'thead' => "<thead%s>%s</thead>",
            'tbody' => "<tbody%s>%s</tbody>",
            'tfoot' => "<tfoot%s>%s</tfoot>",
            'head' => "<th%s>%s</th>",
            'column' => "<td%s>%s</td>",
            'row' => "<tr%s>%s</tr>",
            'span' => "<span%s>%s</span>",
            'image' => "<img src='%s'%s/>",
            'others' => "<%s>%s</%s>"
        ]);
        $this->pdf = $view->request->bootstrap('pdf');
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
                ) !== false) ? $options['url'] : DS . ".." . $this->route . $options['url'];
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
                $rowsCols = [];
                if (count($data[$key]['content']) > 0) {
                    $rowsCols = $this->rowsCols(
                        $key,
                        $data[$key]['counts'] ?? [
                        'cols' => [count($data[$key]['content'][0])],
                        'rows' => count($data[$key]['content'])
                    ],
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
    private function rowsCols(string $name, array $counts, array $data, array $options = []): array
    {
        $row = [];
        $newName = $name === 'tbody' || $name === 'tfoot' ? 'column' : 'head';
        for ($a = 0; $a < $counts['rows']; $a++) {
            $count = $a;
            if ($a > 0) {
                $count = isset($counts['cols'][$a]) ? $a : 0;
            }
            for ($b = 0; $b < $counts['cols'][$count]; $b++) {
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
                [$this->formatAttributes($options['tr'][$a . " x " . $b] ?? []), implode("", $col)]
            );
        }
        return $row;
    }

    /**
     * @param string $content
     * @param string $tag
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function tag(string $content, string $tag, array $options = []): string
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
     * @param array $data
     * @param string $path
     * @param string $modo
     * @param array|null $index
     *
     * @return PdfHelper
     * @throws Exceptions
     */
    public function gerarPdf(array $data, string $path, string $modo = 'FD', array $index = null): PdfHelper
    {
        if (isset($data['protection'])) {
            if (isset($data['display'])) {
                $this->pdf->validateHTML($data['html'], $data['display'], $data['protection']);
            } else {
                $this->pdf->validateHTML($data['html'], null, $data['protection']);
            }
        } elseif (isset($data['display'])) {
            $this->pdf->validateHTML($data['html'], $data['display']);
        } else {
            $this->pdf->validateHTML($data['html']);
        }
        $this->pdf->gerarPDF([$path, $modo], $index);
        return $this;
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