<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Application\RequestEnvironment\RequestEnvironmentInterface;
use Amp\Http\Server\Request;
use IfCastle\Protocol\RequestInterface;

class HttpProtocolBuilder
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
        
        //$requestEnvironment->set
    }
}