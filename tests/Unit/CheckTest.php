<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\Check\Check;

#[CoversClass(Check::class)]
#[CoversMethod(Check::class, 'fromArray')]
#[CoversMethod(Check::class, 'setId')]
#[CoversMethod(Check::class, 'getId')]
#[CoversMethod(Check::class, 'setUrlId')]
#[CoversMethod(Check::class, 'getUrlId')]
#[CoversMethod(Check::class, 'setStatus')]
#[CoversMethod(Check::class, 'getStatus')]
#[CoversMethod(Check::class, 'setH1')]
#[CoversMethod(Check::class, 'getH1')]
#[CoversMethod(Check::class, 'setTitle')]
#[CoversMethod(Check::class, 'getTitle')]
#[CoversMethod(Check::class, 'setDescription')]
#[CoversMethod(Check::class, 'getDescription')]
#[CoversMethod(Check::class, 'setTimestamp')]
#[CoversMethod(Check::class, 'getTimestamp')]
#[CoversMethod(Check::class, 'exists')]
class CheckTest extends TestCase
{
    public function testFromArray(): void
    {
        $checkInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/checkInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $check = Check::fromArray($checkInfo['first']);
        $id = 5;
        $check->setId($id);

        $this->assertInstanceOf(Check::class, $check);
        $this->assertEquals($id, $check->getId());
        $this->assertEquals($checkInfo['first']['url_id'], $check->getUrlId());
        $this->assertEquals($checkInfo['first']['status'], $check->getStatus());
        $this->assertEquals($checkInfo['first']['h1'], $check->getH1());
        $this->assertEquals($checkInfo['first']['title'], $check->getTitle());
        $this->assertEquals($checkInfo['first']['description'], $check->getDescription());
        $this->assertTrue($check->exists());
    }

    public function testTimestamp(): void
    {
        $checkInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/checkInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $check = Check::fromArray($checkInfo['first']);
        $id = 5;
        $check->setId($id);

        $testTimestamp =  '2026-03-19 20:30:45';
        $check->setTimestamp($testTimestamp);

        $this->assertEquals($testTimestamp, $check->getTimestamp());
    }
}
