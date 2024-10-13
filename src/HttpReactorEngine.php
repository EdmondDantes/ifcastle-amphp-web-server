<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\BindContext;
use IfCastle\AmpPool\Worker\WorkerInterface;

final class HttpReactorEngine       extends \IfCastle\Amphp\AmphpEngine
{
    private \WeakReference|null $worker = null;
    
    public function __construct(WorkerInterface $worker)
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
        $httpServer->expose('127.0.0.1:9095', (new BindContext)->withTcpNoDelay());
        
        // 3. Handle incoming connections and start the server
        $httpServer->start(
            new ClosureRequestHandler(static function () use ($worker): Response {
                
                return new Response(
                    HttpStatus::OK,
                    [
                        'content-type' => 'text/plain; charset=utf-8',
                    ],
                    'Hello, World! From worker id: '.$worker->getWorkerId()
                    .' and group id: '.$worker->getWorkerGroupId()
                );
            }),
            new DefaultErrorHandler(),
        );
        
        // 4. Await termination of the worker
        $worker->awaitTermination();
        
        // 5. Stop the HTTP server
        $httpServer->stop();
    }
}