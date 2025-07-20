<?php

declare(strict_types=1);

use Saloon\Config;
use Saloon\Http\Faking\MockClient;

pest()
    ->beforeEach(function (): void {
        MockClient::destroyGlobal();
    })
    ->in(__DIR__);

Config::preventStrayRequests();
