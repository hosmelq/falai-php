<?php

declare(strict_types=1);

namespace HosmelQ\FalAI;

use function Safe\preg_match;

use InvalidArgumentException;

class AppId
{
    /**
     * Application namespaces that require special handling.
     */
    private const APP_NAMESPACES = ['comfy', 'workflows'];

    /**
     * Create a new AppId instance.
     */
    private function __construct(
        public readonly string $owner,
        public readonly string $alias,
        public readonly ?string $path = null,
        public readonly ?string $namespace = null,
    ) {
    }

    /**
     * Create an AppId instance from an endpoint ID string.
     */
    public static function parseEndpointId(string $id): self
    {
        $parts = explode('/', self::ensureEndpointIdFormat($id));

        if (in_array($parts[0], self::APP_NAMESPACES, true)) {
            return new self(
                owner: $parts[1],
                alias: $parts[2],
                path: isset($parts[3]) ? implode('/', array_slice($parts, 3)) : null,
                namespace: $parts[0],
            );
        }

        return new self(
            owner: $parts[0],
            alias: $parts[1],
            path: isset($parts[2]) ? implode('/', array_slice($parts, 2)) : null,
            namespace: null,
        );
    }

    /**
     * Ensure the app ID is in the correct format.
     */
    private static function ensureEndpointIdFormat(string $id): string
    {
        $parts = explode('/', $id);

        if (count($parts) > 1) {
            return $id;
        }

        if (preg_match('/^(\d+)-([a-zA-Z0-9-]+)$/', $id, $matches) !== 0) {
            [, $appOwner, $appId] = $matches;

            return sprintf('%s/%s', $appOwner, $appId);
        }

        throw new InvalidArgumentException(sprintf('Invalid app id: %s. Must be in the format <appOwner>/<appId>', $id));
    }
}
