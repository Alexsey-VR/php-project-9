<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Controllers\MainAction;

#[CoversClass(MainAction::class)]
class MainActionTest extends TestCase
{
    public function testInvoke()
    {
        session_start();

        $messagesMockBuilder = $this->getMockBuilder(Messages::class);
        $messagesMock = $messagesMockBuilder->getMock();
        $messagesMock->method('getMessages')->willReturn(['OK']);
        $mainAction = new MainAction($messagesMock);

        $this->assertTrue($mainAction instanceof MainAction);

        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        
        $responseMockBuilder = $this->getMockBuilder(ResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();
        
        $phpRendererMockBuilder = $this->getMockBuilder(PhpRenderer::class);
        $phpRendererMock = $phpRendererMockBuilder->getMock();
        
        $responseMockBuilder = $this->getMockBuilder(PsrResponseInterface::class);
        $psrResponseMock = $responseMockBuilder->getMock();
        $phpRendererMock->method('render')->willReturn($psrResponseMock);

        $mainAction->setRenderer($phpRendererMock);
        $mainAction->setTemplate('template stub');
        $psrResponse = $mainAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        $this->assertTrue($psrResponse instanceof PsrResponseInterface);
    }
}
