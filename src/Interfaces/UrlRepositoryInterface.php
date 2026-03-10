<?php

namespace Analyzer\Interfaces;

use Analyzer\Interfaces\UrlInterface;

interface UrlRepositoryInterface
{
    public function save(UrlInterface $url): void;
    public function create(UrlInterface $url): void;
    public function update(UrlInterface $url): void;
    public function find(int $id): ?UrlInterface;
    public function delete(int $id): void;
    
    /**
     * @return array<int,UrlInterface>
     */
    public function getEntities(): array;
}
