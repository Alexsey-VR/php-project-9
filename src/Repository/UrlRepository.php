<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\{UrlInterface, UrlRepositoryInterface};
use Analyzer\Interfaces\AppExceptionInterface;
use Analyzer\Url\Url as Url;
use Analyzer\Exceptions\UrlRepositoryException as UrlRepositoryException;
use PDO;

class UrlRepository implements UrlRepositoryInterface
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        date_default_timezone_set('UTC');
    }

    /**
     * @param array<mixed> $dbData
     * @return array<int, UrlInterface>
     */
    private function getUrlList(array $dbData): array
    {
        $urls = [];
        foreach ($dbData as $item) {
            if (is_array($item)) {
                $url = Url::fromArray($item);
                $foundId = $item['id'];
                $timestamp = $item['created_at'];
                $url->setId(
                    is_int($foundId) ? $foundId : throw new UrlRepositoryException(50001)
                );
                $url->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new UrlRepositoryException(50002)
                );
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function save(UrlInterface $url): void
    {
        if ($url->exists()) {
            $this->update($url);
        } else {
            $this->create($url);
        }
    }

    public function create(UrlInterface $url): void
    {
        try {
            $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :timestamp)";
            $stmt = $this->connection->prepare($sql);

            $name = $url->getUrl();
            $timestamp = date('Y-m-d H:i:s');

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':timestamp', $timestamp);
            $stmt->execute();
        } catch (AppExceptionInterface $e) {
            throw new UrlRepositoryException(40401);
        }

        $id = intval($this->connection->lastInsertId()) ?: throw new UrlRepositoryException(50003);
        $url->setId($id);
        $url->setTimestamp($timestamp);
    }

    public function update(UrlInterface $url): void
    {
        $sql = "UPDATE urls SET name = :name" .
               ", created_at = :timestamp" .
               " WHERE id = :id";
        $stmt = $this->connection->prepare($sql);

        $name = $url->getUrl();
        $timestamp = $url->getTimestamp();
        $id = $url->getId();

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':id', $id);

        $stmt->execute();
    }

    public function find(int $id): ?UrlInterface
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $result = $this->getUrlList([$stmt->fetch()]);

        return isset($result[0]) ? $result[0] : null;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM urls WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function getEntities(): array
    {
        $sql = "SELECT * FROM urls ORDER BY created_at DESC";
        $stmt = $this->connection->query($sql);

        return $this->getUrlList(
            $stmt !== false ? $stmt->fetchAll() : []
        );
    }
}
