<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
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
    private const CHECK_INFO = [
        'urlId' => 1,
        'status' => 200,
        'h1' => 'Sample h1',
        'title' => 'Sample title',
        'description' => 'Sample description'
    ];

    public function testFromArray(): void
    {
        $check = Check::fromArray(self::CHECK_INFO);
        $id = 5;
        $check->setId($id);

        $this->assertInstanceOf(Check::class, $check);
        $this->assertEquals($id, $check->getId());
        $this->assertEquals(self::CHECK_INFO['urlId'], $check->getUrlId());
        $this->assertEquals(self::CHECK_INFO['status'], $check->getStatus());
        $this->assertEquals(self::CHECK_INFO['h1'], $check->getH1());
        $this->assertEquals(self::CHECK_INFO['title'], $check->getTitle());
        $this->assertEquals(self::CHECK_INFO['description'], $check->getDescription());
        $this->assertTrue($check->exists());
    }

    public function testTimestamp(): void
    {
        $check = Check::fromArray(self::CHECK_INFO);

        $testTimestamp =  '2026-03-19 20:30:45';
        $check->setTimestamp($testTimestamp);

        $this->assertEquals($testTimestamp, $check->getTimestamp());
    }
}
