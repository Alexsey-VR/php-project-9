<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\CheckRepositoryInterface;
use Analyzer\Interfaces\CheckInterface;
use Analyzer\Check\Check as Check;
use PDO as PDO;
use Exception as Exception;

class CheckRepository implements CheckRepositoryInterface
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        date_default_timezone_set('UTC');
    }

    public function save(CheckInterface $check): void
    {
        if ($check->exists()) {
            $this->update($check);
        } else {
            $this->create($check);
        }
    }

    public function create(CheckInterface $check): void
    {
        $sql = "INSERT INTO checks (url_id, status, h1, title, description, checked_at) VALUES " .
               "(:url_id, :status, :h1, :title, :description, :timestamp)";
        $stmt = $this->conn->prepare($sql);

        $urlId = $check->getUrlId();
        $status = $check->getStatus();
        $h1 = $check->getH1();
        $title = $check->getTitle();
        $description = $check->getDescription();
        $timestamp = date('Y-m-d H:i:s');

        $stmt->bindParam(':url_id', $urlId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->execute();

        $id = intval($this->conn->lastInsertId());
        $check->setId(
            $id ? $id : throw new Exception("PDO error: can't get last insert id")
        );
    }

    public function update(CheckInterface $check): void
    {
        $sql = "UPDATE checks SET url_id=:url_id, status=:status, h1=:h1, " .
               "title=:title, description=:description, checked_at=:timestamp WHERE id=:id";
        $stmt = $this->conn->prepare($sql);

        $urlId = $check->getUrlId();
        $status = $check->getStatus();
        $h1 = $check->getH1();
        $title = $check->getTitle();
        $description = $check->getDescription();
        $timestamp = date('Y-m-d H:i:s');
        $id = $check->getId();

        $stmt->bindParam(':url_id', $urlId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function find(int $id): ?CheckInterface
    {
        $sql = "SELECT * FROM checks WHERE id=:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $checkInfo = $stmt->fetch();
        if (is_array($checkInfo)) {
            $foundId = $checkInfo['id'];
            $timestamp = $checkInfo['checked_at'];
            $check = Check::fromArray($checkInfo);
            $check->setId(
                is_int($foundId) ? $foundId : throw new Exception("PDO error: found ID has a wrong type")
            );
            $check->setTimestamp(
                is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
            );

            return $check;
        }

        return null;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM checks WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function getEntities(): array
    {
        $sql = "SELECT * FROM checks ORDER BY checked_at DESC";
        $stmt = $this->conn->query($sql);

        $items = [];
        if ($stmt) {
            $items = $stmt->fetchAll(PDO::FETCH_DEFAULT);
        }

        $checks = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $check = Check::fromArray($item);
                $foundId = $item['id'];
                $timestamp = $item['checked_at'];
                $check->setId(
                    is_int($foundId) ? $foundId : throw new Exception("PDO error: found ID has a wrong type")
                );
                $check->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
                );
                $checks[] = $check;
            }
        }

        return $checks;
    }
}
