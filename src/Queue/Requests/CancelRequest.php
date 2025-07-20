<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Requests;

use HosmelQ\FalAI\Queue\EndpointBuilder;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class CancelRequest extends Request
{
    /**
     * {@inheritDoc}
     */
    protected Method $method = Method::PUT;

    /**
     * Create a new CancelRequest instance.
     */
    public function __construct(
        protected readonly string $endpointId,
        protected readonly string $requestId,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function resolveEndpoint(): string
    {
        return EndpointBuilder::buildCancelUrl($this->endpointId, $this->requestId);
    }
}
