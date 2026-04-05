<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
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
    public function testFromArray(): void
    {
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);
            $this->assertInstanceOf(Url::class, $url);

            $name = $url->getUrl();
            $this->assertEquals($urlInfo['mail']['name'], $name);
        }
    }

    public function testId(): void
    {
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);

            $testId = 5;
            $url->setId($testId);
            $id = $url->getId();

            $this->assertEquals($testId, $id);
            $this->assertTrue($url->exists());
        }   
    }

    public function testTimestamp(): void
    {
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);

            $testTimestamp = '2026-03-05 15:30:45';
            $url->setTimestamp($testTimestamp);

            $this->assertEquals($testTimestamp, $url->getTimestamp());
        }
    }
}
