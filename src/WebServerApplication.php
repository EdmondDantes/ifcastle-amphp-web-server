<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Application\ApplicationAbstract;
use IfCastle\Application\EngineInterface;
use IfCastle\ServiceManager\DescriptorRepositoryInterface;

class WebServerApplication          extends ApplicationAbstract
{
    #[\Override]
    protected function engineStartAfter(): void
    {
        (new SymfonyApplication(
            $this->systemEnvironment,
            $this->systemEnvironment->resolveDependency(DescriptorRepositoryInterface::class)
        ))->run();
    }
    
    #[\Override]
    protected function defineEngine(): EngineInterface|null
    {
        return new WebServerEngine;
    }
}