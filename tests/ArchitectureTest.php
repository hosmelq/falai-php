<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security()->ignoring('assert');

arch('annotations')
    ->expect('HosmelQ\FalAI')
    ->toHaveMethodsDocumented()
    ->toHavePropertiesDocumented();

arch('strict types')
    ->expect('HosmelQ\FalAI')
    ->toUseStrictTypes();
