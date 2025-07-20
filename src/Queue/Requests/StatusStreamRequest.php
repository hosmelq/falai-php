<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Requests;

use HosmelQ\FalAI\Queue\EndpointBuilder;
use HosmelQ\SSE\Saloon\Traits\HasServerSentEvents;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class StatusStreamRequest extends Request
{
    use HasServerSentEvents;

    /**
     * {@inheritDoc}
     */
    protected Method $method = Method::GET;

    /**
     * Create a new StreamRequest instance.
     */
    public function __construct(
        protected readonly string $modelId,
        protected readonly string $requestId,
        protected readonly bool $withLogs = false,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function resolveEndpoint(): string
    {
        return EndpointBuilder::buildStatusStreamUrl($this->modelId, $this->requestId);
    }

    /**
     * {@inheritDoc}
     */
    protected function defaultQuery(): array
    {
        return [
            'logs' => $this->withLogs,
        ];
    }
}
