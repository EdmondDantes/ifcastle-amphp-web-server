<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\WorkerPool;

class WebServerEngine               extends \IfCastle\Amphp\AmphpEngine
{
    #[\Override]
    public function start(): void
    {
        $workerPool                 = new WorkerPool(logger: $logger);
        $workerPool->run();
    }
}