<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Slim\Interfaces\CallableResolverInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Analyzer\Exceptions\UrlErrorRenderer;
use Analyzer\Exceptions\UrlErrorHandler;
use Slim\Views\PhpRenderer;
use Exception;

use function get_class;

#[CoversClass(UrlErrorHandler::class)]
#[CoversClass(UrlErrorRenderer::class)]
class UrlErrorTest extends TestCase
{
    public function testErrorRenderer(): void
    {
        $exceptionRenderer = new UrlErrorRenderer();
        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $exceptionRenderer->setRenderer($slimRenderer);

        $builder = $this->getMockBuilder(Exception::class);
        $exceptionStub = $builder->getMock();

        $exceptionInfo = $exceptionRenderer->__invoke($exceptionStub, true);

        $this->assertTrue(mb_strpos($exceptionInfo, 'Произошла ошибка') !== false);

        $exceptionInfo = $exceptionRenderer->__invoke($exceptionStub, false);

        $this->assertTrue(mb_strpos($exceptionInfo, 'Произошла ошибка') !== false);
    }
}
