<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\Worker\WorkerEntryPointInterface;
use IfCastle\AmpPool\Worker\WorkerInterface;
use IfCastle\Application\Environment\SystemEnvironmentInterface;

final class HttpReactor implements WorkerEntryPointInterface
{
    private ?\WeakReference $worker = null;

    public function initialize(WorkerInterface $worker): void
    {
        $this->worker               = \WeakReference::create($worker);
    }

    public function run(): void
    {
        $worker                     = $this->worker->get();

        if ($worker === null) {
            return;
        }

        $poolContext                = $worker->getPoolContext();

        if (empty($poolContext[SystemEnvironmentInterface::APPLICATION_DIR])) {
            throw new \RuntimeException('Application directory not set in pool context');
        }

        HttpReactorApplication::$worker = $this->worker;
        HttpReactorApplication::run($poolContext[SystemEnvironmentInterface::APPLICATION_DIR]);
    }
}
