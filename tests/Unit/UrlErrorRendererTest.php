<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\Exceptions\UrlErrorRenderer;
use Slim\Views\PhpRenderer;
use Exception;

use function get_class;

#[CoversClass(UrlErrorRenderer::class)]
class UrlErrorRendererTest extends TestCase
{
    public function testErrorRenderer(): void
    {
        $exceptionRenderer = new UrlErrorRenderer();
        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $exceptionRenderer->setRenderer($slimRenderer);

        $payload = [
            'name' => 'testName',
            'id' => 'testId',
            'timestamp' => 'testTimestamp'
        ];
        $exceptionRenderer->setPayload($payload);

        $builder = $this->getMockBuilder(Exception::class);
        $exceptionStub = $builder->getMock();

        $exceptionInfo = $exceptionRenderer->__invoke($exceptionStub, true);

        $this->assertTrue(mb_strpos($exceptionInfo, 'Произошла ошибка') !== false);

        $exceptionInfo = $exceptionRenderer->__invoke($exceptionStub, false);

        $this->assertTrue(mb_strpos($exceptionInfo, 'Произошла ошибка') !== false);
    }
}

