<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\AmpPool\Exceptions\FatalWorkerException;
use IfCastle\Application\ApplicationAbstract;
use IfCastle\Application\EngineInterface;
use IfCastle\Application\EngineRolesEnum;

final class HttpReactorApplication extends ApplicationAbstract
{
    #[\Override]
    protected function defineEngine(): EngineInterface|null
    {
        try {
            $engine                 = parent::defineEngine();
        } catch (\Throwable $exception) {
            throw new FatalWorkerException(
                'define engine error: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        if ($engine === null) {
            throw new FatalWorkerException('Engine is not defined');
        }

        return $engine;
    }

    #[\Override]
    protected function defineEngineRole(): EngineRolesEnum
    {
        return EngineRolesEnum::SERVER;
    }
}
