<?php

namespace Restfull\ORM\Entity;

use Restfull\ORM\TableRegistry;

/**
 * Class Entity
 * @package Restfull\ORM\Entity
 */
class Entity
{

    /**
     * @var TableRegistry
     */
    public $repository;

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var array
     */
    public $option = [];

    /**
     * @param string $type
     * @param array $options
     * @return Entity
     */
    public function config(string $type, array $options = []): Entity
    {
        $this->type = $type;
        $this->option = $options;
        return $this;
    }

    /**
     * @return Entity
     */
    public function entity(): Entity
    {
        if ($this->type == "query") {
            foreach ($this->option['result'] as $key => $value) {
                $this->$key = $value;
            }
            unset($this->option, $this->type);
            return $this;
        } elseif ($this->type != 'open') {
            if (isset($this->option['fields'])) {
                if ($this->option['fields'][0] == 'count') {
                    $this->count = $this->option['result']['count'];
                    if (count($this->option['result']) == 1) {
                        return $this;
                    }
                    unset($this->option['result']['count'], $this->option['fields'][0]);
                }
                $columns = $this->selectColumns();
            }
        }
        if (!isset($columns)) {
            $columns = array_merge($this->columnsFields(true), ['entity' => null]);
        }
        $this->createEntity($columns['columns'], $columns['alias'], $columns['entity']);
        unset($this->option, $this->type);
        return $this;
    }

    /**
     * @return array
     */
    private function selectColumns(): array
    {
        $fields = $this->columnsFields(true);
        $entity = $columns = ['main' => [], 'foreing' => []];
        $keys = array_keys($this->repository->columns);
        for ($a = 0; $a < count($keys); $a++) {
            $column = $this->repository->columns[$keys[$a]];
            for ($b = 0; $b < count($column); $b++) {
                if (count($fields['columns']) > 0) {
                    if (in_array($column[$b]['name'], $fields['columns']) !== false) {
                        $columns['main'][] = $column[$b]['name'];
                    }
                } else {
                    $columns['main'][] = $column[$b]['name'];
                }
                if (count($this->repository->entity) > 0) {
                    if (in_array($column[$b]['type'], $this->repository->entity[$keys[$a]]) !== false) {
                        $entity['main'][count(
                                $columns['main']
                        ) - 1] = $this->repository->entity[$keys[$a]][$column[$b]['name']];
                    }
                }
            }
        }
        if (isset($this->repository->join)) {
            $keys = array_keys($this->repository->join);
            for ($a = 0; $a < count($keys); $a++) {
                $column = $this->repository->join[$keys[$a]];
                for ($b = 0; $b < count($column); $b++) {
                    if (isset($fields['columns'])) {
                        if (!in_array($column[$b]['name'], $columns['main']) !== false && in_array(
                                        $column[$b]['name'],
                                        $fields['columns']
                                ) !== false) {
                            $columns['foreing'][] = $column[$b]['name'];
                        }
                    } else {
                        if (!in_array($column[$b]['name'], $columns) !== false) {
                            $columns['foreing'][] = $column[$b]['name'];
                        }
                    }
                    if (count($this->repository->entity) > 0) {
                        if (in_array($column[$b]['type'], $this->repository->entity[$keys[$a]]) !== false) {
                            $entity['foreing'][count(
                                    $columns['main']
                            ) - 1] = $this->repository->entity[$keys[$a]][$column[$b]['name']];
                        }
                    }
                }
            }
        }
        $alias = $fields['alias'];
        $keys = ['columns', 'entity', 'alias'];
        for ($a = 0; $a < count($keys); $a++) {
            if ($keys[$a] != 'alias') {
                if (isset(${$keys[$a]})) {
                    $datas[$keys[$a]] = ${$keys[$a]}['main'];
                    if (count(${$keys[$a]}['foreing']) > 0) {
                        foreach (${$keys[$a]}['foreing'] as $key => $value) {
                            if (count($fields['columns']) > count($datas[$keys[$a]])) {
                                $datas[$keys[$a]][] = $value;
                            }
                        }
                        unset(${$keys[$a]}['foreing']);
                    }
                }
            } else {
                if (isset(${$keys[$a]})) {
                    $datas[$keys[$a]] = ${$keys[$a]};
                }
            }
        }
        return $datas;
    }

