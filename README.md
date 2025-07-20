# falai-php

The [fal.ai](https://fal.ai) client for PHP.

Built on the [Saloon PHP](https://docs.saloon.dev) library with [sse-saloon](https://github.com/hosmelq/sse-saloon) for Server-Sent Events support.

## Features

- **Queue-based processing** - Submit requests to fal.ai's queue system for asynchronous processing.
- **Synchronous execution** - Direct model execution without queueing.
- **Status streaming** - Stream status updates using Server-Sent Events.
- **Request cancellation** - Cancel queued requests when needed.

## Requirements

- PHP 8.2+
- `ext-sodium` (optional, required for webhook verification).
- PSR-16 cache implementation (optional, for improved webhook verification performance).

## Installation

```bash
composer require hosmelq/falai
```

## Configuration

Set your fal.ai API key as an environment variable:

```bash
FAL_KEY='your-api-key-here'
```

Or pass it directly to the client:

```php
$fal = FalAI::client('your-api-key-here');
```

## Basic Usage

**Queue-based execution (recommended):**

```php
<?php

use HosmelQ\FalAI\FalAI;
use HosmelQ\FalAI\Queue\Responses\QueueStatusCompleted;

$fal = FalAI::client();

$response = $fal->queue()->submit('fal-ai/fast-sdxl', [
    'prompt' => 'a sleeping cat',
]);

$status = $fal->queue()->status('fal-ai/fast-sdxl', $response->requestId);

if ($status instanceof QueueStatusCompleted) {
    $result = $fal->queue()->result('fal-ai/fast-sdxl', $response->requestId);
}
```

**Synchronous execution:**

```php
<?php

use HosmelQ\FalAI\FalAI;

$fal = FalAI::client();

$result = $fal->run('fal-ai/fast-sdxl', [
    'prompt' => 'a fluffy cat',
]);
```

## Usage

### Queue Operations

The recommended approach for most requests. The queue system provides better reliability, enables request cancellation, and handles longer-running tasks efficiently:

#### Submit Request

Submit a request to the queue for asynchronous processing:

```php
<?php

use HosmelQ\FalAI\FalAI;

$fal = FalAI::client();

$status = $fal->queue()->submit('fal-ai/fast-sdxl', [
    'prompt' => 'a black cat',
]);

echo $status->requestId;
```

#### Check Status

Check the current status of a queued request:

```php
<?php

use HosmelQ\FalAI\FalAI;
use HosmelQ\FalAI\Queue\Responses\QueueStatusCompleted;
use HosmelQ\FalAI\Queue\Responses\QueueStatusInProgress;
use HosmelQ\FalAI\Queue\Responses\QueueStatusQueued;

$fal = FalAI::client();

$status = $fal->queue()->status('fal-ai/fast-sdxl', $requestId);

if ($status instanceof QueueStatusQueued) {
    echo 'Status: IN_QUEUE';
} elseif ($status instanceof QueueStatusInProgress) {
    echo 'Status: IN_PROGRESS';
} elseif ($status instanceof QueueStatusCompleted) {
    echo 'Status: COMPLETED';
}

echo $status->jsonSerialize()['status'];
```

#### Get Results

Retrieve the final results from a completed request:

```php
<?php

use HosmelQ\FalAI\FalAI;

$fal = FalAI::client();

$result = $fal->queue()->result('fal-ai/fast-sdxl', $requestId);
```

#### Stream Status Updates

Stream status updates using Server-Sent Events:

```php
use HosmelQ\FalAI\Queue\Responses\QueueStatusCompleted;

foreach ($fal->queue()->streamStatus('fal-ai/fast-sdxl', $requestId) as $status) {
    echo 'Status: '.$status->jsonSerialize()['status']."\n";
    
    if ($status instanceof QueueStatusCompleted) {
        echo 'Task completed! Result ready.'."\n";
        break;
    }
}
```

#### Cancel Request

Cancel queued requests before they start processing:

```php
<?php

use HosmelQ\FalAI\FalAI;

$fal = FalAI::client();

$cancelled = $fal->queue()->cancel('fal-ai/fast-sdxl', $requestId);

if ($cancelled) {
    echo 'Request cancelled successfully.';
} else {
    echo 'Request could not be cancelled (may already be completed).';
}
```

#### Webhooks

Configure webhooks to receive automatic notifications when your queued requests complete. This eliminates the need to poll for status updates and provides immediate notification of task completion.

##### Setting Up Webhooks

Include a webhook URL when submitting requests to the queue:

```php
<?php

use HosmelQ\FalAI\FalAI;

$fal = FalAI::client();

$status = $fal->queue()->submit(
    endpointId: 'fal-ai/fast-sdxl',
    input: ['prompt' => 'a white cat'],
    webhookUrl: 'https://your-app.com/webhook'
);
```

When the request completes, fal.ai will send a POST request to your webhook URL with the result data. See the [webhook payload documentation](https://docs.fal.ai/model-endpoints/webhooks) for payload structure details.

##### Webhook Verification

For security, verify webhook signatures to ensure requests are authentic and originate from fal.ai. The verification process uses cryptographic signatures and timestamp validation:

```php
<?php

use HosmelQ\FalAI\Exceptions\WebhookVerificationException;
use HosmelQ\FalAI\WebhookVerifier;

$verifier = new WebhookVerifier();

$body = $request->getContent();
$headers = $request->headers->all();

try {
    $isValid = $verifier->verify($body, $headers);
    
    if ($isValid) {
        $payload = json_decode($body, true);
        
        if ($payload['status'] === 'OK') {
            echo 'Request '.$payload['request_id'].' completed successfully.';
        } elseif ($payload['status'] === 'ERROR') {
            echo 'Request failed: '.$payload['error'];
        }
    }
} catch (WebhookVerificationException $e) {
    echo 'Webhook verification failed: '.$e->getMessage();
}
```

##### Performance Optimization

For better performance with webhook verification, install a PSR-16 cache implementation:

```bash
composer require symfony/cache
```

```php
<?php

use HosmelQ\FalAI\WebhookVerifier;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

$cache = new Psr16Cache(new FilesystemAdapter());
$verifier = new WebhookVerifier($cache);
```

This caches the public keys used for signature verification, reducing the number of HTTP requests to fal.ai.

### Synchronous Execution

For direct execution without queueing, use the `run()` method. 

⚠️ **Important:** Queue-based processing is the recommended approach for most use cases as it provides better reliability and handling of longer-running requests.

⚠️ **Critical:** If the connection fails during synchronous requests, the result cannot be retrieved. See [synchronous requests documentation](https://docs.fal.ai/model-endpoints/synchronous-requests) for more details.

```php
<?php

use HosmelQ\FalAI\FalAI;

$fal = FalAI::client();

$result = $fal->run('fal-ai/fast-sdxl', [
    'prompt' => 'a cat with blue eyes',
]);

echo $result['images'][0]['url'];
```

### Error Handling

Handle API errors and exceptions that may occur during requests:

```php
<?php

use HosmelQ\FalAI\FalAI;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

$fal = FalAI::client();

try {
    $result = $fal->run('fal-ai/fast-sdxl', [
        'prompt' => 'a gray cat',
    ]);
} catch (FatalRequestException $e) {
    echo 'Fatal error: '.$e->getMessage();
} catch (RequestException $e) {
    echo 'Request error: '.$e->getMessage();
}
```

When streaming status updates:

```php
<?php

use HosmelQ\FalAI\FalAI;
use HosmelQ\SSE\SSEProtocolException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

$fal = FalAI::client();

try {
    $fal->queue()->streamStatus('fal-ai/fast-sdxl', $requestId);
} catch (FatalRequestException $e) {
    echo 'Fatal error: '.$e->getMessage();
} catch (RequestException $e) {
    echo 'Request error: '.$e->getMessage();
} catch (SSEProtocolException $e) {
    echo 'SSE protocol error: '.$e->getMessage();
}
```

### Priority and Hints

Advanced configuration options for fine-tuning queue behavior. See the [optimization documentation](https://docs.fal.ai/private-serverless-apps/optimizations/hot-app-routing) for detailed strategies:

```php
<?php

use HosmelQ\FalAI\FalAI;
use HosmelQ\FalAI\Queue\QueuePriority;

$fal = FalAI::client();

$status = $fal->queue()->submit(
    endpointId: 'black-forest-labs/FLUX.1-schnell',
    input: ['prompt' => 'a striped cat'],
    hint: 'black-forest-labs/FLUX.1-schnell',
    priority: QueuePriority::Low
);
```

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## Credits

- [Hosmel Quintana](https://github.com/hosmelq)
- [All Contributors](../../contributors)

**Built on:**
- [Saloon PHP](https://docs.saloon.dev)
- [sse-saloon](https://github.com/hosmelq/sse-saloon)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
