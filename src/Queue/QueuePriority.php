<?php

declare(strict_types=1);

namespace HosmelQ\FalAI\Queue;

enum QueuePriority: string
{
    case Low = 'low';
    case Normal = 'normal';
}
