<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer\Http;

use IfCastle\Protocol\Http\ResponseMutable;
use IfCastle\Protocol\ResponseFactoryInterface;
use IfCastle\Protocol\ResponseInterface;

final class ResponseFactory implements ResponseFactoryInterface
{
    #[\Override]
    public function createResponse(
        ?string $protocolName       = null,
        ?string $protocolVersion    = null,
        ?string $protocolRole       = null,
    ): ResponseInterface {
        return new ResponseMutable($protocolName, $protocolVersion, $protocolRole);
    }
}
