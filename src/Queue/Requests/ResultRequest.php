<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue\Requests;

use HosmelQ\FalAI\Queue\EndpointBuilder;
use JsonException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class ResultRequest extends Request
{
    /**
     * {@inheritDoc}
     */
    protected Method $method = Method::GET;

    /**
     * Create a new ResultRequest instance.
     */
    public function __construct(
        protected readonly string $endpointId,
        protected readonly string $requestId,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function createDtoFromResponse(Response $response): array
    {
        /** @var array<string, mixed> $data */
        $data = $response->json();

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveEndpoint(): string
    {
        return EndpointBuilder::buildResponseUrl($this->endpointId, $this->requestId);
    }
}
