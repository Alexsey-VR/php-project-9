<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Analyzer\Url\Url;

#[CoversClass(Url::class)]
#[CoversMethod(Url::class, 'fromArray')]
class UrlTest extends TestCase
{
    public function testFromArray(): void
    {
        $urlInfo = ['name' => 'https://mail.ru'];
        $url = Url::fromArray($urlInfo);
        $this->assertInstanceOf(Url::class, $url);

        $name = $url->getUrl();
        $this->assertEquals($urlInfo['name'], $name);
    }

    public function testId(): void
    {
        $urlInfo = ['name' => 'https://mail.ru'];
        $url = Url::fromArray($urlInfo);

        $testId = 5;
        $url->setId($testId);
        $id = $url->getId();

        $this->assertEquals($testId, $id);
    }

    public function testTimestamp(): void
    {
        $urlInfo = ['name' => 'https://mail.ru'];
        $url = Url::fromArray($urlInfo);
        $id = 5;
        $url->setId($id);

        $testTimestamp = '2026-03-05 15:30:45';
        $url->setTimestamp($testTimestamp);
        $timestamp = $url->getTimestamp();

        $this->assertEquals($testTimestamp, $timestamp);
    }

    public function testExists(): void
    {
        $urlInfo = ['name' => 'https://mail.ru'];
        $url = Url::fromArray($urlInfo);
        $url->setId(5);

        $this->assertTrue($url->exists());
    }
}
