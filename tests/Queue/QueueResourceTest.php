<?php

declare(strict_types=1);

use HosmelQ\FalAI\FalAI;
use HosmelQ\FalAI\Queue\QueuePriority;
use HosmelQ\FalAI\Queue\Requests\CancelRequest;
use HosmelQ\FalAI\Queue\Requests\ResultRequest;
use HosmelQ\FalAI\Queue\Requests\StatusRequest;
use HosmelQ\FalAI\Queue\Requests\SubmitRequest;
use HosmelQ\FalAI\Queue\Responses\QueueStatusCompleted;
use HosmelQ\FalAI\Queue\Responses\QueueStatusInProgress;
use HosmelQ\FalAI\Queue\Responses\QueueStatusQueued;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('submits request', function (): void {
    MockClient::global([
        SubmitRequest::class => MockResponse::fixture('queue_submit'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->submit('fal-ai/fast-sdxl', [
        'prompt' => 'a cat',
    ]);

    MockClient::global()->assertSent(function (SubmitRequest $request): bool {
        return $request->body()->get('prompt') == 'a cat';
    });

    expect($result)->toBeInstanceOf(QueueStatusQueued::class);
});

it('submits request with hint and queue priority headers', function (): void {
    MockClient::global([
        SubmitRequest::class => MockResponse::fixture('queue_submit'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->submit(
        endpointId: 'black-forest-labs/FLUX.1-schnell',
        input: ['prompt' => 'a cat'],
        hint: 'black-forest-labs/FLUX.1-schnell',
        priority: QueuePriority::Low
    );

    MockClient::global()->assertSent(function (SubmitRequest $request): bool {
        return $request->headers()->get('X-Fal-Queue-Priority') === 'low'
            && $request->headers()->get('X-Fal-Runner-Hint') === 'black-forest-labs/FLUX.1-schnell';
    });

    expect($result)->toBeInstanceOf(QueueStatusQueued::class);
});

it('submits request with webhook URL', function (): void {
    MockClient::global([
        SubmitRequest::class => MockResponse::fixture('queue_submit'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->submit(endpointId: 'fal-ai/fast-sdxl', input: [
        'prompt' => 'a cat',
    ], webhookUrl: 'https://example.com/webhook');

    MockClient::global()->assertSent(function (SubmitRequest $request): bool {
        return $request->query()->get('fal_webhook') === 'https://example.com/webhook';
    });

    expect($result)->toBeInstanceOf(QueueStatusQueued::class);
});

it('checks status and returns queued status', function (): void {
    MockClient::global([
        StatusRequest::class => MockResponse::fixture('queue_status_queued'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->status('fal-ai/fast-sdxl', '6e8a8cf2-c2ca-4636-9071-0b3ae067edb1');

    expect($result)->toBeInstanceOf(QueueStatusQueued::class);
});

it('checks status and returns in-progress status', function (): void {
    MockClient::global([
        StatusRequest::class => MockResponse::fixture('queue_status_in_progress'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->status('fal-ai/fast-sdxl', '6e8a8cf2-c2ca-4636-9071-0b3ae067edb1');

    expect($result)->toBeInstanceOf(QueueStatusInProgress::class);
});

it('checks status and returns completed status', function (): void {
    MockClient::global([
        StatusRequest::class => MockResponse::fixture('queue_status_completed'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->status('fal-ai/fast-sdxl', '6e8a8cf2-c2ca-4636-9071-0b3ae067edb1', true);

    expect($result)->toBeInstanceOf(QueueStatusCompleted::class);
});

it('gets result from completed request', function (): void {
    MockClient::global([
        ResultRequest::class => MockResponse::fixture('queue_result'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->result('fal-ai/fast-sdxl', '6e8a8cf2-c2ca-4636-9071-0b3ae067edb1');

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('images')
        ->and($result)->toHaveKey('timings');
});

it('cancels queued request successfully', function (): void {
    MockClient::global([
        CancelRequest::class => MockResponse::fixture('queue_cancel_202'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->cancel('fal-ai/fast-sdxl', 'a13f6512-ca57-4470-897b-f9e7c0c3150d');

    expect($result)->toBeTrue();
});

it('fails to cancel already completed request', function (): void {
    MockClient::global([
        CancelRequest::class => MockResponse::fixture('queue_cancel_400'),
    ]);

    $client = FalAI::client('test-token');

    $result = $client->queue()->cancel('fal-ai/fast-sdxl', 'a13f6512-ca57-4470-897b-f9e7c0c3150d');

    expect($result)->toBeFalse();
});

it('streamStatus returns a generator', function (): void {
    $client = FalAI::client('test-token');

    $result = $client->queue()->streamStatus('fal-ai/fast-sdxl', 'test-request-id');

    expect($result)->toBeInstanceOf(Generator::class);
});
