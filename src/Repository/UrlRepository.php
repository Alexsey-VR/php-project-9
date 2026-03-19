<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\UrlInterface;
use Analyzer\Interfaces\UrlRepositoryInterface;
use Analyzer\Url\Url;

class UrlRepository implements UrlRepositoryInterface
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
        date_default_timezone_set('UTC');
    }

    public function isUnique(UrlInterface $url): bool
    {
        $sql = "SELECT * FROM urls WHERE name=:name";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getUrl();
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return true;
        }

        $id = $row['id'];
        $url->setId(
            is_int($id) ? $id : throw new \Exception("PDO error: found ID has wrond type")
        );

        return false;
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
        if ($this->isUnique($url)) {
            $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
            $stmt = $this->conn->prepare($sql);

            $name = $url->getUrl();
            $timestamp = date('Y-m-d H:i:s');

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':created_at', $timestamp);
            $stmt->execute();

            $id = intval($this->conn->lastInsertId());

            $url->setId(
                $id ? $id : throw new \Exception("PDO error: can't get last insert id")
            );
        }
    }

    public function update(UrlInterface $url): void
    {
        $sql = "UPDATE urls SET name=:name, created_at=:created_at WHERE id=:id";
        $stmt = $this->conn->prepare($sql);

        $name = $url->getUrl();
        $timestamp = date('Y-m-d H:i:s');
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
                is_int($foundId) ? $foundId : throw new \Exception("PDO error: found ID has a wrong type")
            );
            $url->setTimestamp(
                is_string($timestamp) ? $timestamp : throw new \Exception("PDO error: timestamp has a wrong type")
            );
            return $url;
        }

        return null;
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM urls WHERE id=:id";
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
            $items = $stmt->fetchAll(\PDO::FETCH_DEFAULT);
        }

        $urls = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $url = Url::fromArray($item);
                $foundId = $item['id'];
                $timestamp = $item['created_at'];
                $url->setId(
                    is_int($foundId) ? $foundId : throw new \Exception("PDO error: found ID has a wrong type")
                );
                $url->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new \Exception("PDO error: timestamp has a wrong type")
                );
                $urls[] = $url;
            }
        }

        return $urls;
    }
}
