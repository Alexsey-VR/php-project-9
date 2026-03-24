<?php

namespace Analyzer\Interfaces;

use Analyzer\Interfaces\UrlCheckInterface;
use PDO;

interface UrlCheckRepositoryInterface
{
    public function save(UrlCheckInterface $urlCheck): void;
    public function create(UrlCheckInterface $urlCheck): void;
    public function update(UrlCheckInterface $urlCheck): void;
    public function find(int $id): ?UrlCheckInterface;
    public function delete(int $id): void;

    /**
     * @return array<int,UrlCheckInterface>
     */
    public function getEntities(): array;

    /**
     * @return array<int,UrlCheckInterface>
     */
    public function getEntitiesByUrlId(int $urlId): array;

    public function getConnection(): PDO;
}
