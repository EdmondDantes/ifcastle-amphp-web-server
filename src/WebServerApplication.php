<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Application\ApplicationAbstract;
use IfCastle\Application\EngineRolesEnum;

class WebServerApplication extends ApplicationAbstract
{
    public const array TAGS = ['amphpWebServer'];

    #[\Override]
    protected function defineEngineRole(): EngineRolesEnum
    {
        return EngineRolesEnum::SERVER;
    }
}