    /**
     * @param bool $alias
     * @return array[]
     */
    private function columnsFields(bool $alias = false): array
    {
        $data = ['columns' => [], 'alias' => []];
        if (isset($this->option['fields'])) {
            foreach (array_keys($this->option['fields']) as $keys) {
                $data['columns'] = count(
                        $data['columns']
                ) == 0 ? [$this->option['fields'][$keys]['column']] : array_merge(
                        $data['columns'],
                        [$this->option['fields'][$keys]['column']]
                );
                if ($alias) {
                    $data['alias'] = count(
                            $data['alias']
                    ) == 0 ? $this->option['fields'][$keys]['alias'] : array_merge(
                            $data['alias'],
                            $this->option['fields'][$keys]['alias']
                    );
                }
            }
        }
        return $data;
    }

    /**
     * @param array $columns
     * @param array|null $entity
     * @return Entity
     */
    private function createEntity(array $columns, array $alias, array $entity = null): Entity
    {
        $datas = '';
        if ($this->type == 'open') {
            foreach ($columns as $key => $value) {
                $this->{$alias[$key]} = $this->option['result'][0][0];
            }
            return $this;
        }
        for ($a = 0; $a < count($this->option['result']); $a++) {
            foreach ($columns as $key => $value) {
                if (in_array($alias[$key], array_keys($this->option['result'][$a]))) {
                    if (isset($entity[array_search($value, $columns)])) {
                        $this->option['result'][$a][$value] = $this->{$entity[array_search($value, $columns)]}(
                                $this->option['result'][$a][$value]
                        );
                    }
                    $this->{$alias[$key]} = $this->option['result'][$a][$alias[$key]];
                }
            }
        }
        return $this;
    }

    /**
     * @param string $msg
     * @return string
     */
    public function utf8Fix(string $msg): string
    {
        $accents = [
                "á",
                "à",
                "â",
                "ã",
                "ä",
                "é",
                "è",
                "ê",
                "ë",
                "í",
                "ì",
                "î",
                "ï",
                "ó",
                "ò",
                "ô",
                "õ",
                "ö",
                "ú",
                "ù",
                "û",
                "ü",
                "ç",
                "Á",
                "À",
                "Â",
                "Ã",
                "Ä",
                "É",
                "È",
                "Ê",
                "Ë",
                "Í",
                "Ì",
                "Î",
                "Ï",
                "Ó",
                "Ò",
                "Ô",
                "Õ",
                "Ö",
                "Ú",
                "Ù",
                "Û",
                "Ü",
                "Ç",
                "-",
                "ª",
                "º"
        ];
        $utf8 = [
                "Ã¡",
                "Ã ",
                "Ã¢",
                "Ã£",
                "Ã¤",
                "Ã©",
                "Ã¨",
                "Ãª",
                "Ã«",
                "Ã­",
                "Ã¬",
                "Ã®",
                "Ã¯",
                "Ã³",
                "Ã²",
                "Ã´",
                "Ãµ",
                "Ã¶",
                "Ãº",
                "Ã¹",
                "Ã»",
                "Ã¼",
                "Ã§",
                "Ã",
                "Ã€",
                "Ã‚",
                "Ãƒ",
                "Ã„",
                "Ã‰",
                "Ãˆ",
                "ÃŠ",
                "Ã‹",
                "Ã",
                "ÃŒ",
                "ÃŽ",
                "Ã",
                "Ã“",
                "Ã’",
                "Ã”",
                "Ã•",
                "Ã–",
                "Ãš",
                "Ã™",
                "Ã›",
                "Ãœ",
                "Ã‡",
                "â€“",
                "Âª",
                "Âº"
        ];
        for ($a = 0; $a < count($utf8); $a++) {
            if (stripos($msg, $utf8[$a])) {
                $msg = str_replace($utf8[$a], $accents[$a], $msg);
            }
        }
        return $msg;
    }

}
