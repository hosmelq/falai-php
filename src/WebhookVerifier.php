<?php

declare(strict_types=1);

namespace HosmelQ\FalAI;

use function Safe\base64_decode;
use function Safe\file_get_contents;
use function Safe\hex2bin;
use function Safe\json_decode;

use HosmelQ\FalAI\Exceptions\WebhookVerificationException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Safe\Exceptions\StringsException;
use SodiumException;
use Throwable;

class WebhookVerifier
{
    /**
     * Default cache key for storing JWKS data.
     */
    private const DEFAULT_CACHE_KEY = 'falai_jwks';

    /**
     * Default TTL for cached JWKS data (24 hours in seconds).
     */
    private const DEFAULT_CACHE_TTL = 24 * 60 * 60;

    /**
     * The URL for fetching the JWKS (JSON Web Key Set) from fal.ai.
     */
    private const JWKS_URL = 'https://rest.alpha.fal.ai/.well-known/jwks.json';

    /**
     * Required HTTP headers for webhook verification.
     *
     * @var list<string>
     */
    private const REQUIRED_HEADERS = [
        'X-Fal-Webhook-Request-Id',
        'X-Fal-Webhook-Signature',
        'X-Fal-Webhook-Timestamp',
        'X-Fal-Webhook-User-Id',
    ];

    /**
     * Maximum allowed time difference between webhook timestamp and current time (in seconds).
     */
    private const TIMESTAMP_TOLERANCE = 5 * 60;

    /**
     * Create a new webhook verifier instance.
     */
    public function __construct(
        private readonly ?CacheInterface $cache = null,
        private readonly string $cacheKey = self::DEFAULT_CACHE_KEY,
        private readonly int $cacheTtl = self::DEFAULT_CACHE_TTL,
    ) {
    }

    /**
     * Verify a webhook signature.
     *
     * @param array<string, string> $headers
     *
     * @throws WebhookVerificationException
     * @throws InvalidArgumentException
     */
    public function verify(string $body, array $headers): bool
    {
        $this->validateHeaders($headers);
        $this->validateTimestamp($headers['X-Fal-Webhook-Timestamp']);

        return $this->verifySignature(
            $this->constructMessage($headers, $body),
            $this->decodeSignature($headers['X-Fal-Webhook-Signature']),
            $this->getPublicKeys()
        );
    }

    /**
     * Decode a base64url-encoded string.
     */
    private function base64UrlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $remainder = strlen($data) % 4;

        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode($data, true);
    }

    /**
     * Construct the verification message from headers and body hash.
     *
     * @param array<string, string> $headers
     */
    private function constructMessage(array $headers, string $body): string
    {
        return implode("\n", [
            $headers['X-Fal-Webhook-Request-Id'],
            $headers['X-Fal-Webhook-User-Id'],
            $headers['X-Fal-Webhook-Timestamp'],
            hash('sha256', $body),
        ]);
    }

    /**
     * Decode the hexadecimal-encoded signature.
     *
     * @throws WebhookVerificationException
     */
    private function decodeSignature(string $signature): string
    {
        try {
            $decoded = hex2bin($signature);
        } catch (StringsException) {
            throw WebhookVerificationException::invalidSignatureFormat();
        }

        if (strlen($decoded) !== 64) {
            throw WebhookVerificationException::invalidSignatureFormat();
        }

        return $decoded;
    }

    /**
     * Fetch the JWKS from the remote endpoint.
     *
     * @return array<string, mixed>
     *
     * @throws WebhookVerificationException
     */
    private function fetchJwks(): array
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => BaseFalConnector::USER_AGENT,
                ],
            ]);

            /** @var array<string, mixed> $jwks */
            $jwks = json_decode(file_get_contents(self::JWKS_URL, false, $context), true);

            return $jwks;
        } catch (Throwable $throwable) {
            throw WebhookVerificationException::jwksError('Failed to fetch JWKS: '.$throwable->getMessage());
        }
    }

    /**
     * Get the public keys from the cache or fetch them if needed.
     *
     * @return list<string>
     *
     * @throws WebhookVerificationException
     * @throws InvalidArgumentException
     */
    private function getPublicKeys(): array
    {
        $jwks = null;

        if ($this->cache instanceof CacheInterface) {
            $data = $this->cache->get($this->cacheKey);

            if (is_array($data)) {
                $jwks = $data;
            }
        }

        if (is_null($jwks)) {
            $jwks = $this->fetchJwks();

            if ($this->cache instanceof CacheInterface) {
                $this->cache->set($this->cacheKey, $jwks, $this->cacheTtl);
            }
        }

        if (! isset($jwks['keys']) || ! is_array($jwks['keys'])) {
            throw WebhookVerificationException::jwksError('Invalid JWKS format: missing keys array');
        }

        $keys = [];

        foreach ($jwks['keys'] as $key) {
            if (is_array($key) && isset($key['x']) && is_string($key['x'])) {
                $keys[] = $this->base64UrlDecode($key['x']);
            }
        }

        if ($keys === []) {
            throw WebhookVerificationException::jwksError('No valid public keys found in JWKS');
        }

        return $keys;
    }

    /**
     * Validate that all required headers are present and not empty.
     *
     * @param array<string, string> $headers
     *
     * @throws WebhookVerificationException
     */
    private function validateHeaders(array $headers): void
    {
        foreach (self::REQUIRED_HEADERS as $header) {
            if (! isset($headers[$header]) || mb_trim($headers[$header]) === '') {
                throw WebhookVerificationException::missingHeader($header);
            }
        }
    }

    /**
     * Validate that the webhook timestamp is within the acceptable tolerance window.
     *
     * @throws WebhookVerificationException
     */
    private function validateTimestamp(string $timestamp): void
    {
        if (abs(time() - (int) $timestamp) > self::TIMESTAMP_TOLERANCE) {
            throw WebhookVerificationException::invalidTimestamp($timestamp);
        }
    }

    /**
     * Verify the signature against the provided public keys using ED25519.
     *
     * @param list<string> $keys
     *
     * @throws WebhookVerificationException
     */
    private function verifySignature(string $message, string $signature, array $keys): bool
    {
        if (! extension_loaded('sodium')) {
            throw WebhookVerificationException::missingSodiumExtension();
        }

        foreach ($keys as $key) {
            if (strlen($key) !== 32) {
                continue;
            }

            try {
                if ($signature !== '' && sodium_crypto_sign_verify_detached($signature, $message, $key)) {
                    return true;
                }
            } catch (SodiumException) {
                continue;
            }
        }

        throw WebhookVerificationException::invalidSignature();
    }
}
