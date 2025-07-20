<?php

declare(strict_types=1);

use HosmelQ\FalAI\Queue\EndpointBuilder;

it('builds base URL for endpoint', function (): void {
    $url = EndpointBuilder::buildBaseUrl('fal-ai/fast-sdxl');

    expect($url)->toBe('/fal-ai/fast-sdxl');
});

it('builds base URL for namespaced endpoint', function (): void {
    $url = EndpointBuilder::buildBaseUrl('comfy/fal-ai/fast-sdxl');

    expect($url)->toBe('/comfy/fal-ai/fast-sdxl');
});

it('builds cancel URL', function (): void {
    $url = EndpointBuilder::buildCancelUrl('fal-ai/fast-sdxl', 'test-request-id');

    expect($url)->toBe('/fal-ai/fast-sdxl/requests/test-request-id/cancel');
});

it('builds response URL', function (): void {
    $url = EndpointBuilder::buildResponseUrl('fal-ai/fast-sdxl', 'test-request-id');

    expect($url)->toBe('/fal-ai/fast-sdxl/requests/test-request-id');
});

it('builds status URL', function (): void {
    $url = EndpointBuilder::buildStatusUrl('fal-ai/fast-sdxl', 'test-request-id');

    expect($url)->toBe('/fal-ai/fast-sdxl/requests/test-request-id/status');
});

it('builds status stream URL', function (): void {
    $url = EndpointBuilder::buildStatusStreamUrl('fal-ai/fast-sdxl', 'test-request-id');

    expect($url)->toBe('/fal-ai/fast-sdxl/requests/test-request-id/status/stream');
});
