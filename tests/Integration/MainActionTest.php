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
        session_start();

        $messagesMockBuilder = $this->getMockBuilder(Messages::class);
        $messagesMock = $messagesMockBuilder->getMock();
        $messagesMock->method('getMessages')->willReturn(['OK']);
        $mainAction = new MainAction($messagesMock);

        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestMock = $serverRequestMockBuilder->getMock();

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $phpRendererMockBuilder = $this->getMockBuilder(PhpRenderer::class);
        $phpRendererMock = $phpRendererMockBuilder->getMock();

        $responseMockBuilder = $this->getMockBuilder(PsrResponseInterface::class);
        $psrResponseMock = $responseMockBuilder->getMock();
        $phpRendererMock->method('render')->willReturn($psrResponseMock);

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $mainAction->setRenderer($slimRenderer);
        $mainAction->setTemplate('index.phtml');
        $psrResponse = $mainAction->__invoke(
            $serverRequestMock,
            $response,
            []
        );

        $this->assertTrue($psrResponse->getStatusCode() === 200);
    }
}
