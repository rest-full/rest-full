<?php

namespace Restfull\Datasourse;

use Restfull\Database\Query;
use Restfull\Database\Table;

/**
 * Interface QueryDatabaseInterface
 * @package Restfull\Datasourse
 */
interface QueryInterface
{

    /**
     * @param Table $data
     * @return bool
     */
    public function save(Table $data): bool;

    /**
     * @param Table $data
     * @return bool
     */
    public function update(Table $data): bool;

    /**
     * @param Table $data
     * @return Query
     */
    public function select(Table $data): Query;

    /**
     * @param Table $data
     * @return bool
     */
    public function delete(Table $data): bool;

    /**
     * @param Table $table
     * @return bool
     */
    public function truncate(Table $table): bool;

    /**
     * @return array
     */
    public function all(): array;

    /**
     * @return array
     */
    public function first(): array;

    /**
     * @return int
     */
    public function lastPrimaryKey(): int;

    /**
     * @return int
     */
    public function count(): int;
}
