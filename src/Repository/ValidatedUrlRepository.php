<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\{UrlInterface, UrlRepositoryInterface};
use PDO as PDO;
use Exception as Exception;
use Valitron\Validator;

class ValidatedUrlRepository implements UrlRepositoryInterface
{
    private PDO $conn;
    private UrlRepositoryInterface $repo;
    private string $tableName;
    private string $message;
    private bool $status;

    private const string SUCCESS_MESSAGE = "Страница успешно добавлена";
    private const string PARAM_URL_NAME = ":name";

    public function __construct(UrlRepositoryInterface $repo, bool $isTest = false)
    {
        $this->conn = $repo->getConnection();
        $this->repo = $repo;
        if ($isTest) {
            $this->tableName = "urls_test";
        } else {
            $this->tableName = "urls";
        }
        $this->message = self::SUCCESS_MESSAGE;
    }

    public function isUnique(UrlInterface $url): bool
    {
        $param = self::PARAM_URL_NAME;
        $sql = "SELECT * FROM {$this->tableName} WHERE name={$param}";
        $stmt = $this->conn->prepare($sql);
        $name = $url->getUrl();
        $stmt->bindParam($param, $name);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return true;
        }

        $id = $row['id'];
        $url->setId(
            is_int($id) ? $id : throw new Exception("PDO error: found ID has wrond type")
        );

        return false;
    }

    public function validate(UrlInterface $url): bool
    {
        $validator = new Validator(['url' => $url->getUrl()]);
        $this->status = true;

        if (!$this->isUnique($url)) {
            $this->message = 'Такой адрес уже существует';
            $this->status = false;
        }

        $validator->rules(['required' => ['url']]);
        if ($this->status && !$validator->validate()) {
            $this->message = "URL не должен быть пустым";
            $this->status = false;
        }

        $validator->rules(
            [
                'lengthMax' =>
                [
                    ['url', 255]
                ]
            ]
        );
        if ($this->status && !$validator->validate()) {
            $this->message = "URL превышает 255 символов";
            $this->status = false;
        }

        $validator->rules(['url' => ['url']]);
        if ($this->status && !$validator->validate()) {
            $this->message = "Некорректный URL";
            $this->status = false;
        }

        return $this->status;
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
        if ($this->validate($url)) {
            $this->repo->create($url);
        }
    }

    public function update(UrlInterface $url): void
    {
        $this->repo->update($url);
    }

    public function find(int $id): ?UrlInterface
    {
        return $this->repo->find($id);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    public function getEntities(): array
    {
        return $this->repo->getEntities();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isValid(): bool
    {
        return $this->status;
    }

    public function getConnection(): PDO
    {
        return $this->repo->getConnection();
    }
}
