<?php

declare(strict_types=1);

namespace Restfull\Search;

use Restfull\Correios\Client;
use Restfull\Error\Exceptions;

/**
 *
 */
class Correios
{

    /**
     * @var Search
     */
    private $search;

    /**
     * @var array
     */
    private $data = [];

    /**
     *
     */
    public function __construct()
    {
        $this->search = new Client();
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string $cep
     * @param bool $ajax
     *
     * @return Correios
     * @throws Exceptions
     */
    public function cep(string $cep, bool $ajax = false, bool $disabledInput = false): Correios
    {
        $validated = $this->validate($cep);
        if ($validated['valid']) {
            if ($ajax) {
                $this->data = [
                    'result' => $validated['resp'],
                    'address' => '',
                    'district' => '',
                    'city' => '',
                    'state' => ''
                ];
                if ($disabledInput) {
                    foreach (['address', 'district', 'city', 'state'] as $data) {
                        $this->data[$data] = [
                            'value' => $this->data[$data],
                            'disabled' => !empty($this->data['result'])
                        ];
                    }
                }
                return $this;
            }
            throw new Exceptions('This zip code not valid.', 404);
        }
        $answer = $this->search->zipcode(str_replace("-", "", $cep));
        if (isset($answer['error']) && $answer['error'] === 'CEP INVÁLIDO') {
            if ($ajax) {
                $this->data = [
                    'result' => 'Dados não encontrado',
                    'address' => '',
                    'district' => '',
                    'city' => '',
                    'state' => ''
                ];
                if ($disabledInput) {
                    foreach (['address', 'district', 'city', 'state'] as $data) {
                        $this->data[$data] = [
                            'value' => $this->data[$data],
                            'disabled' => !empty($this->data['result'])
                        ];
                    }
                }
                return $this;
            }
            throw new Exceptions('Zip code not found.', 404);
        }
        $newStreet = '';
        for ($a = 0; $a < strlen($answer['street']); $a++) {
            if (!is_numeric($answer['street'][$a])) {
                $newStreet .= $answer['street'][$a];
            }
        }
        $answer['street'] = trim($newStreet);
        unset($newStreet);
        $this->data = [
            'result' => '',
            'address' => $answer['street'],
            'district' => $answer['district'],
            'city' => $answer['city'],
            'state' => $answer['uf']
        ];
        if ($disabledInput) {
            foreach (['address', 'district', 'city', 'state'] as $data) {
                $this->data[$data] = ['value' => $this->data[$data], 'disabled' => !empty($this->data['result'])];
            }
        }
        return $this;
    }

    /**
     * @param string $cep
     *
     * @return array
     */
    private function validate(string $cep): array
    {
        if (strlen(str_replace("-", "", $cep)) === 0) {
            return ['valid' => true, 'resp' => 'Campo CEP não pode ser vazio.'];
        }
        if (strlen(str_replace("-", "", $cep)) < 8) {
            return ['valid' => true, 'resp' => 'Campo CEP menor que 8 caracteres.'];
        }
        if (!preg_match('/^[0-9]{5}\-[0-9]{3}$/i', $cep)) {
            return ['valid' => true, 'resp' => 'Campo CEP inválido.'];
        }
        return ['valid' => false, 'resp' => ''];
    }

    /**
     * @param string $data
     * @param array $searchs
     *
     * @return string
     */
    public function searchData(string $data, array $searchs): string
    {
        if (is_array($searchs[0])) {
            foreach ([0, 1] as $a) {
                if (stripos($data, $searchs[0][$a]) !== false) {
                    $searchs[0] = $searchs[0][$a];
                    $searchs[1] = $searchs[1][$a];
                    $searchs[2] = $searchs[2][$a];
                    break;
                }
            }
        }
        if (stripos($data, $searchs[0]) !== false) {
            $data = substr($data, stripos($data, $searchs[0]) + strlen($searchs[1]));
        }
        if (isset($searchs[2])) {
            if (stripos($data, $searchs[2]) !== false) {
                $data = substr($data, 0, stripos($data, $searchs[2]));
            }
        }
        if ($searchs[3]) {
            if (strpos($data, " - até ") !== false || strpos($data, " - de ") !== false) {
                $data = substr($data, 0, strlen($data) - (strlen(substr($data, strpos($data, "- "))) + 1));
            }
            $found = false;
            for ($a = 0; $a < strlen($data); $a++) {
                if (is_numeric($data[$a])) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $data = substr($data, 0, $a - 1);
            }
        }
        return $data;
    }

}
