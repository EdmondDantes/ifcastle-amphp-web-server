<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\WorkerPool;
use Psr\Log\LoggerInterface;

class WebServerEngine               extends \IfCastle\Amphp\AmphpEngine
{
    public function __construct(private readonly LoggerInterface|null $logger = null) {}
    
    #[\Override]
    public function start(): void
    {
        $workerPool                 = new WorkerPool(logger: $this->logger);
        $workerPool->run();
    }
}