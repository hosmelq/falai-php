<?php

declare(strict_types=1);

namespace HosmelQ\FalAI;

class FalRunConnector extends BaseFalConnector
{
    /**
     * {@inheritDoc}
     */
    public function resolveBaseUrl(): string
    {
        return self::RUN_URL_FORMAT;
    }
}
