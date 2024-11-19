<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use Amp\Cancellation;
use IfCastle\Amphp\AmphpEngine;
use IfCastle\AmpPool\Coroutine\CoroutineInterface;
use IfCastle\AmpPool\Exceptions\FatalWorkerException;
use IfCastle\AmpPool\Strategies\JobExecutor\JobHandlerInterface;
use IfCastle\AmpPool\Worker\WorkerEntryPointInterface;
use IfCastle\AmpPool\Worker\WorkerInterface;
use IfCastle\Application\Environment\SystemEnvironmentInterface;
use IfCastle\Application\WorkerPool\WorkerTypeEnum;
use IfCastle\Application\WorkerProtocol\Exceptions\WorkerCommunicationException;
use IfCastle\Application\WorkerProtocol\WorkerProtocolInterface;
use IfCastle\ServiceManager\ExecutorInterface;

final class JobWorker implements WorkerEntryPointInterface, JobHandlerInterface
{
    /**
     * @var \WeakReference<WorkerInterface>|null
     */
    private ?\WeakReference $worker = null;

    /**
     * @var \WeakReference<ExecutorInterface>|null
     */
    private ?\WeakReference $executor = null;

    /**
     * @var \WeakReference<WorkerProtocolInterface>|null
     */
    private ?\WeakReference $workerProtocol = null;

    #[\Override]
    public function initialize(WorkerInterface $worker): void
    {
        $this->worker               = \WeakReference::create($worker);
        $worker->getWorkerGroup()->getJobExecutor()->defineJobHandler($this);
    }

    #[\Override]
    public function run(): void
    {
        $worker                     = $this->worker->get();

        if ($worker === null) {
            return;
        }

        $poolContext                = $worker->getPoolContext();
        $runner                     = null;

        try {
            $runner                 = new WorkerRunner(
                $worker,
                AmphpEngine::class,
                $poolContext[SystemEnvironmentInterface::APPLICATION_DIR],
                WorkerTypeEnum::JOB->value,
                WebServerApplication::class,
                [WorkerTypeEnum::JOB->value]
            );

            $systemEnvironment      = $runner->run()->getSystemEnvironment();

            $executor               = $systemEnvironment->findDependency(ExecutorInterface::class);

            if ($executor === null) {
                throw new FatalWorkerException('The Service Manager ExecutorInterface is not available.'
                                               . ' Job-Worker cannot run properly.');
            }

            $this->executor         = \WeakReference::create($executor);

            $workerProtocol         = $systemEnvironment->findDependency(WorkerProtocolInterface::class);

            if ($workerProtocol === null) {
                throw new FatalWorkerException('The Worker Protocol is not available.'
                                               . ' Job-Worker cannot run properly.');
            }

            $this->workerProtocol   = \WeakReference::create($workerProtocol);

            $worker->awaitTermination();
        } finally {
            $runner?->dispose();
        }
    }

    /**
     * @throws WorkerCommunicationException
     */
    #[\Override]
    public function handleJob(
        string              $data,
        ?CoroutineInterface $coroutine = null,
        ?Cancellation       $cancellation = null
    ): mixed {

        $executor                   = $this->executor->get();
        $workerProtocol             = $this->workerProtocol->get();

        if ($executor === null || $workerProtocol === null) {
            throw new WorkerCommunicationException('The Job-Worker is not properly initialized.');
        }

        $request                    = $workerProtocol->parseWorkerRequest($data);
        $context                    = $request->getExecutionContext();
        $context[CoroutineInterface::class] = $coroutine;
        $context[Cancellation::class] = $cancellation;

        try {
            $response                   = $executor->executeCommand($request->getCommandDescriptor(), context: $context);
        } catch (\Throwable $exception) {
            $response                   = $exception;
        }

        return $workerProtocol->buildWorkerResponse($response);
    }
}
