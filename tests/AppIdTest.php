<?php

declare(strict_types=1);

use HosmelQ\FalAI\AppId;

it('parses standard owner/alias format', function (): void {
    $appId = AppId::parseEndpointId('fal-ai/fast-sdxl');

    expect($appId)
        ->alias->toBe('fast-sdxl')
        ->namespace->toBeNull()
        ->owner->toBe('fal-ai')
        ->path->toBeNull();
});

it('parses owner/alias with path', function (): void {
    $appId = AppId::parseEndpointId('fal-ai/fast-sdxl/path/to/resource');

    expect($appId)
        ->alias->toBe('fast-sdxl')
        ->namespace->toBeNull()
        ->owner->toBe('fal-ai')
        ->path->toBe('path/to/resource');
});

it('parses comfy namespaced format', function (): void {
    $appId = AppId::parseEndpointId('comfy/fal-ai/fast-sdxl');

    expect($appId)
        ->alias->toBe('fast-sdxl')
        ->namespace->toBe('comfy')
        ->owner->toBe('fal-ai')
        ->path->toBeNull();
});

it('parses workflows namespaced format', function (): void {
    $appId = AppId::parseEndpointId('workflows/fal-ai/fast-sdxl');

    expect($appId)
        ->alias->toBe('fast-sdxl')
        ->namespace->toBe('workflows')
        ->owner->toBe('fal-ai')
        ->path->toBeNull();
});

it('parses namespaced format with path', function (): void {
    $appId = AppId::parseEndpointId('comfy/fal-ai/fast-sdxl/path/to/resource');

    expect($appId)
        ->alias->toBe('fast-sdxl')
        ->namespace->toBe('comfy')
        ->owner->toBe('fal-ai')
        ->path->toBe('path/to/resource');
});

it('parses legacy numeric-owner format', function (): void {
    $appId = AppId::parseEndpointId('110602490-my-endpoint');

    expect($appId)
        ->alias->toBe('my-endpoint')
        ->namespace->toBeNull()
        ->owner->toBe('110602490')
        ->path->toBeNull();
});

it('parses legacy format with hyphenated alias', function (): void {
    $appId = AppId::parseEndpointId('110602490-fast-lightning-sdxl');

    expect($appId)
        ->alias->toBe('fast-lightning-sdxl')
        ->namespace->toBeNull()
        ->owner->toBe('110602490')
        ->path->toBeNull();
});

it('parses standard format with nested paths', function (): void {
    $appId = AppId::parseEndpointId('fal-ai/fast-sdxl/very/deep/path/structure');

    expect($appId)
        ->alias->toBe('fast-sdxl')
        ->namespace->toBeNull()
        ->owner->toBe('fal-ai')
        ->path->toBe('very/deep/path/structure');
});

it('parses namespaced format with nested paths', function (): void {
    $appId = AppId::parseEndpointId('comfy/fal-ai/fast-sdxl/very/deep/path/structure');

    expect($appId)
        ->alias->toBe('fast-sdxl')
        ->namespace->toBe('comfy')
        ->owner->toBe('fal-ai')
        ->path->toBe('very/deep/path/structure');
});

it('throws exception for empty app id', function (): void {
    AppId::parseEndpointId('');
})->throws(InvalidArgumentException::class, 'Invalid app id: . Must be in the format <appOwner>/<appId>');

it('throws exception for single part without slash', function (): void {
    AppId::parseEndpointId('singlepart');
})->throws(InvalidArgumentException::class, 'Invalid app id: singlepart. Must be in the format <appOwner>/<appId>');

it('throws exception for invalid format without numeric owner', function (): void {
    AppId::parseEndpointId('just-a-string');
})->throws(InvalidArgumentException::class, 'Invalid app id: just-a-string. Must be in the format <appOwner>/<appId>');
