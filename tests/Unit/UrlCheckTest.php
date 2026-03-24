<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\UrlCheck\UrlCheck;

#[CoversClass(UrlCheck::class)]
#[CoversMethod(UrlCheck::class, 'fromArray')]
#[CoversMethod(UrlCheck::class, 'setId')]
#[CoversMethod(UrlCheck::class, 'getId')]
#[CoversMethod(UrlCheck::class, 'setUrlId')]
#[CoversMethod(UrlCheck::class, 'getUrlId')]
#[CoversMethod(UrlCheck::class, 'setStatus')]
#[CoversMethod(UrlCheck::class, 'getStatus')]
#[CoversMethod(UrlCheck::class, 'setH1')]
#[CoversMethod(UrlCheck::class, 'getH1')]
#[CoversMethod(UrlCheck::class, 'setTitle')]
#[CoversMethod(UrlCheck::class, 'getTitle')]
#[CoversMethod(UrlCheck::class, 'setDescription')]
#[CoversMethod(UrlCheck::class, 'getDescription')]
#[CoversMethod(UrlCheck::class, 'setTimestamp')]
#[CoversMethod(UrlCheck::class, 'getTimestamp')]
#[CoversMethod(UrlCheck::class, 'exists')]
class UrlCheckTest extends TestCase
{
    public function testFromArray(): void
    {
        $urlCheckInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);
        $id = 5;
        $urlCheck->setId($id);

        $this->assertInstanceOf(UrlCheck::class, $urlCheck);
        $this->assertEquals($id, $urlCheck->getId());
        $this->assertEquals($urlCheckInfo['first']['url_id'], $urlCheck->getUrlId());
        $this->assertEquals($urlCheckInfo['first']['status'], $urlCheck->getStatus());
        $this->assertEquals($urlCheckInfo['first']['h1'], $urlCheck->getH1());
        $this->assertEquals($urlCheckInfo['first']['title'], $urlCheck->getTitle());
        $this->assertEquals($urlCheckInfo['first']['description'], $urlCheck->getDescription());
        $this->assertTrue($urlCheck->exists());
    }

    public function testTimestamp(): void
    {
        $urlCheckInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);
        $id = 5;
        $urlCheck->setId($id);

        $testTimestamp =  '2026-03-19 20:30:45';
        $urlCheck->setTimestamp($testTimestamp);

        $this->assertEquals($testTimestamp, $urlCheck->getTimestamp());
    }
}
