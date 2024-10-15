<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Application\RequestEnvironment\RequestEnvironmentInterface;
use Amp\Http\Server\Request;
use IfCastle\Protocol\Http\HttpRequestInterface;
use IfCastle\Protocol\RequestInterface;

final class HttpProtocolBuilder
{
    public function __invoke(RequestEnvironmentInterface $requestEnvironment): void
    {
        if($requestEnvironment->hasDependency(RequestInterface::class)) {
            return;
        }
        
        $originalRequest            = $requestEnvironment->originalRequest();
        
        
        if(false === $originalRequest instanceof Request) {
            return;
        }
        
        $requestEnvironment->set(HttpRequestInterface::class, new Http\HttpRequestAdapter($originalRequest));
    }
}