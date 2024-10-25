<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer\Http;

use IfCastle\OsUtilities\Safe;

class FileContainer extends \IfCastle\Protocol\FileContainer
{
    public function __construct(
        string $fileName,
        ?string $mimeType,
        protected ?string $contents = null
    ) {
        parent::__construct($fileName, $mimeType, $this->contents !== null ? \strlen($this->contents) : 0);
    }

    #[\Override]
    public function getContents(): string
    {
        return $this->contents ?? '';
    }

    #[\Override] public function flushTo(string $fileName): static
    {
        Safe::execute(fn() => \file_put_contents($fileName, $this->contents, \FILE_APPEND));
        return $this;
    }
}
