<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\Worker\WorkerInterface;
use IfCastle\Application\ApplicationAbstract;
use IfCastle\Application\Bootloader\BootloaderExecutorInterface;
use IfCastle\Application\EngineInterface;
use IfCastle\Application\EngineRolesEnum;

class HttpReactorApplication        extends ApplicationAbstract
{
    static public \WeakReference|null $worker;
    
    #[\Override]
    protected static function predefineEngine(BootloaderExecutorInterface $bootloaderExecutor): void
    {
        $bootloaderExecutor->getBootloaderContext()->getSystemEnvironmentBootBuilder()
                           ->bindConstructible(EngineInterface::class, WebServerEngine::class, isThrow: false)
                           ->bindObject(WorkerInterface::class, self::$worker);
        
        self::$worker               = null;
        
        $bootloaderExecutor->getBootloaderContext()
                           ->getRequestEnvironmentPlan()
                           ->addBuildHandler(new HttpProtocolBuilder);
    }
    
    #[\Override]
    protected function defineEngineRole(): EngineRolesEnum
    {
        return EngineRolesEnum::SERVER;
    }
}