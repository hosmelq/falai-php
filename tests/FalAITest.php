<?php

declare(strict_types=1);

use HosmelQ\FalAI\BaseFalConnector;
use HosmelQ\FalAI\FalAI;
use HosmelQ\FalAI\Queue\QueueResource;
use HosmelQ\FalAI\Requests\RunRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('can be created using client method', function (): void {
    $client = FalAI::client('test-token');

    expect($client)->toBeInstanceOf(FalAI::class);
});

it('returns QueueResource instance', function (): void {
    $client = FalAI::client('test-token');

    expect($client->queue())->toBeInstanceOf(QueueResource::class);
});

it('executes synchronous request successfully', function (): void {
    MockClient::global([
        RunRequest::class => MockResponse::fixture('run_success'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->run('fal-ai/fast-sdxl', [
        'prompt' => 'a cat',
    ]);

    MockClient::global()->assertSent(function (RunRequest $request): bool {
        return $request->body()->get('prompt') === 'a cat';
    });

    expect($result)
        ->images->toHaveCount(1)
        ->prompt->toBe('a cat');
});

it('uses correct connectors for different operations', function (): void {
    $client = FalAI::client('test-token');

    expect($client->getQueueConnector()->resolveBaseUrl())->toBe(BaseFalConnector::QUEUE_URL_FORMAT)
        ->and($client->getRunConnector()->resolveBaseUrl())->toBe(BaseFalConnector::RUN_URL_FORMAT);
});
