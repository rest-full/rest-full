<?php

declare(strict_types=1);

namespace Restfull\View\Helper;

use Restfull\Error\Exceptions;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 *
 */
class FormHelper extends Helper
{

    /**
     * @var array
     */
    private $inputs = [];

    /**
     * @var array
     */
    private $datasInputs = [];

    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $selectValues = [];

    /**
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view, [
            'create' => "<form action='%s' method='%s'%s>%s</form>",
            'button' => "<button type='%s'%s>%s</button>",
            'input' => "<input type='%s' name='%s' value='%s'%s/>",
            'label' => "<label%s>%s</label>",
            'option' => "<option value='%s'%s>%s</option>",
            'group' => "<optgroup %s>%s</optgroup>",
            'select' => "<select name='%s'%s>%s</select>",
            'textarea' => "<textarea name='%s' rows='%s' cols='%s'%s>%s</textarea>",
            'others' => "<%s>%s</%s>",
            'image' => "<img src='%s'%s/>"
        ]);
        if (count($this->view->datas()) > 0) {
            $values = $this->view->datas();
            if (isset($values['table'])) {
                $this->table = $values['table'];
            }
            $values = $this->findRepositoryInViewData($values);
            if (count($this->repositories) > 0) {
                $inputs = $this->formatInputs($values);
                $this->inputs = $inputs['newColumns'];
                $this->selectValues = $inputs['selects'];
                $this->datasInputs = $inputs['values'];
            }
        }
        return $this;
    }

    /**
     * @param string $context
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function create(string $context, array $options = []): string
    {
        if (isset($options['method'])) {
            $divInitial = '';
            if (stripos($context, 'id="inputs"') !== false) {
                $divInitial = substr($context, 0, stripos($context, '>') + 1);
                $context = substr($context, strlen($divInitial));
            }
            $input = empty($options['method']) ? $this->formatTemplate(
                str_replace('create', 'input', __METHOD__),
                ['hidden', '_METHOD', $options['method'], ['id' => 'chosenMethod']]
            ) : $this->formatTemplate(
                str_replace('create', 'input', __METHOD__),
                ['hidden', '_METHOD', $options['method']]
            );
            $context = $divInitial . $this->tag($input, 'div', ['class' => 'd-none']) . $context;
            unset($options['method']);
        }
        foreach (['method' => 'post', 'autocomplete' => 'off', 'enctype' => 'multipart/form-data'] as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
            if ($key === 'method') {
                $method = empty($options[$key]) ? $value : $options[$key];
                unset($options[$key]);
            }
        }
        if (!isset($options['url'])) {
            throw new Exceptions('It has to have the value and the url key.');
        }
        $baseUrl = URL . DS;
        if (!empty($this->request->base)) {
            $baseUrl .= $this->request->base . DS;
        }
        $action = $baseUrl . $this->view->encryptLinks(
                $options['url'],
                $options['prefix'] ?? 'app',
                isset($options['urlParams']) ? explode('|', $options['urlParams']) : ['']
            );
        unset($options['url'], $options['prefix'], $options['urlParams']);
        return $this->formatTemplate(__METHOD__, [$action, $method, $this->formatAttributes($options), $context]);
    }

    /**
     * @param string $content
     * @param string $name
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    private function tag(string $content, string $name, array $options = []): string
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
     * @param array $options
     *
     * @return string
     */
    public function control(string $name, array $options = []): string
    {
        if (in_array($name, ['csrf', 'method', 'id']) === false) {
            if (!isset($options['type'])) {
                $input = $this->inputs[$this->table];
                $type = in_array($input[$name]['type'], ['select', 'checkbox', 'radio', 'textarea']
                ) ? $input[$name]['type'] : 'input';
                $options['type'] = $input[$name]['type'];
            } else {
                $type = in_array($options['type'], ['select', 'checkbox', 'radio', 'textarea']
                ) ? $options['type'] : 'input';
                if ($options['type'] === "hidden") {
                    $options['label'] = false;
                }
            }
            if (in_array($type, ['select', 'checkbox', 'radio']) === false) {
                if (isset($this->table)) {
                    if (isset($this->inputs[$this->table][$name]['max'])) {
                        $options['maxlength'] = $this->inputs[$this->table][$name]['max'];
                    }
                }
            }
        } else {
            $type = 'input';
            $options['type'] = 'hidden';
            if ($name === 'id') {
                $options['div']['class'] = 'inputs disabled';
                foreach ($this->inputs as $key => $value) {
                    if (in_array($name, array_keys($value)) !== false) {
                        $this->table = $key;
                    }
                }
            } else {
                $options['label'] = false;
            }
        }
        if (isset($options['value'])) {
            $values = $options['value'];
            unset($options['value']);
        } elseif (in_array($type, ['input', 'textarea']) !== false) {
            if (in_array($options['type'], ['button', 'submit', 'reset', 'file']) !== false) {
                $values = $name;
            } else {
                $values = $this->datasInputs[$this->table]->{$name};
            }
        } elseif (in_array($type, ['select', 'checkbox', 'radio']) !== false) {
            if ($type === 'checkbox') {
                $values = $this->inputs[$this->table][$name]['max'];
                if (!is_null($values)) {
                    $values = explode("','", substr($values, 1, -1));
                } else {
                    $values = $this->selectValues[$this->table][$name];
                }
            } else {
                $values = $this->selectValues[$this->table][$name];
            }
        }
        if (in_array($type, ['select', 'checkbox', 'radio']) !== false) {
            if (in_array($type, ['radio', 'checkbox']) !== false) {
                if (isset($options['label']['modifyCss'])) {
                    $options['label']['class'] = $options['label']['modifyCss'];
                    unset($options['label']['modifyCss']);
                }
                if (!isset($options['default']) && isset($this->datasInputs[$this->table]->{$name})) {
                    $options['default'] = explode(',', $this->datasInputs[$this->table]->{$name});
                }
            } else {
                if (!isset($options['default']) && isset($this->datasInputs[$this->table]->{$name})) {
                    $options['default'] = $this->datasInputs[$this->table]->{$name};
                }
            }
            if ($type === 'select') {
                if (is_array($values[0])) {
                    if (array_key_exists('key', $values[0]) === false) {
                        $value[] = ['key' => '', 'value' => 'Selecionar'];
                    }
                } else {
                    $value[] = ['key' => '', 'value' => 'Selecionar'];
                }
            }
            $count = count($values);
            for ($a = 0; $a < $count; $a++) {
                if (is_array($values[$a])) {
                    $value[] = array_key_exists('key', $values[$a]) !== false ? $values[$a] : [
                        'key' => $values[$a],
                        'value' => $this->translator(
                            $values[$a],
                            $options['convert'] ?? '',
                            $options['translate'] ?? true
                        )
                    ];
                } else {
                    $value[] = [
                        'key' => $values[$a],
                        'value' => $this->translator(
                            $values[$a],
                            $options['convert'] ?? '',
                            $options['translate'] ?? true
                        )
                    ];
                }
            }
            foreach (['convert', 'translate'] as $newKey) {
                if (isset($options[$newKey])) {
                    unset($options[$nweKey]);
                }
            }
        } else {
            $value = $values;
        }
        unset($values);
        if (in_array($options['type'], ['button', 'submit', 'reset', 'file']) === false) {
            if (isset($options['label'])) {
                if (is_array($options['label']) && !isset($options['label']['notEmpty'])) {
                    $options['label']['notEmpty'] = 'min-label';
                }
            }
            if ($type !== 'radio') {
                $valid = !empty($value);
                if ($value === '0') {
                    $valid = true;
                }
                if ($valid && isset($options['label']['notEmpty']) && isset($options['label']['class'])) {
                    $options['label']['class'] .= ' ' . $options['label']['notEmpty'];
                }
            }
            if (isset($options['label']['notEmpty'])) {
                unset($options['label']['notEmpty']);
            }
        }
        foreach ($options as $key => $data) {
            if (is_array($options[$key])) {
                if (count($data) === 0) {
                    unset($options[$key]);
                }
            } elseif (is_string($options[$key])) {
                if (empty($options[$key])) {
                    unset($options[$key]);
                }
            }
        }
        return $this->{$type}($name, $value, $options);
    }

