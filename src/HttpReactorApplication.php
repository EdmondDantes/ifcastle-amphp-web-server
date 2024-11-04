<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Application\ApplicationAbstract;
use IfCastle\Application\EngineRolesEnum;

class HttpReactorApplication extends ApplicationAbstract
{
    #[\Override]
    protected function defineEngineRole(): EngineRolesEnum
    {
        return EngineRolesEnum::SERVER;
    }
}
