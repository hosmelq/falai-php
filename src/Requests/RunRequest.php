<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Requests;

use JsonException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class RunRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * {@inheritDoc}
     */
    protected Method $method = Method::POST;

    /**
     * Create a new RunRequest instance.
     *
     * @param array<string, mixed> $input
     */
    public function __construct(
        protected readonly string $endpointId,
        protected readonly array $input,
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
        return $this->endpointId;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->input;
    }
}
