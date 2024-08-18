<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Application\ApplicationAbstract;
use IfCastle\Application\Bootloader\BootloaderExecutorInterface;
use IfCastle\Application\EngineInterface;
use IfCastle\Application\EngineRolesEnum;
use IfCastle\DI\ConstructibleDependency;

class WebServerApplication          extends ApplicationAbstract
{
    #[\Override]
    protected static function predefineEngine(BootloaderExecutorInterface $bootloaderExecutor): void
    {
        $bootloaderExecutor->getBootloaderContext()->getSystemEnvironmentBootBuilder()
            ->set(EngineInterface::class, new ConstructibleDependency(WebServerEngine::class));
    }
    
    #[\Override]
    protected function defineEngineRole(): EngineRolesEnum
    {
        return EngineRolesEnum::SERVER;
    }
}