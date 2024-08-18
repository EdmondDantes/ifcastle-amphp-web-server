<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer;

use IfCastle\Amphp\AmphpEngine;

class WebServerEngine               extends AmphpEngine
{
    #[\Override]
    public function isServer(): bool
    {
        return true;
    }
    
    #[\Override]
    public function isProcess(): bool
    {
        return false;
    }
    
    #[\Override]
    public function isConsole(): bool
    {
        return false;
    }
}