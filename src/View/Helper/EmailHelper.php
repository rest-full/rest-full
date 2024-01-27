<?php

declare(strict_types=1);

namespace Restfull\View\Helper;

use PHPMailer\PHPMailer\Exception;
use Restfull\Error\Exceptions;
use Restfull\Mail\Email;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 *
 */
class EmailHelper extends Helper
{

    /**
     * @var Email
     */
    private $email;

    /**
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view, [
            'table' => "<table%s>%s</table>",
            'thead' => "<thead%s>%s</thead>",
            'tbody' => "<tbody%s>%s</tbody>",
            'tfoot' => "<tfoot%s>%s</tfoot>",
            'head' => "<th%s>%s</th>",
            'column' => "<td%s>%s</td>",
            'row' => "<tr%s>%s</tr>",
            'input' => "<input type='%s' name='%s' value='%s'%s/>",
            'label' => "<label%s>%s</label>",
            'option' => "<option value='%s'%s>%s</option>",
            'select' => "<select name='%s'%s>%s</select>",
            'textarea' => "<textarea name='%s' rows='%s' cols='%s'%s>%s</textarea>",
            'span' => "<span%s>%s</span>",
            'image' => "<img src='%s'%s/>",
            'others' => "<%s>%s</%s>"
        ]);
        $this->email = $view->request->bootstrap('email');
        return $this;
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
     * @param string $name
     * @param string $value
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function input(string $name, string $value, array $options = []): string
    {
        $input = '';
        $div = true;
        if (isset($options['div'])) {
            if ($options['div'] !== false) {
                if (count($options['div']) > 0) {
                    if (isset($options['div']['context'])) {
                        $input .= $options['div']['context'];
                        unset($options['div']['context']);
                    }
                    $optionsDiv = $options['div'];
                } else {
                    $div = false;
                }
            } else {
                $div = $options['div'];
            }
            unset($options['div']);
        } else {
            $optionsDiv = [];
        }
        $type = $options['type'];
        unset($options['type']);
        if (isset($options['span'])) {
            foreach ($options['span'] as $span) {
                if (count($span) > 0) {
                    $optionsSpan = (isset($span['options'])) ? $this->formatAttributes($span['options']) : '';
                    $input .= $this->formatTemplate(
                        str_replace('input', 'span', __METHOD__),
                        [$optionsSpan, $span['context']]
                    );
                }
            }
            unset($options['span']);
        }
        if (isset($options['img'])) {
            $img = (isset($options['img']['options'])) ? $this->formatAttributes($options['img']['options']) : '';
            $input .= $this->formatTemplate(
                str_replace('input', 'image', __METHOD__),
                [DS . ".." . DS . $this->route . $options['img']['context'], $img]
            );
            unset($options['img']);
        }
        if (in_array($type, ['button', 'submit', 'reset', 'file']) === false) {
            if (!isset($options['id'])) {
                $options['id'] = $name;
            } else {
                if ($options['id'] === false) {
                    unset($options['id']);
                }
            }
            if (isset($options['label'])) {
                if ($options['label'] !== false) {
                    if (isset($options['label']['div'])) {
                        $divLabel = $options['label']['div'];
                        unset($options['label']['div']);
                    }
                    $label = $this->label($name, $options['label']);
                    if (isset($divLabel)) {
                        $label = $this->tag($label, 'div', $divLabel);
                    }
                } else {
                    $label = '';
                }
                unset($options['label']);
            } else {
                $label = $this->label($name);
            }
            if (stripos($value, '"')) {
                $value = str_replace('"', '&quot;', $value);
            }
            if (stripos($value, "'")) {
                $value = str_replace("'", "&apos;", $value);
            }
            $input .= $label . $this->formatTemplate(
                    __METHOD__,
                    [$type, $name, $value, $this->formatAttributes($options)]
                );
            if ($div) {
                return $this->tag($input, 'div', $optionsDiv);
            }
            return $input;
        }
        if (isset($options['onclick'])) {
            if (!isset($options['onclick']['url'])) {
                throw new Exceptions('It has to have onclick the value and the url key.');
            }
            $options['data-onclick'] = DS . '..' . DS . $this->view->encryptLinks(
                    $options['onclick']['url'],
                    $options['onclick']['prefix'] ?? 'app',
                    isset($options['onclick']['urlParams']) ? explode('|', $options['onclick']['urlParams']) : ['']
                );
            if (!empty($this->request->base)) {
                $options['data-onclick'] = str_replace(
                    DS . '..' . DS,
                    DS . '..' . $this->request->base . DS,
                    $options['data-onclick']
                );
            }
            unset($options['onclick']);
        }
        $input .= $this->formatTemplate(__METHOD__, [$type, $name, $value, $this->formatAttributes($options)]);
        if ($div) {
            return $this->tag($input, 'div', $optionsDiv);
        }
        return $input;
    }

    /**
     * @param string $name
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function label(string $name, array $options = []): string
    {
        if (!isset($options)) {
            $text = $label['for'] = $name;
        } else {
            $text = $name;
            if (isset($options['text'])) {
                $text = $options['text'];
                unset($options['text']);
            }
            if (!isset($options['for'])) {
                $options['for'] = $name;
            }
        }
        if (stripos($options['for'], '>')) {
            unset($options['for']);
        }
        if (!isset($options['div'])) {
            return $this->formatTemplate(__METHOD__, [$this->formatAttributes($options), $text]);
        }
        $div = $options['div'];
        unset($options['div']);
        return $this->tag($this->formatTemplate(__METHOD__, [$this->formatAttributes($options), $text]), 'div', $div);
    }

    /**
     * @param string $name
     * @param array $datas
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function select(string $name, array $datas, array $options = []): string
    {
        $div = true;
        if (isset($options['div'])) {
            if ($options['div'] !== false) {
                $optionsDiv = $options['div'];
            } else {
                $div = $options['div'];
            }
            unset($options['div']);
        } else {
            $optionsDiv = [];
        }
        if (!isset($options['id'])) {
            $options['id'] = $name;
        } else {
            if ($options['id'] === false) {
                unset($options['id']);
            }
        }
        if (isset($options['label'])) {
            if ($options['label'] !== false) {
                if (isset($options['label']['div'])) {
                    $divLabel = $options['label']['div'];
                    unset($options['label']['div']);
                }
                $label = $this->label($name, $options['label']);
                if (isset($divLabel)) {
                    $label = $this->tag($label, 'div', $divLabel);
                }
            } else {
                $label = '';
            }
            unset($options['label']);
        } else {
            $label = $this->label($name);
        }
        unset($options['type']);
        if (isset($options['option'])) {
            $option = $options['option'];
            unset($options['option']);
        }
        if (isset($option['disabled'])) {
            $disabled = $option['disabled'];
            unset($option['disabled']);
        }
        foreach ($datas as $data) {
            $value = $data['value'];
            $key = $data['key'];
            unset($data['value'], $data['key']);
            if (isset($disabled)) {
                if ($disabled[$data['value']] === 1) {
                    $option['class'] = 'disabled';
                } else {
                    if (isset($option['class'])) {
                        unset($option['class']);
                    }
                }
            }
            if (count($data) > 0) {
                $option = array_merge($option ?? [], $data);
            }
            $out[] = $this->option($key, $value, $options['default'] ?? '', $option ?? []);
        }
        $select = $label . $this->formatTemplate(
                __METHOD__,
                [$name, $this->formatAttributes($options), (isset($out) ? implode("", $out) : '')]
            );
        if ($div) {
            return $this->tag($select, 'div', $optionsDiv);
        }
        return $select;
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $default
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    private function option(string $key, string $value, $default = '', array $options = []): string
    {
        if ($key === $default) {
            $options['selected'] = 'true';
        }
        if (isset($options['data-url'])) {
            $dataUrl = $options['data-url'];
            if (isset($dataUrl['shortenEnable'])) {
                $dataUrl['urlParams'] .= $dataUrl['shortenEnable'] != '|ajax|nao' ? $dataUrl['shortenEnable'] : '|ajax|nao';
                unset($dataUrl['shortenEnable']);
            }
            $options['data-url'] = $this->view->encryptLinks(
                $dataUrl['url'],
                $dataUrl['prefix'] ?? 'app',
                isset($dataUrl['urlParams']) ? explode('|', $dataUrl['urlParams']) : ['']
            );
            $baseUrl = URL . DS;
            if (!empty($this->request->base)) {
                $baseUrl .= $this->request->base . DS;
            }
            $options['data-url'] = $baseUrl . $options['data-url'];
        }
        return $this->formatTemplate(
            __METHOD__,
            [
                (substr($key, 0, stripos($key, " ")) === "selecione") ? '' : $key,
                $this->formatAttributes($options),
                $value
            ]
        );
    }

    /**
     * @param string $name
     * @param array $datas
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function radio(string $name, array $datas, array $options = []): string
    {
        $divpai = [];
        $divChildren = [];
        $divChildren2 = [];
        if (isset($options['div'])) {
            $divpai = $options['div'];
            if (isset($divpai['div'])) {
                $divChildren = $divpai['div'];
                unset($divpai['div']);
                if (isset($divChildren['div'])) {
                    $divChildren2 = $divChildren['div'];
                    unset($divChildren['div']);
                }
            }
            unset($options['div']);
        }
        if (!isset($options['id'])) {
            $options['id'] = $name;
        } else {
            if ($options['id'] === false) {
                unset($options['id']);
            }
        }
        if (isset($options['label'])) {
            if ($options['label'] !== false) {
                if (isset($options['label']['div'])) {
                    $divLabel = $options['label']['div'];
                    unset($options['label']['div']);
                }
                $label = $this->label($name, $options['label']);
                if (isset($divLabel)) {
                    $label = $this->tag($label, 'div', $divLabel);
                }
            } else {
                $label = '';
            }
            unset($options['label']);
        } else {
            $label = $this->label($name);
        }
        if (isset($options['option']['span'])) {
            $span = $options['option']['span'];
            unset($options['option']['span']);
        }
        $type = substr(__METHOD__, stripos(__METHOD__, "::") + 2);
        unset($options['type']);
        foreach ($datas as $data) {
            $option = [];
            if (isset($options['option'])) {
                $option = $options['option'];
            }
            if (isset($options['default'])) {
                if ($data['key'] === $options['default']) {
                    $option['checked'] = 'true';
                }
            }
            $out = $this->formatTemplate(
                str_replace($type, 'input', __METHOD__),
                [$type, $name, $data['key'], $this->formatAttributes($option)]
            );
            if (isset($span)) {
                $out .= $this->tag($span[$data['key']]['context'], 'span', $span[$data['key']]['options']);
            }
            $labelChildren = (isset($data['option']) ? $this->label($data['value'], $data['option']) : $this->label(
                $data['value']
            ));
            if (count($divChildren) > 0) {
                $div = '';
                if (isset($divChildren2['span'])) {
                    $spanOption = array_merge($divChildren2['span'], ['data-radio' => $data['key']]);
                    $classSpanOption = $divChildren2['span']['class'];
                    unset($divChildren2['span']);
                } else {
                    $spanOption['data-radio'] = $data['key'];
                }
                if (isset($option['checked'])) {
                    $spanOption['class'] .= ' ' . ($spanOption['active'] ?? $classSpanOption . '-active');
                } else {
                    if ($classSpanOption != $spanOption['class']) {
                        $spanOption['class'] = $classSpanOption;
                    }
                }
                if (isset($spanOption)) {
                    $div = $this->tag($this->tag('', 'span', $spanOption), 'div', $divChildren2);
                }
                $input[] = $this->tag($out . $div . $labelChildren, 'div', $divChildren);
            } else {
                $input[] = $out . $labelChildren;
            }
        }
        if (isset($options['option'])) {
            unset($options['option']);
        }
        if (isset($options['default'])) {
            unset($options['default']);
        }
        return $this->tag($label . implode('', $input), 'div', $divpai);
    }

    /**
     * @param string $name
     * @param array $datas
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function checkbox(string $name, array $datas, array $options = []): string
    {
        $divpai = [];
        $divChildren = [];
        $divChildren2 = [];
        if (isset($options['div'])) {
            $divpai = $options['div'];
            if (isset($divpai['div'])) {
                $divChildren = $divpai['div'];
                unset($divpai['div']);
                if (isset($divChildren['div'])) {
                    $divChildren2 = $divChildren['div'];
                    $classChildren2 = $divChildren2['span']['class'];
                    $active = ' ' . $divChildren2['span']['active'];
                    unset($divChildren['div'], $divChildren2['span']['active']);
                }
            }
            unset($options['div']);
        }
        if (!isset($options['id'])) {
            $options['id'] = $name;
        } else {
            if ($options['id'] === false) {
                unset($options['id']);
            }
        }
        if (isset($options['label'])) {
            if ($options['label'] !== false) {
                if (isset($options['label']['div'])) {
                    $divLabel = $options['label']['div'];
                    unset($options['label']['div']);
                }
                $label = $this->label($name, $options['label']);
                if (isset($divLabel)) {
                    $label = $this->tag($label, 'div', $divLabel);
                }
            } else {
                $label = '';
            }
            unset($options['label']);
        } else {
            $label = $this->label($name);
        }
        if (isset($options['option']['span'])) {
            $span = $options['option']['span'];
            unset($options['option']['span']);
        }
        $type = substr(__METHOD__, stripos(__METHOD__, "::") + 2);
        unset($options['type']);
        $option = [];
        if (isset($options['option'])) {
            $option = $options['option'];
        }
        unset($options['option']);
        foreach ($datas as $data) {
            if (isset($options['default'])) {
                if (in_array($data['key'], $options['default']) !== false) {
                    $option['checked'] = 'true';
                }
            }
            $labelChildren = (isset($data['option']) ? $this->label(
                $data['value'],
                $optionLabel
            ) : (isset($data['value']) ? $this->label($data['value']) : ''));
            $out = $this->formatTemplate(
                str_replace($type, 'input', __METHOD__),
                [$type, $name, $data['key'], $this->formatAttributes($option)]
            );
            if (isset($span)) {
                $out .= $this->tag($span[$data['key']]['context'], 'span', $span[$data['key']]['options']);
            }
            if (count($divChildren) > 0) {
                $div = '';
                if (isset($divChildren2)) {
                    if (isset($divChildren2['span'])) {
                        $divChildren2span = $divChildren2['span'];
                        unset($divChildren2['span']);
                    }
                }
                if (isset($divChildren2span)) {
                    $divChildren2span['class'] = isset($option['checked']) && $option['checked'] ? $classChildren2 . $active : $classChildren2;
                    $divChildren2span['class'] = isset($option['checked']) && $option['checked'] ? $classChildren2 . $active : $classChildren2;
                    $spanOption = array_merge($divChildren2span, ['data-checkbox' => $data['key']]);
                }
                if (isset($spanOption)) {
                    $div = $this->tag($this->tag('', 'span', $spanOption), 'div', $divChildren2);
                }
                $input[] = $this->tag($out . $div . $labelChildren, 'div', $divChildren);
            } else {
                $input[] = $out . $labelChildren;
            }
            if (isset($option['checked'])) {
                unset($option['checked']);
            }
        }
        return $label . $this->tag(implode('', $input), 'div', $divpai);
    }

    /**
     * @param string $name
     * @param string $value
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function textarea(string $name, string $value, array $options = []): string
    {
        unset($options['type']);
        $optionsDiv = isset($options['div']) ? $options['div'] : [];
        if (isset($options['div'])) {
            unset($options['div']);
        }
        if (!isset($options['id']) || $options['id'] !== false) {
            $options['id'] = $name;
        }
        if (isset($options['label'])) {
            if ($options['label'] !== false) {
                if (isset($options['label']['div'])) {
                    $divLabel = $options['label']['div'];
                    unset($options['label']['div']);
                }
                if (stripos($options['label']['class'], 'min-label') !== false) {
                    $options['label']['class'] = str_replace(
                        'min-label',
                        (isset($options['disabled']) ? 'min-label min-label-textarea-disabled' : 'min-label min-label-textarea'),
                        $options['label']['class']
                    );
                }
                $label = $this->label($name, $options['label']);
                if (isset($divLabel)) {
                    $label = $this->tag($label, 'div', $divLabel);
                }
            } else {
                $label = '';
            }
            unset($options['label']);
        } else {
            $label = $this->label($name);
        }
        $row = $options['row'];
        $col = $options['col'];
        unset($options['row'], $options['col']);
        $textarea = $this->formatTemplate(__METHOD__, [$name, $row, $col, $this->formatAttributes($options), $value]);
        return $this->tag($label . $textarea, 'div', $optionsDiv);
    }

    /**
     * @param array $from
     * @param array $to
     * @param array $send
     * @param array $attachment
     *
     * @return EmailHelper
     * @throws Exception
     * @throws Exceptions
     */
    public function enviarEmail(array $from, array $to, array $send, array $attachment): EmailHelper
    {
        if (isset($from['ccs'])) {
            $ccs = $from['ccs'];
            unset($from['ccs']);
        }
        if (isset($from['bccs'])) {
            $bccs = $from['bccs'];
            unset($from['bccs']);
        }
        $this->email->addressing($to, $from['sender'], $ccs, $bccs);
        $this->email->attachment($attachment);
        $this->email->sends($send['subject'], $send['message']);
        return $this;
    }
}