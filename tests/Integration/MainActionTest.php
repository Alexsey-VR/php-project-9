<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Views\PhpRenderer;
use Slim\Factory\AppFactory;
use Slim\Http\Response;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Controllers\MainAction;

#[CoversClass(MainAction::class)]
class MainActionTest extends TestCase
{
    public function testInvoke(): void
    {
        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $templatePath = __DIR__ . '/../../templates';
        $slimRenderer = new PhpRenderer($templatePath);

        $mainAction = new MainAction($messagesMock, $slimRenderer);

        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestMock = $serverRequestMockBuilder->getMock();

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $psrResponse = $mainAction->__invoke(
            $serverRequestMock,
            $response
        );

        $this->assertTrue($psrResponse->getStatusCode() === 200);
    }
}
