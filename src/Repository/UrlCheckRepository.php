<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\UrlCheckRepositoryInterface;
use Analyzer\Interfaces\UrlCheckInterface;
use Analyzer\UrlCheck\UrlCheck as UrlCheck;
use Analyzer\Exceptions\UrlCheckRepositoryException as UrlCheckRepositoryException;
use PDO;

class UrlCheckRepository implements UrlCheckRepositoryInterface
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        date_default_timezone_set('UTC');
    }

    /**
     * @param array<mixed> $dbData
     * @return array<int, UrlCheckInterface>
     */
    private function getUrlCheckList(array $dbData): array
    {
        $urlChecks = [];
        foreach ($dbData as $item) {
            if (is_array($item)) {
                $urlCheck = UrlCheck::fromArray($item);
                $foundId = $item['id'];
                $timestamp = $item['created_at'];

                $validId = is_int($foundId) ? $foundId : throw new UrlCheckRepositoryException(50001);
                $urlCheck->setId($validId);

                $validTimestamp = is_string($timestamp) ? $timestamp : throw new UrlCheckRepositoryException(50002);
                $urlCheck->setTimestamp($validTimestamp);

                $urlChecks[] = $urlCheck;
            }
        }

        return $urlChecks;
    }

    public function save(UrlCheckInterface $urlCheck): void
    {
        if ($urlCheck->exists()) {
            $this->update($urlCheck);
        } else {
            $this->create($urlCheck);
        }
    }

    public function create(UrlCheckInterface $urlCheck): void
    {
        $sql = "INSERT INTO url_checks (url_id, status, h1, title, description, created_at) VALUES " .
               "(:url_id, :status, :h1, :title, :description, :timestamp)";
        $stmt = $this->connection->prepare($sql);

        $urlId = $urlCheck->getUrlId();
        $status = $urlCheck->getStatus();
        $h1 = $urlCheck->getH1();
        $title = $urlCheck->getTitle();
        $description = $urlCheck->getDescription();
        $timestamp = date('Y-m-d H:i:s');

        $stmt->bindParam(':url_id', $urlId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->execute();

        $validId = (
            ($lastId = $this->connection->lastInsertId()) !== false
        )
        ? intval($lastId)
        : throw new UrlCheckRepositoryException(50003);

        $urlCheck->setId($validId);
        $urlCheck->setTimestamp($timestamp);
    }

    public function update(UrlCheckInterface $urlCheck): void
    {
        $sql = "UPDATE url_checks SET url_id = :url_id, status = :status" .
               ", h1 = :h1, " . "title = :title" .
               ", description = :description" .
               ", created_at = :timestamp" .
               " WHERE id = :id";
        $stmt = $this->connection->prepare($sql);

        $urlId = $urlCheck->getUrlId();
        $status = $urlCheck->getStatus();
        $h1 = $urlCheck->getH1();
        $title = $urlCheck->getTitle();
        $description = $urlCheck->getDescription();
        $timestamp = date('Y-m-d H:i:s');

        $id = $urlCheck->getId();

        $stmt->bindParam(':url_id', $urlId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function find(int $id): ?UrlCheckInterface
    {
        $sql = "SELECT * FROM url_checks WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $result = $this->getUrlCheckList([$stmt->fetch()]);

        return array_last($result);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM url_checks WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function getEntities(): array
    {
        $sql = "SELECT * FROM url_checks ORDER BY created_at DESC";
        $stmt = $this->connection->query($sql);

        return $this->getUrlCheckList(
            $stmt !== false ? $stmt->fetchAll() : []
        );
    }

    public function getEntitiesByUrlId(int $urlId): array
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':url_id', $urlId);
        $stmt->execute();

        return $this->getUrlCheckList(
            $stmt !== false ? $stmt->fetchAll() : []
        );
    }

    public function getLastEntities(): array
    {
        $sql = "SELECT DISTINCT ON (url_id) * FROM url_checks ORDER BY url_id, created_at DESC";
        $stmt = $this->connection->query($sql);

        return $this->getUrlCheckList(
            $stmt !== false ? $stmt->fetchAll() : []
        );
    }
}
