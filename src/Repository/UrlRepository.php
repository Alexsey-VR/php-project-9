<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\{UrlInterface, UrlRepositoryInterface};
use Analyzer\Url\Url as Url;
use PDO as PDO;
use Exception as Exception;

class UrlRepository implements UrlRepositoryInterface
{
    private PDO $conn;

    private string $tableName;

    private const string PARAM_ID = ":id";
    private const string PARAM_NAME = ":name";
    private const string PARAM_TIMESTAMP = ":timestamp";

    private const string ERROR_MESSAGE_FOR_TIMESTAMP = "PDO error: timestamp has a wrong type";
    private const string ERROR_MESSAGE_FOR_ID = "PDO error: can't get last insert id";

    public function __construct(PDO $conn, bool $isTest = false)
    {
        $this->conn = $conn;
        if ($isTest) {
            $this->tableName = "urls_test";
        } else {
            $this->tableName = "urls";
        }
        date_default_timezone_set('UTC');
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
        $params = implode(',', [
            self::PARAM_NAME,
            self::PARAM_TIMESTAMP
        ]);

        $sql = "INSERT INTO {$this->tableName} (name, created_at) VALUES ({$params})";
        $stmt = $this->conn->prepare($sql);

        $name = $url->getUrl();
        $timestamp = date('Y-m-d H:i:s');

        $stmt->bindParam(self::PARAM_NAME, $name);
        $stmt->bindParam(self::PARAM_TIMESTAMP, $timestamp);
        $stmt->execute();

        $id = intval($this->conn->lastInsertId());

        $url->setId(
            $id ? $id : throw new Exception(self::ERROR_MESSAGE_FOR_ID)
        );
        $url->setTimestamp(
            is_string($timestamp) ? $timestamp : throw new Exception(self::ERROR_MESSAGE_FOR_TIMESTAMP)
        );
    }

    public function update(UrlInterface $url): void
    {
        $sql = "UPDATE {$this->tableName} SET name = " . self::PARAM_NAME .
               ", created_at = " . self::PARAM_TIMESTAMP .
               " WHERE id = " . self::PARAM_ID;
        $stmt = $this->conn->prepare($sql);

        $name = $url->getUrl();
        $timestamp = $url->getTimestamp();
        $id = $url->getId();

        $stmt->bindParam(self::PARAM_NAME, $name);
        $stmt->bindParam(self::PARAM_TIMESTAMP, $timestamp);
        $stmt->bindParam(self::PARAM_ID, $id);

        $stmt->execute();
    }

    public function find(int $id): ?UrlInterface
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = " . self::PARAM_ID;
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(self::PARAM_ID, $id);
        $stmt->execute();

        $urlInfo = $stmt->fetch();
        if (is_array($urlInfo)) {
            $foundId = $urlInfo['id'];
            $timestamp = $urlInfo['created_at'];
            $url = Url::fromArray($urlInfo);
            $url->setId(
                is_int($foundId) ? $foundId : throw new Exception(self::ERROR_MESSAGE_FOR_ID)
            );
            $url->setTimestamp(
                is_string($timestamp) ? $timestamp : throw new Exception(self::ERROR_MESSAGE_FOR_TIMESTAMP)
            );
            return $url;
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

        $urls = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $url = Url::fromArray($item);
                $foundId = $item['id'];
                $timestamp = $item['created_at'];
                $url->setId(
                    is_int($foundId) ? $foundId : throw new Exception(self::ERROR_MESSAGE_FOR_ID)
                );
                $url->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new Exception(self::ERROR_MESSAGE_FOR_TIMESTAMP)
                );
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }
}
