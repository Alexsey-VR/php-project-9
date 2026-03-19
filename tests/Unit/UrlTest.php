<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Analyzer\Url\Url;

#[CoversClass(Url::class)]
#[CoversMethod(Url::class, 'fromArray')]
#[CoversMethod(Url::class, 'setId')]
#[CoversMethod(Url::class, 'getId')]
#[CoversMethod(Url::class, 'setTimestamp')]
#[CoversMethod(Url::class, 'getTimestamp')]
#[CoversMethod(Url::class, 'exists')]
class UrlTest extends TestCase
{
    private const URL_INFO = ['name' => 'https://mail.ru'];

    public function testFromArray(): void
    {
        $url = Url::fromArray(self::URL_INFO);
        $this->assertInstanceOf(Url::class, $url);

        $name = $url->getUrl();
        $this->assertEquals(self::URL_INFO['name'], $name);
    }

    public function testId(): void
    {
        $url = Url::fromArray(self::URL_INFO);

        $testId = 5;
        $url->setId($testId);
        $id = $url->getId();

        $this->assertEquals($testId, $id);
        $this->assertTrue($url->exists());
    }

    public function testTimestamp(): void
    {
        $url = Url::fromArray(self::URL_INFO);

        $testTimestamp = '2026-03-05 15:30:45';
        $url->setTimestamp($testTimestamp);

        $this->assertEquals($testTimestamp, $url->getTimestamp());
    }
}
