<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Application\Bootloader\BootloaderExecutorInterface;
use IfCastle\Application\Bootloader\BootloaderInterface;
use IfCastle\Application\EngineInterface;

final class Bootloader implements BootloaderInterface
{
    #[\Override]
    public function buildBootloader(BootloaderExecutorInterface $bootloaderExecutor): void
    {
        $bootloaderExecutor->getBootloaderContext()->getSystemEnvironmentBootBuilder()
                           ->bindConstructible(EngineInterface::class, WebServerEngine::class, isThrow: false);
    }
}
