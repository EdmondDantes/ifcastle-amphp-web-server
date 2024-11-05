<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\Worker\WorkerInterface;
use IfCastle\Application\Bootloader\BootloaderExecutorInterface;
use IfCastle\Application\EngineInterface;
use IfCastle\Application\Runner;

final class WorkerRunner extends Runner
{
    /**
     * @param string[]             $runtimeTags
     */
    public function __construct(
        private ?WorkerInterface $worker,
        private readonly string $engineClass,
        string $appDir,
        string $appType,
        string $applicationClass,
        array  $runtimeTags = []
    ) {
        parent::__construct($appDir, $appType, $applicationClass, WebServerApplication::TAGS + $runtimeTags);
    }

    #[\Override]
    protected function postConfigureBootloader(BootloaderExecutorInterface $bootloaderExecutor): void
    {
        $bootloaderExecutor->getBootloaderContext()->getSystemEnvironmentBootBuilder()
                           ->bindConstructible(EngineInterface::class, $this->engineClass, false, true)
                           ->bindObject(WorkerInterface::class, $this->worker);

        $bootloaderExecutor->getBootloaderContext()->enabledWarmUp();

        $this->worker               = null;

        $bootloaderExecutor->getBootloaderContext()
                           ->getRequestEnvironmentPlan()
                           ->addBuildHandler(new HttpProtocolBuilder());
    }
}
