<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\Exceptions\FatalWorkerException;
use IfCastle\AmpPool\Worker\WorkerEntryPointInterface;
use IfCastle\AmpPool\Worker\WorkerInterface;
use IfCastle\Application\Environment\SystemEnvironmentInterface;
use IfCastle\Application\WorkerPool\WorkerTypeEnum;

final class HttpReactor implements WorkerEntryPointInterface
{
    /**
     * @var \WeakReference<WorkerInterface>|null
     */
    private ?\WeakReference $worker = null;

    #[\Override]
    public function initialize(WorkerInterface $worker): void
    {
        $this->worker               = \WeakReference::create($worker);
    }

    #[\Override]
    public function run(): void
    {
        $worker                     = $this->worker->get();

        if ($worker === null) {
            return;
        }

        $poolContext                = $worker->getPoolContext();

        if (empty($poolContext[SystemEnvironmentInterface::APPLICATION_DIR])) {
            throw new FatalWorkerException('Application directory not set in pool context');
        }

        (new WorkerRunner(
            $worker,
            HttpReactorEngine::class,
            $poolContext[SystemEnvironmentInterface::APPLICATION_DIR],
            WorkerTypeEnum::REACTOR->value,
            WebServerApplication::class,
            [WorkerTypeEnum::REACTOR->value]
        ))->runAndDispose();
    }
}
