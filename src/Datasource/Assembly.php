<?php

namespace Restfull\Datasource;

use Restfull\Error\Exceptions;

/**
 *
 */
abstract class Assembly
{

    /**
     * @var array
     */
    public $bindValue = [];

    /**
     * @var int
     */
    protected $count = 0;

    private $options = [];

    public function __construct(string $command)
    {
        if ($command === 'schema') {
            $this->options = [
                'table' => [
                    'engine',
                    'auto_increment',
                    'avg_row_length',
                    'charset',
                    'collate',
                    'comment',
                    'data directory',
                    'index directory',
                    'insert_method',
                    'max_rows',
                    'min_rows',
                    'password',
                    'row_formart',
                    'key_block_size',
                    'union'
                ],
                'column' => ['zerofill', 'unsigned', 'null', 'auto_increment', 'generated', 'unique', 'binary'],
                'index' => ['order'],
                'constraint' => ['update', 'delete']
            ];
        }
        return $this;
    }

    /**
     * @param array $datas
     * @return string
     * @throws Exceptions
     */
    protected function concatAnd(array $datas): string
    {
        $or = [];
        if (isset($datas['or'])) {
            if (!in_array('array', array_map('gettype', $datas['or']))) {
                throw new Exceptions('It has to be multidimensional or past in and.', 404);
            }
            foreach ($datas['or'] as $dataValues) {
                $or[$datasValues['position']] = $this->concatOr($dataValues['values']);
            }
            unset($datas['or']);
        }
        $and = [];
        foreach ($datas as $key => $values) {
            $and[] = $this->comaparation([$key => $values]);
            if (in_array($key, array_keys($or)) !== false) {
                $and[] = '(' . $or[$key] . ')';
            }
        }
        return implode(' and ', $and);
    }

    /**
     * @param array $datas
     * @param bool $concat
     * @return array|string
     */
    protected function concatOr(array $datas)
    {
        $newOr = [];
        foreach ($datas as $datasKey => $datasValues) {
            $or = [];
            foreach ($datasValues as $key => $values) {
                $or[] = $this->comaparation([$key => $value]);
            }
            if (count($or) > 0) {
                $newOr[] = implode(' ' . $datasKey . ' ', $or);
            }
        }
        return $newOr;
    }

    /**
     * @param array $data
     * @return string
     */
    private function comaparation(array $data):string
    {
        $comparation = [
            ' between ' => ' <==> ',
            ' not between ' => ' !<==> ',
            ' like ' => ' % ',
            ' not like ' => ' !% ',
            ' = ' => ' & ',
            ' <> ' => ' +- ',
            ' >= ' => ' +& ',
            ' > ' => ' + ',
            ' <= ' => ' -& ',
            ' < ' => ' - ',
            ' in ' => ' () ',
            ' not in ' => ' !() ',
            ' is ' => ' )( ',
            ' is not ' => ' !)( '
        ];
        $keyData = array_keys($data)[0];
        foreach ($comparation as $key => $value) {
            if (stripos($keyData, $value) !== false) {
                $oldComparation = $value;
                $newComparation = $key;
                break;
            }
        }
        $text = ':c';
        if (in_array($newComparation, [' in ', ' between ']) !== false) {
            $values = explode(' and ', $data[$keyData]);
            $number = count($values);
            $count = $this->count + 2;
            $before = $after = '';
            $concat = ' and ';
            if ($newComparation === ' in ') {
                $values = explode('", "', substr($data[$keyData], 2, -2));
                $count = $number = $this->count + count($values);
                $after = '")';
                $before = '("';
                $concat = '", "';
            }
            for ($amount = $this->count; $amount < $number; $amount++) {
                $this->bindValue[$text . $amount] = $values[($amount - $this->count)];
                $cind_values[] = $text . $amount;
            }
            $data[str_replace($oldComparation, $newComparation, $keyData)] = $before . implode(
                    $concat,
                    $cind_values
                ) . $after;
            $this->count = $count;
        } else {
            $this->bindValue[$text . $this->count] = $data[$keyData];
            $data[str_replace($oldComparation, $newComparation, $keyData)] = $text . $this->count;
            $this->count++;
        }
        unset($data[$keyData]);
        $keyData = array_keys($data)[0];
        return $keyData.$data[$keyData];
    }

    /**
     * @param string $metadatas
     * @param array $formats
     * @return array
     */
    protected function informationSchemaTable(string $metadatas, array $formats, array $schemas): array
    {
        if (count($schemas) > 1) {
            foreach (explode(', ', $metadatas) as $metadata) {
                $type = stripos($metadata, ' foreign ') !== false ? 'constraint' : (stripos(
                    $metadata,
                    ' key '
                ) !== false ? 'index' : 'column');
                $datasSchema = $schemas['column'];
                if (in_array($type, ['constraint', 'index'])) {
                    $datasSchema = $schemas['index'];
                    if ($type == 'constraint') {
                        $datasSchema = $schemas['constraint'];
                        if (substr($metadata, 0, stripos($metadata, ' ')) == 'constraint') {
                            $datasSchema = array_merge(['nameConstraint'], $datasSchema);
                        }
                    }
                }
                $datas[] = $this->identificationMetadata($metadata, $formats, $datasSchema, $type);
            }
            return $datas;
        }
        return $datas;
    }

    private function identificationMetadata(string $data, array $formats, array $schema, string $type): array
    {
        $format = $formats[$type];
        foreach ($schema as $key => $value) {
            if ($type === 'column') {
                if ($key < substr_count($format, ' ')) {
                    list($identification, $data) = [
                        substr($data, 0, stripos($format, ' ')),
                        substr($data, stripos($format, ' '))
                    ];
                    $format = substr($format, stripos($format, ' '));
                } else {
                    $identification = $data;
                }
            } else {
                list($identification, $data) = [
                    substr($data, 0, stripos($format, ' ')),
                    substr($data, stripos($format, ' '))
                ];
                if (stripos($identification, '(') !== false) {
                    $identification = substr($identification, stripos($identification, '('));
                }
                if (stripos($identification, ')') !== false) {
                    $identification = substr($identification, 0, stripos($identification, ')'));
                }
                if ($type === 'index') {
                    $identifications = stripos($identification, ', ') !== false ? explode(
                        ', ',
                        $identification
                    ) : [$identification];
                    $options = $this->adjustOptions($identifications, $type);
                }
                $format = substr($format, stripos($format, ' '));
            }
            if ($format === '%o') {
                $datas[$value] = $options ?? $this->adjustOptions($data, $type);
            }
            $datas[$value] = $identifications ?? $identification;
        }
        return $datas;
    }

    private function adjustOptions(string $options, string $type): array
    {
        $datas = [];
        foreach ($this->options[$type] as $option) {
            if (stripos($options, $option) !== false) {
//                $datas[$options]=
            }
        }
        return $datas;
    }

}