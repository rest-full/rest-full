<?php

namespace Restfull\Datasourse;

use Restfull\ORM\Assembly;

/**
 * Interface AssemblyInterface
 * @package Restfull\Datasourse
 */
interface AssemblyInterface
{

    /**
     * @return int
     */
    public function amountQuery(): int;

    /**
     * @param bool|null $activelastid
     * @return mixed
     */
    public function lastID(bool $activelastid = null);

    /**
     * @return Assembly
     */
    public function queryAndBindValues(): Assembly;

    /**
     * @param array $command
     * @param int $countData
     * @param array $limit
     * @return Assembly
     */
    public function queryAssembly(array $command, int $countData, array $limit): Assembly;

    /**
     * @return Assembly|null
     */
    public function nested(): ?Assembly;

    /**
     * @return Assembly|null
     */
    public function union(): ?Assembly;

    /**
     * @return bool
     */
    public function existQuery(): bool;

    /**
     * @return bool
     */
    public function existShow(): bool;

}