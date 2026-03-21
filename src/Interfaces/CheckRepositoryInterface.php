<?php

namespace Analyzer\Interfaces;

use Analyzer\Interfaces\CheckInterface;

interface CheckRepositoryInterface
{
    public function save(CheckInterface $check): void;
    public function create(CheckInterface $check): void;
    public function update(CheckInterface $check): void;
    public function find(int $id): ?CheckInterface;
    public function delete(int $id): void;

    /**
     * @return array<int,CheckInterface>
     */
    public function getEntities(): array;
}
