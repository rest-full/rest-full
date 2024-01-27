<?php

declare(strict_types=1);

namespace Restfull\Datasource;

/**
 *
 */
class BaseQueryFormat extends QueryFormat
{

    /**
     * @param string $command
     *
     * @return array
     */
    public function formatSelected(string $command, string $mode): array
    {
        $type = $command === 'select' ? 'dql' : (in_array($command, ['insert', 'update', 'delete']
        ) !== false ? 'dml' : 'ddl');
        if ($command !== $this->command()) {
            $this->command($command);
        }
        $formats = $this->{$type}();
        if ($mode === 'query') {
            return $formats;
        }
        foreach ($formats as $index => $format) {
            $newFormats[$index] = [$format, $this->alignSchemaFields($format)];
        }
        return $newFormats;
    }

    /**
     * @param string $format
     *
     * @return array
     */
    private function alignSchemaFields(string $format): array
    {
        $command = $this->command();
        foreach (['create table %t (', 'alter table %t', 'drop table %t', ') %o'] as $newFormat) {
            if ($newFormat == $format) {
                $identification = in_array($newFormat, ['create table %t (', 'alter table %t', 'drop table %t']
                ) !== false ? 'nameTable' : 'options';
                break;
            }
        }
        $keys = in_array($command, ['create', 'alter', 'drop']) !== false ? [
            '%o' => 'options',
            '%tc' => 'tableColumn',
            '%c' => 'nameColumn',
            '%i' => 'index',
            '%t' => 'nameTable',
            '%ni' => 'nameIndex'
        ] : [
            '%t' => 'nameTable',
            '%f' => 'fields',
            '%v' => 'values',
            '%jy' => 'joinType',
            '%jt' => 'joinTable',
            '%jc' => 'joinConditions',
            '%c' => 'conditions',
            '%g' => 'group',
            '%h' => 'having',
            '%o' => 'order',
            '%l' => 'limit'
        ];
        foreach ($keys as $key => $value) {
            if (stripos($format, $key) !== false) {
                $format = str_replace($key, $value, $format);
                break;
            }
        }
        if (isset($identification)) {
            $format = substr($format, stripos($format, $identification));
            return [$format];
        }
        if (stripos($format, '(') !== false || stripos($format, ')') !== false) {
            if (stripos($format, '(') !== false) {
                $format = str_replace('(', '', $format);
            } else {
                $format = str_replace(')', '', $format);
            }
        }
        return explode(' ', $format);
    }
}