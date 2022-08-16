<?php

namespace RZP\Models\Merchant\OneClickCheckout\Webhooks;

use App;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout\Utils;
use RZP\Models\Merchant\OneClickCheckout\Shopify\Webhooks\Webhooks as ShopifyWebhooks;

class Service extends Base\Service
{
    const VALID_PLATFORM_TYPES = [
        'shopify',
    ];

    protected $monitoring;
    protected $app;

    public function __construct()
    {
        parent::__construct();
        $this->monitoring = new Utils\Monitoring();
        $this->app = App::getFacadeRoot();
    }

    public function handle(array $data)
    {
        $rawContents = $data['raw_contents'];
        $headers = $data['headers'];
        $platform = $data['platform'];
        $input = $data['input'];

        $this->trace->info(
            TraceCode::WEBHOOK_PROCESS_REQUEST_1CC,
            [
                'platform' => $platform,
                'input'    => $input,
                'headers'  => $headers,
            ]);

        $this->validatePlatform($platform);
        $this->monitoring->addTraceCount(
            Metric::WEBHOOK_PROCESS_REQUEST_1CC_COUNT,
            ['platform' => $platform]);

        switch ($platform)
        {
            case 'shopify':
                (new ShopifyWebhooks())->handle($data);
                break;

            default:
                $this->logAndThrowInvalidPlatformError();
                break;
        }
    }

    protected function validatePlatform(string $platform): void
    {
        $isValidPlatforn = in_array($platform, self::VALID_PLATFORM_TYPES);

        if ($isValidPlatforn === false)
        {
            $this->logAndThrowInvalidPlatformError();
        }
    }

    // the error is not logged by platforms (Shopify) so adding a specific error code
    // does not help. Instead we rely on the logs
    protected function logAndThrowInvalidPlatformError(): void
    {
        $this->trace->error(
            TraceCode::WEBHOOK_PROCESS_REQUEST_FAILED_1CC,
            [
                'platform' => 'invalid',
            ],
            false);

        $this->monitoring->addTraceCount(
            Metric::WEBHOOK_PROCESS_REQUEST_1CC_ERROR_COUNT,
            [
                'platform' => 'invalid',
            ],
            false);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_ERROR,
            null,
            null,
            'INVALID_PLATFORM');
    }
}
