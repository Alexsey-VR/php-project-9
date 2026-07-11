<?php

namespace Analyzer\Repository;

use Analyzer\Interfaces\{UrlInterface, UrlRepositoryInterface};
use Analyzer\Exceptions\UrlRepositoryException as UrlRepositoryException;
use Valitron\Validator;
use PDO;

class ValidatedUrlRepository implements UrlRepositoryInterface
{
    private PDO $connection;
    private UrlRepositoryInterface $repo;
    private string $message;
    private bool $status;

    private const string SUCCESS_MESSAGE = "Страница успешно добавлена";
    private const string ERROR_MESSAGE_FOR_UNIQUE = "Страница уже существует";
    private const int MAX_URL_NAME_LENGTH = 255;

    public function __construct(UrlRepositoryInterface $repo, PDO $connection)
    {
        $this->connection = $connection;
        $this->repo = $repo;
        $this->message = self::SUCCESS_MESSAGE;
    }

    private function normalize(string $urlName): string
    {
        $urlNameUTF8 = mb_convert_encoding($urlName, 'UTF-8');
        $trimmedUrlName = mb_ltrim($urlNameUTF8);
        return mb_strtolower($trimmedUrlName);
    }

    public function isUnique(UrlInterface $url): bool
    {
        $sql = "SELECT * FROM urls WHERE name SIMILAR TO :name";
        $stmt = $this->connection->prepare($sql);
        $name = $url->getUrl();
        $normalizedName = $this->normalize(
            is_string($name) ? $name : ''
        );

        $parsedUrl = parse_url($normalizedName);
        $onlyDomain = '';
        if (is_array($parsedUrl)) {
            $onlyDomain = array_key_exists('host', $parsedUrl) ? "%{$parsedUrl['host']}%" : '';
        }

        $stmt->bindParam(':name', $onlyDomain);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return true;
        }

        $id = is_int($id = $row['id']) ? $id : throw new UrlRepositoryException(50001);
        $url->setId($id);

        $this->status = false;
        $this->setMessage(self::ERROR_MESSAGE_FOR_UNIQUE);

        return false;
    }

    public function validate(UrlInterface $url): bool
    {
        $validator = new Validator(['url' => $url->getUrl()]);
        $this->status = true;

        $validator->rules(['required' => ['url']]);

        /**
         * @return bool
         */
        $result = $validator->validate();
        if (!$result) {
            $this->setMessage("URL не должен быть пустым");
            $this->status = false;
            return $this->status;
        }

        $validator->rules(
            [
                'lengthMax' =>
                [
                    ['url', self::MAX_URL_NAME_LENGTH]
                ]
            ]
        );

        $result = $validator->validate();
        if (!$result) {
            $this->setMessage("URL превышает 255 символов");
            $this->status = false;
            return $this->status;
        }

        $validator->rules(['url' => ['url']]);
        $result = $validator->validate();
        if (!$result) {
            $this->setMessage("Некорректный URL");
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
        if ($this->validate($url) && $this->isUnique($url)) {
            $normalizedUrlName = $this->normalize(
                $url->getUrl() ?? ''
            );
            $url->setUrl($normalizedUrlName);
            $this->repo->create($url);
        }

        if (!$this->status) {
            $this->message = (mb_strpos($this->getMessage(), 'Unique violation') !== false) ?
                self::ERROR_MESSAGE_FOR_UNIQUE : $this->getMessage();
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

    private function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isValid(): bool
    {
        return $this->status;
    }
}
