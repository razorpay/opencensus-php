<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify\Webhooks;

use App;
use Throwable;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Jobs\OneCCShopifyCreateOrder;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;

class Webhooks extends Base\Core
{

    const REFUND_CREATED = 'refund/created';
    const REUND_MUTEX_KEY = 'shopify_1cc_reund_order_mutex';
    const WEBHOOK_MUTEX_KEY = 'process_webhooks_1cc_mutex';

    const MUTEX_LOCK_TTL_SEC = 60;
    const MAX_RETRY_COUNT = 4;
    const MAX_RETRY_DELAY_MILLIS = 1 * 30 * 1000;

    protected $mutex;
    protected $monitoring;
    protected $utils;
    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = App::getFacadeRoot()['api.mutex'];

        $this->monitoring = new Shopify\Monitoring();

        $this->utils = new Shopify\Utils();

        $this->validator = new Validator();
    }

    /**
     * initial mode decides which SQS queue it gets sent on
     * the worker itself can later change the mode
     * @param array $data - complete data from the api
     * @return void
     */
    public function handle(array $data): void
    {
        $initialMode = $this->app->environment(Environment::PRODUCTION) === true ? Mode::LIVE : Mode::TEST;
        $this->app['basicauth']->setMode($initialMode);

        OneCCShopifyCreateOrder::dispatch(
          array_merge($data,
          [
            'mode' => $initialMode,
            'type' => 'webhook'
        ]));
    }

    public function processWebhookWithLock(array $data)
    {
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_WEBHOOK_MUTEX_INITIATED,
            [
                'type'  => 'mutex_initiated',
                'input' => $data,
            ]);
        $data['headers'] = $this->extractAndSetHeaders($data['headers']);
        $webhookId = $data['headers']['x-shopify-webhook-id'];
        $key = $this->getMutexKey($webhookId);

        $res = $this->mutex->acquireAndRelease(
            $key,
            function () use ($data)
            {
                return $this->processWebhook($data);
            },
            self::MUTEX_LOCK_TTL_SEC,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::MAX_RETRY_COUNT,
            self::MAX_RETRY_DELAY_MILLIS - 500,
            self::MAX_RETRY_DELAY_MILLIS
        );

      return $res;
    }

    protected function getMutexKey(string $webhookId): string
    {
        return self::REUND_MUTEX_KEY . ':' . $webhookId;
    }

    /**
     * @param array {$data} Contents from the webhook API
     * @return array Status of function. Determines SQS worker's retry logic
     */
    protected function processWebhook(array $data)
    {
        $rawContents = $data['raw_contents'];
        $headers = $data['headers'];
        $platform = $data['platform'];
        $input = $data['input'];

        if ($headers['x-shopify-topic'] !== 'refunds/create')
        {
            $this->trace->error(
              TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
              [
                'type'  => 'invalid_topic',
                'topic' => $headers['x-shopify-topic'],
              ]);
            return;
        }

        $shopId = $this->utils->stripAndReturnShopId($headers['x-shopify-shop-domain']);
        $configs = $this->getMerchantConfigs($shopId);

        if (empty($configs) === true)
        {
            $this->trace->error(
              TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
              [
                'type'  => 'configs_not_found',
              ]);
            return;
        }

        $signature = $headers['x-shopify-hmac-sha256'];
        $isSignatureValid = $this->validator->isSignatureValid($rawContents, $signature, $configs['api_secret']);

        if ($isSignatureValid === false)
        {
            return;
        }

        $client = new Shopify\Client($configs);

        $txns = $this->getTransactionsByOrder($client, $input['order_id']);
        if (empty($txns['transactions']) === true)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_FETCH_TRANSACTIONS_FAILED,
                [
                    'merchant_order_id' => $input['order_id'],
                    'txns' => $txns,
                ]);
            return;
        }

        $txn = $this->getPrepaidTransaction($txns['transactions']);
        if (empty($txn) === true)
        {
            return;
        }

        $refundFromTxn = (int)(floatval($txn['amount']) * 100);
        $refundFromWebhook = (int)(floatval($input['transactions'][0]['amount']) * 100);

        [$merchantRzpOrderId, $paymentId] = explode('|', $txn['authorization']);

        if ($merchantRzpOrderId === null or $paymentId === null)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
                [
                    'type'          => 'non_rzp_order',
                    'authorization' => $txn['authorization'],
                ]);
        }

        $payment = $this->findPaymentAndSetMode(substr($paymentId, 4));

        if ($payment === null)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
                [
                    'type'       => 'payment_not_found',
                    'payment_id' => $paymentId,
                ]);
            return;
        }

        if ($payment->hasOrder() === false)
        {
            return;
        }

        $order = $payment->getOrderAttribute();
        // set the merchant after we get the correct mode
        $this->findAndSetMerchantOrFail($configs['merchant_id']);

        $isValid = $this->validator->validateOrderAndPayment($order, $payment, $this->merchant, $merchantRzpOrderId);
        if ($isValid === false)
        {
            return;
        }

        $paymentAmount = $payment->getAmount();

        if ($paymentAmount > $refundFromWebhook)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
                [
                    'type'           => 'partial_refund_sent',
                    'order_id'       => $order->getPublicId(),
                    'payment_id'     => $payment->getPublicId(),
                    'refund_txn'     => $refundFromTxn,
                    'refund_webhook' => $refundFromWebhook,
                    'payment_amount' => $paymentAmount,
                ]);
            return;
        }

        try
        {
            $res = (new Payment\Service)->refund($paymentId, ['amount' => $paymentAmount]);
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_SUCCESS,
                [
                    'refund_txn'     => $refundFromTxn,
                    'refund_webhook' => $refundFromWebhook,
                    'payment_amount' => $payment->getAmount(),
                    'result'         => $res,
                ]);
        }
        catch (BadRequestException $e)
        {
            $error = $e->getError();
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_FAILED,
                [
                  'type'           => $error['internal_error_code'],
                  'order_id'       => $order->getPublicId(),
                  'payment_id'     => $paymentId,
                  'refund_txn'     => $refundFromTxn,
                  'refund_webhook' => $refundFromWebhook,
                  'payment_amount' => $paymentAmount,
              ]);
              return;
        }
    }

    protected function getTransactionsByOrder($client, string $merchantOrderId)
    {
        try {
          $txns = $client->getTransactionsByOrder($merchantOrderId);
          return json_decode($txns, true);

        } catch (\Throwable $e) {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_FETCH_TRANSACTIONS_FAILED,
                [
                    'type'  => 'api_error',
                    'error' => $e->getMessage(),
                ]);
            throw $e;
        }
    }

    protected function getMerchantConfigs(string $shopId): array
    {
        return (new AuthConfig\Core())->getShopify1ccConfigByShopId($shopId);
    }

    protected function findAndSetMerchantOrFail(string $merchantId): void
    {
        $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        $this->app['basicauth']->setMerchant($this->merchant);
    }

    protected function extractAndSetHeaders(array $headers): array
    {
        $data = [
            'x-shopify-api-version' => $headers['x-shopify-api-version'][0],
            'x-shopify-hmac-sha256' => $headers['x-shopify-hmac-sha256'][0],
            'x-shopify-shop-domain' => $headers['x-shopify-shop-domain'][0], // includes .myshopify.com
            'x-shopify-topic'       => $headers['x-shopify-topic'][0],
            'x-shopify-webhook-id'  => $headers['x-shopify-webhook-id'][0],
        ];

        if (empty($headers['x-shopify-order-id']) === false)
        {
            $data['x-shopify-order-id'] = $headers['x-shopify-order-id'][0];
        }

        if (empty($headers['x-shopify-test']) === false)
        {
            $data['x-shopify-test'] = $headers['x-shopify-test'][0];
        }

        return $data;
    }

    // There will be only 1 such transaction
    protected function getPrepaidTransaction(array $txns): array
    {
        $txn = [];
        for ($i = 0; $i < count($txns); $i++)
        {
            if ($txns[$i]['status'] === 'success' and $txns[$i]['kind'] === 'sale')
            {
                $txn = $txns[$i];
                break;
            }
        }
        return $txn;
    }

    /**
     * @param string $paymentId - stripped payment id
     * @return null|Payment\Entity
     */
    protected function findPaymentAndSetMode(string $paymentId)
    {
        $mode = $this->app['repo']->determineLiveOrTestModeForEntity($paymentId, 'payment');
        if ($mode === null)
        {
            return null;
        }

        $this->app['basicauth']->setModeAndDbConnection($mode);
        $this->mode = $mode;
        return $this->repo->payment->find($paymentId);
    }
}
