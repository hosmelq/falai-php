<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue;

use HosmelQ\FalAI\AppId;

class EndpointBuilder
{
    /**
     * Build the submit URL for a model.
     */
    public static function buildBaseUrl(string $endpointId): string
    {
        $appId = AppId::parseEndpointId($endpointId);
        $prefix = is_null($appId->namespace) ? '' : $appId->namespace.'/';

        return sprintf('/%s%s/%s', $prefix, $appId->owner, $appId->alias);
    }

    /**
     * Build the cancel URL for a request.
     */
    public static function buildCancelUrl(string $endpointId, string $requestId): string
    {
        return sprintf('%s/requests/%s/cancel', self::buildBaseUrl($endpointId), $requestId);
    }

    /**
     * Build the response URL for a request.
     */
    public static function buildResponseUrl(string $endpointId, string $requestId): string
    {
        return sprintf('%s/requests/%s', self::buildBaseUrl($endpointId), $requestId);
    }

    /**
     * Build the status stream URL for a request.
     */
    public static function buildStatusStreamUrl(string $endpointId, string $requestId): string
    {
        return sprintf('%s/stream', self::buildStatusUrl($endpointId, $requestId));
    }

    /**
     * Build the status URL for a request.
     */
    public static function buildStatusUrl(string $endpointId, string $requestId): string
    {
        return sprintf('%s/requests/%s/status', self::buildBaseUrl($endpointId), $requestId);
    }
}
