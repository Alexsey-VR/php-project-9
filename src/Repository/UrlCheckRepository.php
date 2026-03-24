<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\UrlCheckRepositoryInterface;
use Analyzer\Interfaces\UrlCheckInterface;
use Analyzer\UrlCheck\UrlCheck as UrlCheck;
use PDO as PDO;
use Exception as Exception;

class UrlCheckRepository implements UrlCheckRepositoryInterface
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        date_default_timezone_set('UTC');
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
        $stmt = $this->conn->prepare($sql);

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

        $id = intval($this->conn->lastInsertId());
        $urlCheck->setId(
            $id ? $id : throw new Exception("PDO error: can't get last insert id")
        );
        $urlCheck->setTimestamp(
            is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
        );
    }

    public function update(UrlCheckInterface $urlCheck): void
    {
        $sql = "UPDATE url_checks SET url_id=:url_id, status=:status, h1=:h1, " .
               "title=:title, description=:description, created_at=:timestamp WHERE id=:id";
        $stmt = $this->conn->prepare($sql);

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
        $sql = "SELECT * FROM url_checks WHERE id=:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $urlCheckInfo = $stmt->fetch();
        if (is_array($urlCheckInfo)) {
            $foundId = $urlCheckInfo['id'];
            $timestamp = $urlCheckInfo['created_at'];
            $urlCheck = UrlCheck::fromArray($urlCheckInfo);
            $urlCheck->setId(
                is_int($foundId) ? $foundId : throw new Exception("PDO error: found ID has a wrong type")
            );
            $urlCheck->setTimestamp(
                is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
            );

            return $urlCheck;
        }

        return null;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM url_checks WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function getEntities(): array
    {
        $sql = "SELECT * FROM url_checks ORDER BY created_at DESC";
        $stmt = $this->conn->query($sql);

        $items = [];
        if ($stmt) {
            $items = $stmt->fetchAll(PDO::FETCH_DEFAULT);
        }

        $urlChecks = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $urlCheck = UrlCheck::fromArray($item);
                $foundId = $item['id'];
                $timestamp = $item['created_at'];
                $urlCheck->setId(
                    is_int($foundId) ? $foundId : throw new Exception("PDO error: found ID has a wrong type")
                );
                $urlCheck->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
                );
                $urlChecks[] = $urlCheck;
            }
        }

        return $urlChecks;
    }

    public function getEntitiesByUrlId(int $urlId): array
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':url_id', $urlId);
        $stmt->execute();

        $items = [];
        if ($stmt) {
            $items = $stmt->fetchAll(PDO::FETCH_DEFAULT);
        }

        $urlChecks = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $urlCheck = UrlCheck::fromArray($item);
                $foundId = $item['id'];
                $timestamp = $item['created_at'];
                $urlCheck->setId(
                    is_int($foundId) ? $foundId : throw new Exception("PDO error: found ID has a wrong type")
                );
                $urlCheck->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
                );
                $urlChecks[] = $urlCheck;
            }
        }

        return $urlChecks;
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }
}
