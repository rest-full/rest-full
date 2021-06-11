<?php

namespace Restfull\View\Helper;

use Restfull\Error\Exceptions;
use Restfull\ORM\Entity\Entity;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 * Class FormHelper
 * @package Restfull\View\Helper
 */
class FormHelper extends Helper
{

    /**
     * @var array
     */
    protected $templater = [
            'create' => "<form action='%s' method='%s'%s>%s</form>",
            'button' => "<button type='%s'%s>%s</button>",
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
     * @var array
     */
    private $inputs = [];

    /**
     * @var array
     */
    private $dataInput = [];

    /**
     * FormHelper constructor.
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        foreach ($view->data() as $key => $value) {
            if ($key != 'token' && $value instanceof Entity) {
                $data[$key] = $value;
                $this->dataInput[$key] = [];
            }
        }
        if (isset($data)) {
            foreach ($data as $key => $value) {
                if (isset($value->repository)) {
                    $inputs = $this->formatInputs($value->repository);
                    unset($value->repository);
                    $view->setData($key, $value);
                }
                if (!isset($input)) {
                    $this->inputs[$key] = $inputs;
                } else {
                    $this->input[$key] = null;
                }
            }
        } else {
            $this->input[$key] = null;
        }
        parent::__construct($view, $this->templater);
        return $this;
    }

    /**
     * @param string $context
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function create(string $context, array $options = []): string
    {
        if (isset($options['method'])) {
            $method = $options['method'];
            unset($options['method']);
        } else {
            $method = 'post';
        }
        if (!isset($options['autocomplete'])) {
            $options['autocomplete'] = 'off';
        }
        $action = $this->view->encryptLinks(
                $options['url']['action'],
                [
                        'number' => $options['url']['number'] ?? 3,
                        'encrypt' => $options['url']['encrypt'] ?? ['internal' => false, 'general' => false]
                ],
                $options['url']['params'] ?? ''
        );
        unset($options['url']);
        if (!empty($this->request->base)) {
            $action = $this->request->base . DS . $action;
        }
        return $this->formatTemplate(
                __METHOD__,
                [
                        $action,
                        $method,
                        $this->formatAttributes($options),
                        $context
                ]
        );
    }

    /**
     * @param string $name
     * @param array $options
     * @return string
     */
    public function control(string $name, array $options = []): string
    {
        if (in_array($name, ['csrf', 'method']) === false) {
            $found = false;
            foreach ($this->inputs as $key => $values) {
                foreach ($values as $value) {
                    if ($value['name'] == $name) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    break;
                }
            }
            if (isset($value['max'])) {
                $options['maxlength'] = $value['max'];
            }
            if (!isset($options['type'])) {
                $type = in_array(
                        $value['type'],
                        ['select', 'checkbox', 'radio', 'textarea']
                ) ? $value['type'] : 'input';
                $options['type'] = $value['type'];
            } else {
                $type = in_array(
                        $options['type'],
                        ['select', 'checkbox', 'radio', 'textarea']
                ) ? $options['type'] : 'input';
                if ($options['type'] == "hidden") {
                    $options['label'] = false;
                }
            }
        } else {
            $options['label'] = false;
            $type = 'input';
            $options['type'] = 'hidden';
        }
        if (in_array($type, ['select', 'checkbox', 'radio']) === false) {
            if (!isset($options['value'])) {
                if (in_array($options['type'], ['reset', 'submit', 'button']) === false) {
                    $options['value'] = !empty($values->$name) ? $values->$name : '';
                } elseif (in_array($options['type'], ['reset', 'submit', 'button']) !== false) {
                    $options['value'] = $name;
                }
            }
        } elseif (in_array($type, ['select', 'checkbox', 'radio']) !== false) {
            if (!isset($options['default'])) {
                $options['default'] = !empty($values->$name) ? $values->$name : '';
            }
        }
        if (isset($options['readonly']) && !$options['readonly']) {
            unset($options['readonly']);
        }
        if ($type == 'textarea') {
            unset($options['type']);
        }
        foreach ($options as $key => $value) {
            if (is_array($options[$key])) {
                if (count($value) == 0) {
                    unset($options[$key]);
                }
            } elseif (is_string($options[$key])) {
                if (empty($options[$key])) {
                    unset($options[$key]);
                }
            }
        }
        $value = isset($options['value']) ? $options['value'] : (in_array(
                $type,
                ['select', 'checkbox', 'radio']
        ) ? [] : '');
        unset($options['value']);
        return $this->$type($name, $value, $options);
    }

    /**
     * @param string $name
     * @param string $value
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    private function input(string $name, string $value, array $options = []): string
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
    private function select(string $name, array $datas, array $options = []): string
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
    public function option(string $key, string $value, $default = '', array $options = []): string
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
    private function radio(string $name, array $datas, array $options = []): string
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
    private function checkbox(string $name, array $datas, array $options = []): string
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
    private function textarea(string $name, string $value, array $options = []): string
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
}
