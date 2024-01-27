<?php

declare(strict_types=1);

namespace Restfull\ORM\Validation;

use Restfull\Container\Instances;
use Restfull\Event\Event;

/**
 *
 */
class BaseValidation extends Validation
{

    /**
     * @var array
     */
    protected $dataType = [];

    /**
     *
     */
    public function __construct(Instances $instance, array $rules)
    {
        $this->instance = $instance;
        parent::__construct($rules);
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function required(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (!isset($datas)) {
            $this->error[$keyRule][] = "This {$field} field is required.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function equals(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule, false);
        if ($datas[0] != $datas[1]) {
            $this->error[$keyRule][] = "These {$field} fields aren't the equals.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     * @param array $words
     *
     * @return Validation
     */
    public function search(string $keyRule, array $words = []): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $data = explode(" ", $this->convertArrayToString($keyRule));
        $resp = false;
        $count = count($data);
        for ($b = 0; $b < $count; $b++) {
            if (in_array($data[$b], $words)) {
                $resp = true;
                break;
            }
        }
        if ($resp) {
            $this->error[$keyRule][] = "This {$field} field is contains word that is not allowed.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function float(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (!is_float($datas)) {
            $this->error[$keyRule][] = "This {$field} field isn't a float.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function string(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (!is_string($datas)) {
            $this->error[$keyRule][] = "This {$field} field isn't a string.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function numeric(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if ($datas !== 'null') {
            if (!is_numeric($datas)) {
                $this->error[$keyRule][] = "This {$field} field isn't a numeric.";
            }
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function date(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (preg_match("/^\d{1,2}\/\d{1,2}\/\d{4}$/i", $datas) === false) {
            $this->error[$keyRule][] = "This {$field} field isn't a date formart.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function time(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (preg_match("/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/i", $datas) === false) {
            $this->error[$keyRules[$a]][] = "This {$field} field isn't a time formart.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function array(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (!is_array($datas)) {
            $this->error[$keyRule][] = "This {$field} field isn't a array.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function email(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (!filter_var($datas, FILTER_VALIDATE_EMAIL)) {
            $this->error[$keyRule][] = "This {$field} field isn't a email.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function url(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (!filter_var($datas, FILTER_VALIDATE_URL)) {
            $this->error[$keyRule][] = "This {$field} field isn't a url.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function file(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (!is_file($datas)) {
            $this->error[$keyRule][] = "This {$field} field isn't a file.";
        }
        return $this;
    }

    /**
     * @param string $keyRule
     *
     * @return Validation
     */
    public function alphaNumeric(string $keyRule): Validation
    {
        $field = $keyRule;
        if (strlen($field) > 2) {
            if (substr($field, 0, 2) === 'id') {
                $field = substr($field, 2);
            }
        }
        $datas = $this->convertArrayToString($keyRule);
        if (preg_match("/^[a-zA-Z0-9_]*$/i", $datas) === false) {
            $this->error[$keyRule][] = "This {$field} field isn't a alphanumeric.";
        }
        return $this;
    }

    /**
     * @param Event $event
     * @param array $datas
     *
     * @return null
     */
    public function beforeValidator(Event $event, array $datas)
    {
        return null;
    }

    /**
     * @param Event $event
     *
     * @return null
     */
    public function afterValidator(Event $event)
    {
        return null;
    }

    /**
     * @return BaseValidation
     */
    public function dataType(): BaseValidation
    {
        foreach (array_keys($this->datas) as $key) {
            $this->dataType[$key] = gettype($this->datas[$key]);
        }
        return $this;
    }

}
