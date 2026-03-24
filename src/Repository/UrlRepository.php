<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\{UrlInterface, UrlRepositoryInterface};
use Analyzer\Url\Url as Url;
use PDO as PDO;
use Exception as Exception;

class UrlRepository implements UrlRepositoryInterface
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
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
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->conn->prepare($sql);

        $name = $url->getUrl();
        $timestamp = date('Y-m-d H:i:s');

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':created_at', $timestamp);
        $stmt->execute();

        $id = intval($this->conn->lastInsertId());

        $url->setId(
            $id ? $id : throw new Exception("PDO error: can't get last insert id")
        );
        $url->setTimestamp(
            is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
        );
    }

    public function update(UrlInterface $url): void
    {
        $sql = "UPDATE urls SET name=:name, created_at=:created_at WHERE id=:id";
        $stmt = $this->conn->prepare($sql);

        $name = $url->getUrl();
        $timestamp = $url->getTimestamp();
        $id = $url->getId();

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':created_at', $timestamp);
        $stmt->bindParam(':id', $id);

        $stmt->execute();
    }

    public function find(int $id): ?UrlInterface
    {
        $sql = "SELECT * FROM urls WHERE id=:id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $urlInfo = $stmt->fetch();
        if (is_array($urlInfo)) {
            $foundId = $urlInfo['id'];
            $timestamp = $urlInfo['created_at'];
            $url = Url::fromArray($urlInfo);
            $url->setId(
                is_int($foundId) ? $foundId : throw new Exception("PDO error: found ID has a wrong type")
            );
            $url->setTimestamp(
                is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
            );
            return $url;
        }

        return null;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM urls WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function getEntities(): array
    {
        $sql = "SELECT * FROM urls ORDER BY created_at DESC";
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
                    is_int($foundId) ? $foundId : throw new Exception("PDO error: found ID has a wrong type")
                );
                $url->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new Exception("PDO error: timestamp has a wrong type")
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
