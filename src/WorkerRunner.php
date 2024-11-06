<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\Exceptions\FatalWorkerException;
use IfCastle\AmpPool\Worker\WorkerInterface;
use IfCastle\Application\Bootloader\BootloaderExecutorInterface;
use IfCastle\Application\EngineInterface;
use IfCastle\Application\Runner;

final class WorkerRunner extends Runner
{
    protected bool $throwEngineException = true;

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
    protected function buildBootloader(): BootloaderExecutorInterface
    {
        try {
            return parent::buildBootloader();
        } catch (\Throwable $exception) {
            // We must completely stop the entire application if the bootloader throws an exception.
            // In this case, continuing operation is impossible.
            // The exception will be caught by the parent process and the application will be stopped.
            throw new FatalWorkerException('Bootloader error: ' . $exception->getMessage(), $exception->getCode(), $exception);
        }
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
