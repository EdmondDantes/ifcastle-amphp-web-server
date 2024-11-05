<?php

declare(strict_types=1);

namespace IfCastle\AmphpWebServer\Http;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\ReadableStream;
use Amp\Http\Http1\Rfc7230;
use Amp\Http\InvalidHeaderException;
use Amp\TimeoutCancellation;
use IfCastle\Protocol\Exceptions\ParseException;

use function Amp\ByteStream\buffer;

final class HttpBodyParser
{
    /**
     * @var array<string, string>|null
     */
    protected array|null $requestParameters     = null;

    /**
     * @var array<string, FileContainer>|null
     */
    protected array|null $requestFiles          = null;

    /**
     * @throws ParseException
     * @throws BufferException
     */
    public function __construct(
        private readonly string|ReadableStream $body,
        private readonly string                $contentType,
        private readonly int                   $maxBufferSize            = 10_485_760,
        private ?int                           $fieldCountLimit          = null
    ) {
        $this->parseRequestParameters();
    }

    /**
     * @return array<string, string>|null
     */
    public function getRequestParameters(): array|null
    {
        return $this->requestParameters;
    }

    /**
     * @return array<string, FileContainer>|null
     */
    public function getRequestFiles(): array|null
    {
        return $this->requestFiles;
    }

    /**
     * @throws ParseException
     * @throws BufferException
     */
    protected function parseRequestParameters(): void
    {
        if ($this->requestFiles !== null) {
            return;
        }

        /* @phpstan-ignore-next-line */
        $this->fieldCountLimit      ??= (int) \ini_get('max_input_vars') ?? 1000;

        $boundary                   = $this->parseContentBoundary($this->contentType ?? '');

        if ($boundary === null) {
            $this->requestParameters = [];
            $this->requestFiles     = [];
            return;
        }

        $body                       = $this->body;

        if ($body instanceof ReadableStream) {
            $body                   = buffer($body, new TimeoutCancellation(5), $this->maxBufferSize);
        }

        if ($boundary === '') {
            $this->parseUrlEncodedBody($body);
        } else {
            $this->parseMultipartBody($body, $boundary);
        }
    }

    /**
     * Parse the given content-type and returns the boundary if parsing is supported,
     * an empty string content-type is url-encoded mode or null if not supported.
     */
    protected function parseContentBoundary(string $contentType): ?string
    {
        if (\str_starts_with($contentType, 'application/x-www-form-urlencoded')
        ) {
            return '';
        }

        if (!\preg_match(
            '#^\s*multipart/(?:form-data|mixed)(?:\s*;\s*boundary\s*=\s*("?)([^"]*)\1)?$#',
            $contentType,
            $matches
        )) {
            return null;
        }

        return $matches[2];
    }

    /**
     * @param string $body application/x-www-form-urlencoded body.
     * @throws ParseException
     */
    public function parseUrlEncodedBody(string $body): void
    {
        $this->requestParameters    = [];

        foreach (\explode('&', $body, $this->fieldCountLimit) as $pair) {
            $pair                   = \explode('=', $pair, 2);
            $field                  = \urldecode($pair[0]);
            $value                  = \urldecode($pair[1] ?? '');

            $this->requestParameters[$field][] = $value;
        }

        if (\str_contains($pair[1] ?? '', '&')) {
            throw new ParseException('Maximum number of variables exceeded');
        }
    }

    /**
     * Parses the given body multipart body string using the given boundary.
     * @throws ParseException
     */
    public function parseMultipartBody(string $body, string $boundary): void
    {
        $this->requestParameters    = [];
        $this->requestFiles         = [];

        // RFC 7578, RFC 2046 Section 5.1.1
        if (\strncmp($body, "--{$boundary}\r\n", \strlen($boundary) + 4) !== 0) {
            return;
        }

        $exp                        = \explode("\r\n--{$boundary}\r\n", $body, $this->fieldCountLimit);
        $exp[0]                     = \substr($exp[0], \strlen($boundary) + 4);

        $exp[\count($exp) - 1]      = \substr(\end($exp), 0, -\strlen($boundary) - 8);

        foreach ($exp as $entry) {

            if (($position = \strpos($entry, "\r\n\r\n")) === false) {
                throw new ParseException('No header/body boundary found');
            }

            try {
                $headers            = Rfc7230::parseHeaders(\substr($entry, 0, $position + 2));
            } catch (InvalidHeaderException $exception) {
                throw new ParseException('Invalid headers in body part', 0, $exception);
            }

            $entry                  = \substr($entry, $position + 4);

            $count                  = \preg_match(
                '#^\s*form-data(?:\s*;\s*(?:name\s*=\s*"([^"]+)"|filename\s*=\s*"([^"]*)"))+\s*$#',
                $headers['content-disposition'][0] ?? '',
                $matches
            );

            /* @phpstan-ignore-next-line */
            if (!$count || !isset($matches[1])) {
                throw new ParseException('Missing or invalid content disposition');
            }

            // Ignore Content-Transfer-Encoding as deprecated and hence we won't support it
            $name                   = $matches[1];

            if (isset($matches[2])) {
                $this->requestFiles[$name][]        = new FileContainer($matches[2], $headers['content-type'][0] ?? null, $entry);
            } else {
                $this->requestParameters[$name][]   = $entry;
            }
        }

        /* @phpstan-ignore-next-line */
        if (\str_contains($entry ?? '', '--' . $boundary)) {
            throw new ParseException('Maximum number of variables exceeded');
        }
    }
}
