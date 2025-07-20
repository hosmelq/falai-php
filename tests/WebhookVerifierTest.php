<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Tests;

use HosmelQ\FalAI\Exceptions\WebhookVerificationException;
use HosmelQ\FalAI\WebhookVerifier;
use Mockery;
use Psr\SimpleCache\CacheInterface;

it('validates required headers', function (): void {
    $headers = [
        'X-Fal-Webhook-Request-Id' => 'test-id',
        'X-Fal-Webhook-Timestamp' => (string) time(),
        'X-Fal-Webhook-User-Id' => 'test-user',
    ];

    (new WebhookVerifier())->verify('test body', $headers);
})->throws(WebhookVerificationException::class, 'Missing required header: X-Fal-Webhook-Signature.');

it('rejects timestamps outside tolerance window', function (int $offset): void {
    $headers = [
        'X-Fal-Webhook-Request-Id' => 'test-id',
        'X-Fal-Webhook-Signature' => 'deadbeef',
        'X-Fal-Webhook-Timestamp' => (string) (time() + $offset),
        'X-Fal-Webhook-User-Id' => 'test-user',
    ];

    (new WebhookVerifier())->verify('test body', $headers);
})->with([
    'too old (10 min ago)' => [-600],
    'too new (10 min future)' => [600],
])->throws(WebhookVerificationException::class, 'Timestamp is outside tolerance window');

it('rejects empty or whitespace header values', function (string $value): void {
    $headers = [
        'X-Fal-Webhook-Request-Id' => $value,
        'X-Fal-Webhook-Signature' => str_repeat('00', 64),
        'X-Fal-Webhook-Timestamp' => (string) time(),
        'X-Fal-Webhook-User-Id' => 'test-user',
    ];

    (new WebhookVerifier())->verify('test body', $headers);
})->with([
    'empty' => [''],
    'whitespaces' => ['   '],
])->throws(WebhookVerificationException::class);

it('handles non-numeric timestamp', function (): void {
    $headers = [
        'X-Fal-Webhook-Request-Id' => 'test-id',
        'X-Fal-Webhook-Signature' => 'deadbeef',
        'X-Fal-Webhook-Timestamp' => 'not-a-number',
        'X-Fal-Webhook-User-Id' => 'test-user',
    ];

    (new WebhookVerifier())->verify('test body', $headers);
})->throws(WebhookVerificationException::class, 'Timestamp is outside tolerance window: not-a-number.');

it('rejects invalid signature format', function (string $signature): void {
    $headers = [
        'X-Fal-Webhook-Request-Id' => 'test-id',
        'X-Fal-Webhook-Signature' => $signature,
        'X-Fal-Webhook-Timestamp' => (string) time(),
        'X-Fal-Webhook-User-Id' => 'test-user',
    ];

    (new WebhookVerifier())->verify('test body', $headers);
})->with([
    'wrong length (too short)' => ['deadbeef'],
    'wrong length (too long)' => [str_repeat('ab', 65)],
])->throws(WebhookVerificationException::class, 'Invalid signature format.');

it('fails verification with invalid signature', function (): void {
    $headers = [
        'X-Fal-Webhook-Request-Id' => 'test-id',
        'X-Fal-Webhook-Signature' => str_repeat('00', 64),
        'X-Fal-Webhook-Timestamp' => (string) time(),
        'X-Fal-Webhook-User-Id' => 'test-user',
    ];

    (new WebhookVerifier())->verify('test body', $headers);
})->throws(WebhookVerificationException::class);

it('successfully verifies valid webhook with correct signature', function (): void {
    $keyPair = sodium_crypto_sign_keypair();
    $publicKey = sodium_crypto_sign_publickey($keyPair);
    $secretKey = sodium_crypto_sign_secretkey($keyPair);

    $body = 'test body';
    $timestamp = (string) time();
    $requestId = 'test-request-id';
    $userId = 'test-user-id';

    $message = implode("\n", [
        $requestId,
        $userId,
        $timestamp,
        hash('sha256', $body),
    ]);

    $signature = sodium_crypto_sign_detached($message, $secretKey);

    $headers = [
        'X-Fal-Webhook-Request-Id' => $requestId,
        'X-Fal-Webhook-Signature' => bin2hex($signature),
        'X-Fal-Webhook-Timestamp' => $timestamp,
        'X-Fal-Webhook-User-Id' => $userId,
    ];

    $base64UrlPublicKey = strtr(base64_encode($publicKey), '+/', '-_');
    $base64UrlPublicKey = rtrim($base64UrlPublicKey, '=');

    $mockJwks = [
        'keys' => [
            [
                'crv' => 'Ed25519',
                'kty' => 'OKP',
                'x' => $base64UrlPublicKey,
            ],
        ],
    ];

    $cache = Mockery::mock(CacheInterface::class);

    $cache->shouldReceive('get')->with('falai_jwks')->andReturn($mockJwks);

    expect((new WebhookVerifier($cache))->verify($body, $headers))->toBeTrue();
});
