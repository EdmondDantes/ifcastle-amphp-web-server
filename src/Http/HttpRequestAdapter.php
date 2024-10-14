<?php
declare(strict_types=1);

namespace IfCastle\AmphpWebServer\Http;

use IfCastle\Async\ReadableStreamInterface;
use IfCastle\Protocol\FileContainerInterface;
use IfCastle\Protocol\Http\HttpRequestInterface;
use Amp\Http\Server\Request;

class HttpRequestAdapter            implements HttpRequestInterface
{
    public function __construct(private readonly Request $request) {}
    
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
        return $this->request->getCookies();
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
        return $this->request->getBody();
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
            if(in_array($key, $names, true)) {
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
            if(false === array_key_exists($name, $parameters)) {
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
            if(false === array_key_exists($name, $parameters) || null === $parameters[$name]) {
                return false;
            }
        }
        
        return true;
    }
    
    #[\Override]
    public function getUploadedFiles(): array
    {
        $result                     = [];
        
        foreach ($this->request->getQueryParameters() as $name => $file) {
            if($file instanceof FileContainerInterface) {
                $result[$name]      = $file;
            }
        }
        
        return $result;
    }
    
    #[\Override]
    public function getUploadedFile(string $name): ?FileContainerInterface
    {
        $file                       = $this->request->getQueryParameter($name);
        
        if($file instanceof FileContainerInterface) {
            return $file;
        }
        
        return null;
    }
    
    #[\Override]
    public function hasUploadedFile(string $name): bool
    {
        return $this->request->hasQueryParameter($name);
    }
}