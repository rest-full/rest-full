<?php

namespace Restfull\View\Helper;

use PHPMailer\PHPMailer\Exception;
use Restfull\Error\Exceptions;
use Restfull\Mail\Email;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 * Class EmailHelper
 * @package Restfull\View\Helper
 */
class EmailHelper extends Helper
{

    /**
     * @var array
     */
    protected $templater = [
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
            'div' => "<div%s>%s</div>"
    ];

    /**
     * @var Email
     */
    private $email;

    /**
     * EmailHelper constructor.
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view, $this->templater);
        $this->email = $view->request->bootstrap('email');
        return $this;
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
     * @param string $name
     * @param string $value
     * @param array $options
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
                    $optionsSpan = (isset($span['options'])) ? $this->formatAttributes(
                            $span['options']
                    ) : '';
                    $input .= $this->formatTemplate(
                            str_replace('input', 'span', __METHOD__),
                            [
                                    $optionsSpan,
                                    $span['context']
                            ]
                    );
                }
            }
            unset($options['span']);
        }
        if (isset($options['img'])) {
            $img = (isset($options['img']['options'])) ? $this->formatAttributes($options['img']['options']) : '';
            $input .= $this->formatTemplate(
                    str_replace('input', 'image', __METHOD__),
                    [
                            DS . ".." . DS . $this->route . $options['img']['context'],
                            $img
                    ]
            );
            unset($options['img']);
        }
        if (!in_array($type, ['button', 'submit', 'reset', 'file'])) {
            if (!isset($options['id'])) {
                if (!isset($options['placeholder']) && !isset($options['label'])) {
                    $options['id'] = $name;
                } else {
                    if (isset($options['placeholder'])) {
                        $options['id'] = $options['placeholder'];
                    } else {
                        if ($options['label'] === false) {
                            $options['id'] = $options['label'];
                        }
                    }
                }
            }
            if (isset($options['label'])) {
                if ($options['label'] !== false && $type != 'hidden') {
                    $label = $this->label($name, $options['label']);
                } else {
                    $label = '';
                }
                unset($options['label']);
            } else {
                if ($type != 'hidden') {
                    $label = $this->label($name);
                } else {
                    $label = '';
                }
            }
            if (stripos($value, '"')) {
                $value = str_replace('"', '&quot;', $value);
            }
            if (stripos($value, "'")) {
                $value = str_replace("'", "&apos;", $value);
            }
            $input .= $label . $this->formatTemplate(
                            __METHOD__,
                            [
                                    $type,
                                    $name,
                                    $value,
                                    $this->formatAttributes($options)
                            ]
                    );
            if ($div) {
                return $this->formatTemplate(
                        str_replace('input', 'div', __METHOD__),
                        [
                                $this->formatAttributes($optionsDiv),
                                $input
                        ]
                );
            }
            return $input;
        }
        $input .= $this->formatTemplate(
                __METHOD__,
                [
                        $type,
                        $name,
                        $value,
                        $this->formatAttributes($options)
                ]
        );
        if ($div) {
            return $this->formatTemplate(
                    str_replace('input', 'div', __METHOD__),
                    [
                            $this->formatAttributes($optionsDiv),
                            $input
                    ]
            );
        }
        return $input;
    }

    /**
     * @param string $name
     * @param array $options
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
        return $this->formatTemplate(
                __METHOD__,
                [
                        $this->formatAttributes($options),
                        $text
                ]
        );
    }

    /**
     * @param string $name
     * @param array $datas
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function select(string $name, array $datas, array $options = []): string
    {
        $optionsDiv = isset($options['div']) ? $options['div'] : [];
        if (isset($options['div'])) {
            unset($options['div']);
        }
        if (!isset($options['id'])) {
            if (!isset($options['placeholder']) && !isset($options['label'])) {
                $options['id'] = $name;
            } else {
                if (isset($options['placeholder']) && !isset($options['label'])) {
                    $options['id'] = $options['placeholder'];
                } else {
                    if ($options['label'] === false) {
                        $options['id'] = $options['label'];
                    }
                }
            }
        }
        if (isset($options['label'])) {
            if ($options['label'] !== false) {
                if (isset($options['label']['div'])) {
                    $div = $options['label']['div'];
                    unset($options['label']['div']);
                }
                $label = $this->label($name, $options['label']);
                if (isset($div)) {
                    $label = $this->view->Html->tag($label, $div);
                }
            } else {
                $label = '';
            }
            unset($options['label']);
        } else {
            $label = $this->label($name);
        }
        unset($options['type']);
        foreach ($datas as $data) {
            $out[] = isset($options['default']) ?
                    $this->option($data['key'], $data['value'], $options['default'], $options) : $this->option(
                            $data['key'],
                            $data['value'],
                            $options
                    );
        }
        if (isset($options['default'])) {
            unset($options['default']);
        }
        $select = $label . $this->formatTemplate(
                        __METHOD__,
                        [
                                $name,
                                $this->formatAttributes($options),
                                (isset($out) ? implode("", $out) : '')
                        ]
                );
        return $this->formatTemplate(
                str_replace('select', 'div', __METHOD__),
                [
                        $this->formatAttributes($optionsDiv),
                        $select
                ]
        );
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $default
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    private function option(string $key, string $value, $default = '', array $options = []): string
    {
        $option = !isset($options['option']) ? [] : $options['option'];
        if ($key == $default) {
            $option['selected'] = 'true';
        }
        return $this->formatTemplate(
                __METHOD__,
                [
                        (substr($key, 0, stripos($key, " ")) == "selecione") ? '' : $key,
                        $this->formatAttributes($option),
                        $value
                ]
        );
    }

    /**
     * @param string $name
     * @param array $datas
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function radio(string $name, array $datas, array $options = []): string
    {
        $divpai = [];
        $divfilho = [];
        if (isset($options['div'])) {
            $divpai = $options['div'];
            if (isset($divpai['div'])) {
                $divfilho = $divpai['div'];
                unset($divpai['div']);
            }
            unset($options['div']);
        }
        if (!isset($options['id'])) {
            $options['id'] = $name;
        }
        if (isset($options['label'])) {
            if ($options['label'] !== false) {
                $label = $this->label($name, $options['label']);
            } else {
                $label = '';
            }
            unset($options['label']);
        } else {
            $label = $this->label($name);
        }
        $type = substr(__METHOD__, stripos(__METHOD__, "::") + 2);
        unset($options['type']);
        foreach ($datas as $data) {
            $option = [];
            if (isset($options['option'])) {
                $option = $options['option'];
            }
            if (isset($options['default'])) {
                if ($data['key'] == $options['default']) {
                    $option['checked'] = 'true';
                }
            }
            $out = $this->formatTemplate(
                    str_replace($type, 'input', __METHOD__),
                    [
                            $type,
                            $name,
                            $data['key'],
                            $this->formatAttributes($option)
                    ]
            );
            $labelfilho = (isset($data['option']) ? $this->label(
                    $data['value'],
                    $data['option']
            ) : $this->label($data['value']));
            if (count($divfilho) > 0) {
                $input[] = $this->formatTemplate(
                        str_replace($type, 'div', __METHOD__),
                        [
                                $this->formatAttributes($divfilho),
                                $out . $labelfilho
                        ]
                );
            } else {
                $input[] = $out . $labelfilho;
            }
        }
        if (isset($options['option'])) {
            unset($options['option']);
        }
        if (isset($options['default'])) {
            unset($options['default']);
        }
        return $label . $this->formatTemplate(
                        str_replace($type, 'div', __METHOD__),
                        [
                                $this->formatAttributes($divpai),
                                implode("", $input)
                        ]
                );
    }

    /**
     * @param string $name
     * @param array $datas
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function checkbox(string $name, array $datas, array $options = []): string
    {
        $divpai = [];
        $divfilho = [];
        if (isset($options['div'])) {
            $divpai = $options['div'];
            if (isset($divpai['div'])) {
                $divfilho = $divpai['div'];
                unset($divpai['div']);
            }
            unset($options['div']);
        }
        if (!isset($options['id'])) {
            $options['id'] = $name;
        }
        if (isset($options['label'])) {
            if ($options['label'] !== false) {
                $label = $this->label($name, $options['label']);
            } else {
                $label = '';
            }
            unset($options['label']);
        } else {
            $label = $this->label($name);
        }
        $type = substr(__METHOD__, stripos(__METHOD__, "::") + 2);
        unset($options['type']);
        foreach ($datas as $data) {
            $option = [];
            if (isset($options['option'])) {
                $option = $options['option'];
                if (isset($option['label'])) {
                    $optionLabel = $option['label'];
                }
            }
            if (isset($options['default'])) {
                if (in_array($data['key'], $options['default']) !== false) {
                    $option['checked'] = 'true';
                }
            }
            $labelfilho = (isset($data['option']) ? $this->label(
                    $data['value'],
                    $optionLabel
            ) : (isset($data['value']) ? $this->label($data['value']) : ''));
            $out = $this->formatTemplate(
                    str_replace($type, 'input', __METHOD__),
                    [
                            $type,
                            $name,
                            $data['key'],
                            $this->formatAttributes($option)
                    ]
            );
            if (count($divfilho) > 0) {
                $input[] = $this->formatTemplate(
                        str_replace($type, 'div', __METHOD__),
                        [
                                $this->formatAttributes($divfilho),
                                $out . $labelfilho
                        ]
                );
            } else {
                $input[] = $out . $labelfilho;
            }
        }
        if (isset($options['option'])) {
            unset($options['option']);
        }
        if (isset($options['default'])) {
            unset($options['default']);
        }
        return $label . $this->formatTemplate(
                        str_replace($type, 'div', __METHOD__),
                        [
                                $this->formatAttributes($divpai),
                                implode("", $input)
                        ]
                );
    }

    /**
     * @param string $name
     * @param string $value
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function textarea(string $name, string $value, array $options = []): string
    {
        $optionsDiv = isset($options['div']) ? $options['div'] : [];
        if (isset($options['div'])) {
            unset($options['div']);
        }
        if (!isset($options['id'])) {
            $options['id'] = $name;
        }
        if (isset($options['label'])) {
            if ($options['label'] !== false) {
                $label = $this->label($name, $options['label']);
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
        $textarea = $this->formatTemplate(
                __METHOD__,
                [
                        $name,
                        $row,
                        $col,
                        $this->formatAttributes($options),
                        $value
                ]
        );
        return $this->formatTemplate(
                str_replace('textarea', 'div', __METHOD__),
                [
                        $this->formatAttributes($optionsDiv),
                        $label . $textarea
                ]
        );
    }

    /**
     * @param array $from
     * @param array $to
     * @param array $send
     * @param array $attachment
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