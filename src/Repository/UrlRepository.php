<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\{UrlInterface, UrlRepositoryInterface};
use Analyzer\Url\Url as Url;
use Analyzer\Exceptions\UrlException as UrlException;
use PDO;

class UrlRepository implements UrlRepositoryInterface
{
    private PDO $connection;

    private const string ERROR_MESSAGE_FOR_TIMESTAMP = "PDO error: timestamp has a wrong type";
    private const string ERROR_MESSAGE_FOR_ID = "PDO error: can't get last insert id";

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
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
        try {
            $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :timestamp)";
            $stmt = $this->connection->prepare($sql);

            $name = $url->getUrl();
            $timestamp = date('Y-m-d H:i:s');

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':timestamp', $timestamp);
            $stmt->execute();
        } catch (UrlException $e) {
            throw new UrlException(
                $e->getMessage()
            );
        }

        $id = intval($this->connection->lastInsertId());

        $url->setId(
            $id ? $id : throw new UrlException(self::ERROR_MESSAGE_FOR_ID)
        );
        $url->setTimestamp(
            is_string($timestamp) ? $timestamp : throw new UrlException(self::ERROR_MESSAGE_FOR_TIMESTAMP)
        );
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

        $urlInfo = $stmt->fetch();
        if (is_array($urlInfo)) {
            $foundId = $urlInfo['id'];
            $timestamp = $urlInfo['created_at'];
            $url = Url::fromArray($urlInfo);
            $url->setId(
                is_int($foundId) ? $foundId : throw new UrlException(self::ERROR_MESSAGE_FOR_ID)
            );
            $url->setTimestamp(
                is_string($timestamp) ? $timestamp : throw new UrlException(self::ERROR_MESSAGE_FOR_TIMESTAMP)
            );
            return $url;
        }

        return null;
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
                    is_int($foundId) ? $foundId : throw new UrlException(self::ERROR_MESSAGE_FOR_ID)
                );
                $url->setTimestamp(
                    is_string($timestamp) ? $timestamp : throw new UrlException(self::ERROR_MESSAGE_FOR_TIMESTAMP)
                );
                $urls[] = $url;
            }
        }

        return $urls;
    }
}
