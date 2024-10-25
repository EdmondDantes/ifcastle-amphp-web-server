<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\BindContext;
use IfCastle\AmpPool\Worker\WorkerInterface;
use IfCastle\Application\Environment\PublicEnvironmentInterface;
use IfCastle\Application\Environment\SystemEnvironmentInterface;
use IfCastle\Application\RequestEnvironment\RequestEnvironment;
use IfCastle\Application\RequestEnvironment\RequestPlanInterface;
use IfCastle\Exceptions\UnexpectedValueType;

final class HttpReactorEngine extends \IfCastle\Amphp\AmphpEngine
{
    private \WeakReference|null $worker = null;

    public function __construct(WorkerInterface $worker, private readonly SystemEnvironmentInterface $systemEnvironment)
    {
        $this->worker               = \WeakReference::create($worker);
    }

    #[\Override]
    public function start(): void
    {
        $worker                     = $this->worker->get();

        if ($worker === null) {
            return;
        }

        $socketFactory              = $worker->getWorkerGroup()->getSocketStrategy()->getServerSocketFactory();
        $clientFactory              = new SocketClientFactory($worker->getLogger());
        $httpServer                 = new SocketHttpServer($worker->getLogger(), $socketFactory, $clientFactory);

        // 2. Expose the server to the network
        $httpServer->expose('127.0.0.1:9095', (new BindContext())->withTcpNoDelay());

        $requestPlan                = $this->systemEnvironment->resolveDependency(RequestPlanInterface::class);
        $systemEnvironment          = $this->systemEnvironment;
        $publicEnvironment          = $systemEnvironment->findDependency(PublicEnvironmentInterface::class);
        $environment                = $publicEnvironment ?? $systemEnvironment;

        // 3. Handle incoming connections and start the server
        $httpServer->start(
            new ClosureRequestHandler(static function (Request $request) use ($requestPlan, $environment): Response {
                $requestEnv         = new RequestEnvironment($request, $environment);

                try {
                    $environment->setRequestEnvironment($requestEnv);
                    $requestPlan->executePlan($requestEnv);
                } finally {
                    $requestEnv->dispose();
                }

                $response           = $requestEnv->getResponse();

                if ($response instanceof Response) {
                    return $response;
                }
                throw new UnexpectedValueType('response', $response, Response::class);

            }),
            new DefaultErrorHandler(),
        );

        // 4. Await termination of the worker
        $worker->awaitTermination();

        // 5. Stop the HTTP server
        $httpServer->stop();
    }
}