    /**
     * @param string $name
     * @param string $value
     * @param array $options
     *
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
        if (isset($options['label'])) {
            unset($options['label']);
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
        return $this->tag(
            $this->formatTemplate(__METHOD__, [$this->formatAttributes($options), $text]),
            'div',
            $div
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
    private function select(string $name, array $datas, array $options = []): string
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
        if (isset($options['default'])) {
            $default = (string)$options['default'];
            unset($options['default']);
        }
        foreach ($datas as $data) {
            $value = $data['value'];
            $key = $data['key'];
            unset($data['value'], $data['key']);
            if (isset($data['group'])) {
                $group = $data['group'];
                unset($data['group']);
            }
            if (isset($disabled)) {
                if ($disabled[$value] === 1) {
                    $option['class'] = 'disabled';
                } else {
                    if (isset($option['class'])) {
                        unset($option['class']);
                    }
                }
            }
            if (!isset($options['notCreateValues']) || in_array($key, $options['notCreateValues']) === false) {
                $option = array_merge($option ?? [], $data['option'] ?? []);
                if (!isset($group)) {
                    $out[] = $this->option($key, $value, $default ?? '', $option ?? []);
                } else {
                    if (isset($newGroup['id']) && $group['id'] != $newGroup['id']) {
                        $out[] = $this->group($newOut, $newGroup);
                        $newOut = [];
                    }
                    $newOut[] = $this->option($key, $value, $default ?? '', $option ?? []);
                    $newGroup = $group;
                }
            }
        }
        if (isset($newOut)) {
            $out[] = $this->group($newOut, $newGroup);
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
     * @param array $option
     *
     * @return string
     * @throws Exceptions
     */
    public function option(string $key, string $value, string $default = '', array $options = []): string
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
        return $this->formatTemplate(__METHOD__, [$key, $this->formatAttributes($options), $value]);
    }

    /**
     * @param array $datas
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public
    function group(
        array $datas,
        array $options
    ): string {
        $options['label'] = $options['id'];
        return $this->formatTemplate(__METHOD__, [$this->formatAttributes($options), implode('', $datas)]);
    }

    /**
     * @param string $name
     * @param array $datas
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    private function radio(string $name, array $datas, array $options = []): string
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
            $value = $data['value'];
            $key = $data['key'];
            unset($data['value'], $data['key']);
            $option = [];
            if (isset($options['option'])) {
                $option = $options['option'];
            }
            if (isset($options['default'])) {
                if ($key === $options['default']) {
                    $option['checked'] = 'true';
                }
            }
            if (!isset($options['notCreateValues']) || in_array($key, $options['notCreateValues']) === false) {
                $out = $this->formatTemplate(
                    str_replace($type, 'input', __METHOD__),
                    [$type, $name, $key, $this->formatAttributes($option)]
                );
                if (isset($span)) {
                    $out .= $this->tag($span[$key['context']], 'span', $span[$data['key']]['options']);
                }
                $labelChildren = $this->label($value, $data['option'] ?? []);
                if (count($divChildren) > 0) {
                    $div = '';
                    if (isset($divChildren2['span'])) {
                        $spanOption = array_merge($divChildren2['span'], ['data-radio' => $key]);
                        $classSpanOption = $divChildren2['span']['class'];
                        unset($divChildren2['span']);
                    } else {
                        $spanOption['data-radio'] = $key;
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
    private function checkbox(string $name, array $datas, array $options = []): string
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
            $value = $data['value'];
            $key = $data['key'];
            unset($data['value'], $data['key']);
            if (isset($options['default'])) {
                if (in_array($key, $options['default']) !== false) {
                    $option['checked'] = 'true';
                }
            }
            if (!isset($options['notCreateValues']) || in_array($key, $options['notCreateValues']) === false) {
                $labelChildren = $this->label($value ?? '', $data['option'] ?? []);
                $out = $this->formatTemplate(
                    str_replace($type, 'input', __METHOD__),
                    [$type, $name, $key, $this->formatAttributes($option)]
                );
                if (isset($span)) {
                    $out .= $this->tag($span[$key]['context'], 'span', $span[$key]['options']);
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
                        $spanOption = array_merge($divChildren2span, ['data-checkbox' => $key]);
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
    private function textarea(string $name, string $value, array $options = []): string
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
        $textarea = $this->formatTemplate(__METHOD__, [$name, $row, $col, $this->formatAttributes($options), $value]
        );
        return $this->tag($label . $textarea, 'div', $optionsDiv);
    }
}
