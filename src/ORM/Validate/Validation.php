<?php

namespace Restfull\ORM\Validate;

/**
 * Class Validation
 * @package Restfull\ORM\Validate
 */
class Validation extends validate
{

    /**
     * @return Validation
     */
    public function required(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (empty($this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field is required.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function equals(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if ($this->data[$keyRules[$a]][0] != $this->data[$keyRules[$a]][1]) {
                        $this->error[$keyRules[$a]][] = "These fields aren't the equals. the fields are: {$keyRules[$a]} and {$keyRules[$a]}.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function search(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (stripos($this->data[$keyRules[$a]], ".") !== false) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field is contains abbreviated word.";
                    } else {
                        $data = explode(" ", $this->data[$keyRules[$a]]);
                        $resp = false;
                        for ($b = 0; $b < count($data); $b++) {
                            if (in_array($data[$b], $words)) {
                                $resp = true;
                                break;
                            }
                        }
                        if ($resp) {
                            $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field is contains word that is not allowed.";
                        }
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function phone(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (preg_match("/^\(+[0-9]{2,3}\) [0-9]{4}-[0-9]{4}$/i", $this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a phone formart.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function float(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (!is_float($this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't afloat.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function string(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (!is_string($this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a string.";
                    }
                    if (count($rules) > 1) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    } else {
                        $this->rules[$keyRules[$a]] = $rules[0];
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function numeric(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (!is_numeric($this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a numeric.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function arrays(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (!is_array($this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a array.";
                    }
                    if (count($rules) > 1) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    } else {
                        $this->rules[$keyRules[$a]] = $rules[0];
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function email(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (!filter_var($this->data[$keyRules[$a]], FILTER_VALIDATE_EMAIL)) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a email.";
                    }
                    if (count($rules) > 1) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    } else {
                        $this->rules[$keyRules[$a]] = $rules[0];
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function url(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (!filter_var($this->data[$keyRules[$a]], FILTER_VALIDATE_URL)) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a url.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function file(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (!is_file($this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a file.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function alphaNumeric(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (preg_match("/^[a-zA-Z0-9_]*$/i", $this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a alpha numeric.";
                    }
                    if (count($rules) > 1) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    } else {
                        $this->rules[$keyRules[$a]] = $rules[0];
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function date(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (preg_match("/^\d{1,2}\/\d{1,2}\/\d{4}$/i", $this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a date formart.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return Validation
     */
    public function time(): Validation
    {
        $keyRules = array_keys($this->rules);
        for ($a = 0; $a < count($keyRules); $a++) {
            if (in_array($keyRules[$a], array_keys($this->data))) {
                $rules = explode(", ", $this->rules[$keyRules[$a]]);
                if ($rules[0] == substr(__METHOD__, stripos(__METHOD__, "::") + 2)) {
                    if (preg_match("/^^([0-1][0-9]|2[0-3]):([0-5][0-9])$/i", $this->data[$keyRules[$a]])) {
                        $this->error[$keyRules[$a]][] = "This {$keyRules[$a]} field isn't a time formart.";
                    }
                    unset($rules[0]);
                    if (count($rules) > 0) {
                        sort($rules);
                        $this->rules[$keyRules[$a]] = implode(", ", $rules);
                    }
                }
            }
        }
        return $this;
    }

}
