<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Application\ApplicationAbstract;
use IfCastle\Application\Bootloader\BootloaderExecutorInterface;
use IfCastle\Application\EngineInterface;
use IfCastle\Application\EngineRolesEnum;

class WebServerApplication extends ApplicationAbstract
{
    #[\Override]
    protected static function predefineEngine(BootloaderExecutorInterface $bootloaderExecutor): void
    {
        $bootloaderExecutor->getBootloaderContext()->getSystemEnvironmentBootBuilder()
            ->bindConstructible(EngineInterface::class, WebServerEngine::class, isThrow: false);
    }

    #[\Override]
    protected function defineEngineRole(): EngineRolesEnum
    {
        return EngineRolesEnum::SERVER;
    }
}
