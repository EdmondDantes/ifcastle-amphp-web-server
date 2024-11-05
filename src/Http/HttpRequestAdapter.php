<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer\Http;

use Amp\ByteStream\BufferException;
use Amp\Http\Server\Request;
use IfCastle\Amphp\ReadableStreamAdapter;
use IfCastle\Async\ReadableStreamInterface;
use IfCastle\DI\DisposableInterface;
use IfCastle\Protocol\Exceptions\ParseException;
use IfCastle\Protocol\FileContainerInterface;
use IfCastle\Protocol\Http\HttpRequestForm;
use IfCastle\Protocol\Http\HttpRequestInterface;
use Psr\Http\Message\UriInterface as PsrUri;

class HttpRequestAdapter implements HttpRequestInterface, DisposableInterface
{
    private HttpRequestForm|null|false $form = false;

    public function __construct(private readonly Request $request) {}

    #[\Override]
    public function getUri(): PsrUri
    {
        return $this->request->getUri();
    }

    #[\Override]
    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    #[\Override] public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    #[\Override]
    public function getHeader(string $name): array
    {
        return $this->request->getHeaderArray($name);
    }

    #[\Override] public function getHeaderLine(string $name): string
    {
        return $this->request->getHeader($name);
    }

    #[\Override]
    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    #[\Override]
    public function getCookies(): array
    {
        $result                     = [];

        foreach ($this->request->getCookies() as $cookie) {
            $result[$cookie->getName()] = $cookie->getValue();
        }

        return $result;
    }

    #[\Override]
    public function getBodySize(): int
    {
        return 0;
    }

    #[\Override]
    public function getBody(): string
    {
        return '';
    }

    #[\Override]
    public function getBodyStream(): ?ReadableStreamInterface
    {
        return new ReadableStreamAdapter($this->request->getBody());
    }

    #[\Override]
    public function getRequestParameters(): array
    {
        return $this->request->getQueryParameters();
    }

    #[\Override]
    public function getRequestParameter(string $name): mixed
    {
        return $this->request->getQueryParameter($name);
    }

    #[\Override]
    public function requestParameters(string ...$names): array
    {
        $result                     = [];

        foreach ($this->request->getQueryParameters() as $key => $parameter) {
            if (\in_array($key, $names, true)) {
                $result[$key]       = $parameter;
            }
        }

        return $result;
    }

    #[\Override]
    public function requestParametersWithNull(string ...$names): array
    {
        $result                     = [];

        foreach ($names as $name) {
            $result[$name]          = $this->request->getQueryParameter($name);
        }

        return $result;
    }

    #[\Override]
    public function isRequestParametersExist(string ...$names): bool
    {
        $parameters                 = $this->request->getQueryParameters();

        foreach ($names as $name) {
            if (false === \array_key_exists($name, $parameters)) {
                return false;
            }
        }

        return true;
    }

    #[\Override]
    public function isRequestParametersDefined(string ...$names): bool
    {
        $parameters                 = $this->request->getQueryParameters();

        foreach ($names as $name) {
            if (false === \array_key_exists($name, $parameters) || null === $parameters[$name]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws ParseException
     * @throws BufferException
     */
    #[\Override]
    public function getUploadedFiles(): array
    {
        $result                     = [];

        foreach ($this->retrieveRequestForm()?->files ?? [] as $name => $file) {
            if ($file instanceof FileContainerInterface) {
                $result[$name]      = $file;
            }
        }

        return $result;
    }

    /**
     * @throws ParseException
     * @throws BufferException
     */
    #[\Override]
    public function getUploadedFile(string $name): ?FileContainerInterface
    {
        foreach ($this->retrieveRequestForm()?->files ?? [] as $fileName => $file) {
            if ($fileName === $name && $file instanceof FileContainerInterface) {
                return $file;
            }
        }

        return null;
    }

    #[\Override]
    public function hasUploadedFile(string $name): bool
    {
        return $this->request->hasQueryParameter($name);
    }

    /**
     * @throws ParseException
     * @throws BufferException
     */
    #[\Override]
    public function retrieveRequestForm(): HttpRequestForm|null
    {
        if ($this->form === false) {
            return null;
        }

        if ($this->form !== null) {
            return $this->form;
        }

        $contentType                = $this->request->getHeader('content-type');

        if (false === \in_array($contentType, ['application/x-www-form-urlencoded', 'multipart/form-data'], true)) {
            $this->form             = false;
            return null;
        }

        $body                       = new HttpBodyParser(
            $this->request->getBody(),
            $contentType
        );

        $this->form                 = new HttpRequestForm(
            $this->request->getQueryParameters(),
            $body->getRequestParameters(),
            $body->getRequestFiles()
        );

        return $this->form;
    }

    #[\Override]
    public function dispose(): void
    {
        $this->form                 = null;
    }
}
