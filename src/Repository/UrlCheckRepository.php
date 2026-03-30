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

    private string $tableName;

    private const string PARAM_ID = ":id";
    private const string PARAM_URL_ID = ":url_id";
    private const string PARAM_STATUS = ":status";
    private const string PARAM_H1 = ":h1";
    private const string PARAM_TITLE = ":title";
    private const string PARAM_DESCRIPTION = ":description";
    private const string PARAM_TIMESTAMP = ":timestamp";

    private const string ERROR_MESSAGE_FOR_TIMESTAMP = "PDO error: timestamp has a wrong type";
    private const string ERROR_MESSAGE_FOR_ID = "PDO error: can't get last insert id";

    public function __construct(PDO $conn, bool $isTest = false)
    {
        $this->conn = $conn;
        if ($isTest) {
            $this->tableName = "url_checks_test";
        } else {
            $this->tableName = "url_checks";
        }
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
        $params = implode(',', [
            self::PARAM_URL_ID,
            self::PARAM_STATUS,
            self::PARAM_H1,
            self::PARAM_TITLE,
            self::PARAM_DESCRIPTION,
            self::PARAM_TIMESTAMP
        ]);

        $sql = "INSERT INTO {$this->tableName} (url_id, status, h1, title, description, created_at) VALUES " .
               "({$params})";
        $stmt = $this->conn->prepare($sql);

        $urlId = $urlCheck->getUrlId();
        $status = $urlCheck->getStatus();
        $h1 = $urlCheck->getH1();
        $title = $urlCheck->getTitle();
        $description = $urlCheck->getDescription();
        $timestamp = date('Y-m-d H:i:s');

        $stmt->bindParam(self::PARAM_URL_ID, $urlId);
        $stmt->bindParam(self::PARAM_STATUS, $status);
        $stmt->bindParam(self::PARAM_H1, $h1);
        $stmt->bindParam(self::PARAM_TITLE, $title);
        $stmt->bindParam(self::PARAM_DESCRIPTION, $description);
        $stmt->bindParam(self::PARAM_TIMESTAMP, $timestamp);
        $stmt->execute();

        $id = intval($this->conn->lastInsertId());
        $urlCheck->setId(
            $id ? $id : throw new Exception(self::ERROR_MESSAGE_FOR_ID)
        );
        $urlCheck->setTimestamp(
            is_string($timestamp) ? $timestamp : throw new Exception(self::ERROR_MESSAGE_FOR_TIMESTAMP)
        );
    }

    public function update(UrlCheckInterface $urlCheck): void
    {
        $sql = "UPDATE {$this->tableName} SET url_id = " . self::PARAM_URL_ID . ", status = " . self::PARAM_STATUS .
               ", h1 = " . self::PARAM_H1 . ", " . "title = " . self::PARAM_TITLE .
               ", description = " . self::PARAM_DESCRIPTION .
               ", created_at = " . self::PARAM_TIMESTAMP .
               " WHERE id = " . self::PARAM_ID;
        $stmt = $this->conn->prepare($sql);

        $urlId = $urlCheck->getUrlId();
        $status = $urlCheck->getStatus();
        $h1 = $urlCheck->getH1();
        $title = $urlCheck->getTitle();
        $description = $urlCheck->getDescription();
        $timestamp = date('Y-m-d H:i:s');

        $id = $urlCheck->getId();

        $stmt->bindParam(self::PARAM_URL_ID, $urlId);
        $stmt->bindParam(self::PARAM_STATUS, $status);
        $stmt->bindParam(self::PARAM_H1, $h1);
        $stmt->bindParam(self::PARAM_TITLE, $title);
        $stmt->bindParam(self::PARAM_DESCRIPTION, $description);
        $stmt->bindParam(self::PARAM_TIMESTAMP, $timestamp);
        $stmt->bindParam(self::PARAM_ID, $id);
        $stmt->execute();
    }

    public function find(int $id): ?UrlCheckInterface
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = " . self::PARAM_ID;
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(self::PARAM_ID, $id);
        $stmt->execute();

        $urlCheckInfo = $stmt->fetch();
        if (is_array($urlCheckInfo)) {
            $foundId = $urlCheckInfo['id'];
            $timestamp = $urlCheckInfo['created_at'];
            $urlCheck = UrlCheck::fromArray($urlCheckInfo);
            $urlCheck->setId(
                is_int($foundId) ? $foundId : throw new Exception(self::ERROR_MESSAGE_FOR_ID)
            );
            $urlCheck->setTimestamp(
                is_string($timestamp) ? $timestamp : throw new Exception(self::ERROR_MESSAGE_FOR_TIMESTAMP)
            );

            return $urlCheck;
        }

        return null;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE id = " . self::PARAM_ID;
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(self::PARAM_ID, $id);
        $stmt->execute();
    }

    public function getEntities(): array
    {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY created_at DESC";
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
                    is_int($foundId) ? $foundId : throw new Exception(self::ERROR_MESSAGE_FOR_ID)
                );
                $urlCheck->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new Exception(self::ERROR_MESSAGE_FOR_TIMESTAMP)
                );
                $urlChecks[] = $urlCheck;
            }
        }

        return $urlChecks;
    }

    public function getEntitiesByUrlId(int $urlId): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE url_id = " . self::PARAM_URL_ID . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(self::PARAM_URL_ID, $urlId);
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
                    is_int($foundId) ? $foundId : throw new Exception(self::ERROR_MESSAGE_FOR_ID)
                );
                $urlCheck->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new Exception(self::ERROR_MESSAGE_FOR_TIMESTAMP)
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
