<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use Amp\Http\Server\Request;
use IfCastle\Application\RequestEnvironment\RequestEnvironmentInterface;
use IfCastle\Protocol\Http\HttpRequestInterface;

final class HttpProtocolBuilder
{
    public function __invoke(RequestEnvironmentInterface $requestEnvironment): void
    {
        if ($requestEnvironment->hasDependency(HttpRequestInterface::class)) {
            return;
        }

        $originalRequest            = $requestEnvironment->originalRequest();

        if (false === $originalRequest instanceof Request) {
            return;
        }

        $requestEnvironment->set(HttpRequestInterface::class, new Http\HttpRequestAdapter($originalRequest));
    }
}
