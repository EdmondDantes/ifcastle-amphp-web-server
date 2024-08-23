<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\WorkerPool;
use IfCastle\Application\WorkerPool\WorkerTypeEnum;
use IfCastle\AmpPool\WorkerTypeEnum as WorkerPoolTypeEnum;
use IfCastle\Application\WorkerPool\WorkerGroup;
use IfCastle\Application\WorkerPool\WorkerGroupInterface;
use IfCastle\Application\WorkerPool\WorkerPoolBuilderInterface;
use IfCastle\Application\WorkerPool\WorkerPoolInterface;
use IfCastle\Application\WorkerPool\WorkerState;
use IfCastle\Application\WorkerPool\WorkerStateInterface;
use IfCastle\DI\ConfigInterface;
use Psr\Log\LoggerInterface;

class WebServerEngine               extends \IfCastle\Amphp\AmphpEngine
                                    implements WorkerPoolBuilderInterface, WorkerPoolInterface
{
    protected array $workerGroups = [];
    protected WorkerPool|null $workerPool = null;
    
    public function __construct(ConfigInterface $configuration, private readonly LoggerInterface|null $logger = null)
    {
        $this->applyConfiguration($configuration->findSection('server'));
    }
    
    #[\Override]
    public function start(): void
    {
        if($this->workerPool !== null) {
            return;
        }
        
        $this->workerPool       = new WorkerPool(logger: $this->logger);
        
        foreach ($this->workerGroups as $group) {
            $this->workerPool->describeGroup($group);
        }
        
        $this->workerPool->run();
    }
    
    #[\Override]
    public function describeGroup(WorkerGroupInterface $group): void
    {
        $this->workerGroups[]       = $group;
    }
    
    #[\Override]
    public function getAllWorkerState(): array
    {
        $workerStates               = [];
        
        foreach ($this->workerPool->getWorkersStorage()->foreachWorkers() as $workerState) {
            $workerStates[]         = new WorkerState(
                workerId: $workerState->getWorkerId(),
                groupId: $workerState->getGroupId(),
                shouldBeStarted: $workerState->isShouldBeStarted(),
                pid: $workerState->getPid()
            );
        }
        
        return $workerStates;
    }
    
    #[\Override]
    public function getWorkerState(int $workerId): WorkerStateInterface
    {
        $workerState                = $this->workerPool->getWorkersStorage()->getWorkerState($workerId);
        
        return new WorkerState(
            workerId: $workerState->getWorkerId(),
            groupId: $workerState->getGroupId(),
            shouldBeStarted: $workerState->isShouldBeStarted(),
            pid: $workerState->getPid()
        );
    }
    
    #[\Override]
    public function getWorkerGroups(): array
    {
        $workerGroups               = [];
        
        foreach ($this->workerPool->getGroupsScheme() as $group) {
            $workerGroups[]         = new WorkerGroup(
                $group->getEntryPointClass(),
                $this->workerPoolTypeToAppWorkerType($group->getWorkerType()),
                $group->getMinWorkers(),
                $group->getMaxWorkers(),
                $group->getGroupName()
            );
        }
        
        return $workerGroups;
    }
    
    #[\Override]
    public function findGroup(int|string $groupIdOrName): WorkerGroupInterface|null
    {
        $group                     = $this->workerPool->findGroup($groupIdOrName);
        
        if($group === null) {
            return null;
        }
        
        return new WorkerGroup(
            $group->getEntryPointClass(),
            $this->workerPoolTypeToAppWorkerType($group->getWorkerType()),
            $group->getMinWorkers(),
            $group->getMaxWorkers(),
            $group->getGroupName()
        );
    }
    
    #[\Override]
    public function isWorkerRunning(int $workerId): bool
    {
        return $this->workerPool->isWorkerRunning($workerId);
    }
    
    #[\Override]
    public function restartWorker(int $workerId): bool
    {
        return $this->workerPool->restartWorker($workerId);
    }
    
    private function workerPoolTypeToAppWorkerType(WorkerPoolTypeEnum $workerType): WorkerTypeEnum
    {
        return match ($workerType) {
            WorkerPoolTypeEnum::REACTOR => WorkerTypeEnum::REACTOR,
            WorkerPoolTypeEnum::JOB     => WorkerTypeEnum::JOB,
            WorkerPoolTypeEnum::SERVICE => WorkerTypeEnum::SERVICE,
        };
    }
    
    private function applyConfiguration(array|null $config = null): void
    {
        if($config === null) {
            return;
        }
        
        $reactors                   = $config['reactors'] ?? 1;
        $jobs                       = $config['jobs'] ?? 1;
        
        $reactors                   = (int)$reactors;
        $jobs                       = (int)$jobs;
        
        $this->describeGroup(new WorkerGroup(
                                 HttpReactor::class,
                                 WorkerTypeEnum::REACTOR,
                                 (int)$reactors,
                                 0,
                                 'Reactors'
                             ));
        
        $this->describeGroup(new WorkerGroup(
                                 JobWorker::class,
                                 WorkerTypeEnum::JOB,
                                 (int)$jobs,
                                 0,
                                 'Jobs'
                             ));
    }
}