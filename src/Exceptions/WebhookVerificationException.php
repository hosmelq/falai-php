<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Exceptions;

use InvalidArgumentException;

class WebhookVerificationException extends InvalidArgumentException
{
    /**
     * Create an exception for signature verification failure.
     */
    public static function invalidSignature(): self
    {
        return new self('Webhook signature verification failed.');
    }

    /**
     * Create an exception for invalid signature format.
     */
    public static function invalidSignatureFormat(): self
    {
        return new self('Invalid signature format.');
    }

    /**
     * Create an exception for an invalid timestamp.
     */
    public static function invalidTimestamp(string $timestamp): self
    {
        return new self(sprintf('Timestamp is outside tolerance window: %s.', $timestamp));
    }

    /**
     * Create an exception for JWKS-related errors.
     */
    public static function jwksError(string $message): self
    {
        return new self(sprintf('JWKS error: %s.', $message));
    }

    /**
     * Create an exception for missing required header.
     */
    public static function missingHeader(string $header): self
    {
        return new self(sprintf('Missing required header: %s.', $header));
    }

    /**
     * Create an exception for missing sodium extension.
     */
    public static function missingSodiumExtension(): self
    {
        return new self('Sodium extension is required for signature verification.');
    }
}
