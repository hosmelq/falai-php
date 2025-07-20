<?php

declare(strict_types=1);

namespace HosmelQ\FalAI;

class FalQueueConnector extends BaseFalConnector
{
    /**
     * {@inheritDoc}
     */
    public function resolveBaseUrl(): string
    {
        return self::QUEUE_URL_FORMAT;
    }
}
