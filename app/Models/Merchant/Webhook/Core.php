<?php

namespace RZP\Models\Merchant\Webhook;

use Mail;

use RZP\Models;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Base\RuntimeManager;
use RZP\Models\Feature\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Event\Entity as EventEntity;
use RZP\Mail\Merchant\Webhook as WebhookMail;

class Core extends Base\Core
{
    public function createWebhook(Merchant\Entity $merchant, array $input)
    {
        $entityId = isset($input[Entity::ENTITY_ID]) ? $input[Entity::ENTITY_ID] : null;

        if ((isset($input[Entity::ENTITY_TYPE]) === true) and  ($input[Entity::ENTITY_TYPE] === Entity::APPLICATION))
        {
            (new Validator)->validatePartnerWithWebhooksAccess($merchant);
        }

        $webhooks = $this->getWebhooksWithEntityId($merchant, $entityId);

        if ($webhooks->count() !== 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Webhook already created.');
        }

        $webhook = new Entity;

        // Association must happen before build() because the same is used in validations.
        $webhook->merchant()->associate($merchant);
        $webhook->build($input);

        $this->repo->saveOrFail($webhook);

        // Copy to Stork
        $this->saveWebhookToStork($merchant, $webhook);

        return $webhook;
    }

    public function editWebhook(Merchant\Entity $merchant, string $webhookId, array $input)
    {
        $webhook = $this->repo->webhook->findByIdAndMerchant($webhookId, $merchant);

        $webhook->edit($input);

        $this->repo->saveOrFail($webhook);

        // Copy to Stork
        $this->saveWebhookToStork($merchant, $webhook);

        return $webhook;
    }

    public function fetchApplicableWebhookEvents(Merchant\Entity $merchant)
    {
        return array_keys(Event::filterForPublicApi($merchant));
    }

    public function getWebhooks(Merchant\Entity $merchant)
    {
        return $this->repo->webhook->fetch([], $merchant->getId());
    }

    public function getWebhooksWithEntityId(Merchant\Entity $merchant, string $entityId = null)
    {
        return $this->repo->webhook->findMultipleByMerchantAndEntityId($merchant, $entityId);
    }

    public function prepareAndDispatchWebhook(Merchant\Entity $merchant, string $event, array $input)
    {
        $payloads = $input['payloads'] ?? [$input['payload']];

        $signedAccountId = Merchant\Account\Entity::getSignedId($merchant->getId());

        foreach ($payloads as $payload)
        {
            $eventAttrs = [
                EventEntity::EVENT      => $event,
                EventEntity::ACCOUNT_ID => $signedAccountId,
                EventEntity::CONTAINS   => array_keys($payload),
                EventEntity::CREATED_AT => Carbon::now()->getTimestamp(),
            ];
            $event = new EventEntity($eventAttrs);
            $event->setPayload($payload);
            $event->merchant()->associate($merchant);

            (new Stork)->processEventSafe($event, $this->mode);
        }
    }

    /**
     * Reads webhooks from api for given after-id, limit and writes to stork.
     * @param  array  $input - Optionally should contain after-id, limit parameters.
     * @return array         - List of webhook ids written into stork.
     */
    public function webhookStorkMigrate(array $input): array
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(1000);

        // Ref: http://support.ecisolutions.com/doc-ddms/help/reportsmenu/ascii_sort_order_chart.htm
        $afterId = $input['after_id'] ?? ' ';
        $limit = $input['limit'] ?? 100;

        $webhooks = Entity::where(Entity::ID, '>=', $afterId)
                            ->orderBy(Entity::ID)
                            ->take($limit)
                            ->get();

        $stork = new Stork;
        $successfulIds = [];
        $failedIds = [];

        foreach ($webhooks as $webhook)
        {
            try
            {
                $stork->upsert($webhook);
                $successfulIds[] = $webhook->getId();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e);
                $failedIds[] = $webhook->getId();
            }
        }

        $summary = [
            'last_successful_id' => end($successfulIds),
            'failed_ids'         => $failedIds,
        ];

        $this->trace->info(TraceCode::STORK_WEBHOOK_MIGRATE_SUMMARY, $summary);

        return $summary;
    }

    /**
     * Reads webhooks from api and stork, attempts to reconcile.
     * For now: Helps with updating missing secret in api records.
     *
     * Sample payload-
     * {
     *   "dry_run": false,
     *   "webhook_ids": [
     *     "Ad3K7rDLkTWbJK",
     *     "EvGK2kr7jqlvPo"
     *   ]
     * }
     *
     * @param  array  $input - Should contain webhook_ids to reconcile, dry_run.
     * @return array         - List of webhooks that (will be/have been) reconciled.
     */
    public function webhookStorkRecon(array $input): array
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(1000);

        $dryRun = $input["dry_run"] ?? false;
        $webhookIDs = $input["webhook_ids"];

        $idsWithMismatch = [];

        $apiWebhooks = $this->repo->webhook->findMultipleByIds($webhookIDs);
        $stork = new Stork;

        // Fetch stork webhook entries for all valid webhooks queried.
        foreach ($apiWebhooks as $index => $apiWebhook)
        {
            try
            {
                $fetchedWebhooks = $stork->listWithSecret($apiWebhook);
                foreach ($fetchedWebhooks['webhooks'] as $fw)
                {
                    // Stork might have multiple webhooks per merchant/service.
                    // IDs should match for webhooks created via API
                    if ($fw['id'] === $apiWebhook[Entity::ID])
                    {
                        $apiWebhooks[$index]['stork_data'] = $fw;
                        break;
                    }
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e);
            }
        }

        // Build a set of all webhooks with secret mismatch
        foreach ($apiWebhooks as $apiWebhook)
        {
            $mismatch = ($apiWebhook[Entity::SECRET] ?? null) !== ($apiWebhook['stork_data']['secret'] ?? null);

            // Maintain a list of webhooks to update (webhooks with secret mismatch).
            if ($mismatch === true)
            {
                $idsWithMismatch[] = $apiWebhook[Entity::ID];
            }
        }

        // Update only if dry_run is false, else simply return webhook info.
        if ($dryRun === false)
        {
            foreach ($apiWebhooks as $apiWebhook)
            {
                if (in_array($apiWebhook[Entity::ID], $idsWithMismatch) === true)
                {
                    $updateInput[Entity::SECRET] = $apiWebhook['stork_data']['secret'];
                    unset($apiWebhook['stork_data']);

                    $apiWebhook->edit($updateInput);
                    $this->repo->saveOrFail($apiWebhook);
                }
            }
        }

        return $idsWithMismatch;
    }

    /**
     * Read webhook from id
     * Then deactivate the webhook and sends a deactivation email to merchant.
     * @param string $id
     */
    public function webhookDeactivate(string $id)
    {
        $webhook = $this->repo->webhook->findOrFailPublic($id);

        $this->disableWebhook($webhook);

        $options = [
            'mode'         => $this->mode,
            'type'         => 'deactivate',
        ];

        $this->sendMail($webhook, $options);
    }

    public function sendMail(Entity $webhook, array $options)
    {
        $merchant = $webhook->merchant;

        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $merchant = $merchant->toArrayPublic();

        $webhook = $webhook->toArrayPublic();

        $webhookMail = new WebhookMail($webhook, $merchant, $options);

        Mail::queue($webhookMail);
    }

    protected function disableWebhook(Entity $webhook)
    {
        $webhook->deactivate();

        $this->repo->saveOrFail($webhook);

        // Copy to Stork
        $this->saveWebhookToStork($webhook->merchant, $webhook);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param string          $product
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function createToStork(Merchant\Entity $merchant,
                                     array $input,
                                     string $product)
    {
        $stork = new Stork($product);

        try
        {
            $storkResponse = $stork->fetchMultiple($this->merchant);
        }
        catch (Exception\ServerErrorException $e)
        {
            $this->trace->traceException($e);
            throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR_STORK_FAILURE);
        }

        if ((Product::isProductBanking($product)) and sizeof($storkResponse) > 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_STORK_WEBHOOK_ALREADY_CREATED);
        }

        $webhook = new Entity;
        $webhook->merchant()->associate($merchant);
        $webhook->build($input);

        try
        {
            $response = $stork->create($webhook);
            $webhook->setId($response['webhook']['id']);
        }
        catch (Exception\ServerErrorException $e)
        {
            $this->trace->traceException($e);
            throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR_STORK_FAILURE);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_WEBHOOK_VALIDATION_FAILED);
        }

        return  $webhook;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string          $webhookId
     * @param array           $input
     * @param string          $product
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function updateToStork(Merchant\Entity $merchant,
                                  string $webhookId,
                                  array $input,
                                  $product = null)
    {
        $stork = new Stork($product);
        try
        {
            $storkResponse = $stork->fetch($this->merchant, $webhookId);
        }
        catch (Exception\ServerErrorException $e)
        {
            $this->trace->traceException($e);
            throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR_STORK_FAILURE);
        }

        if (sizeof($storkResponse) < 1)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_STORK_WEBHOOK_NOT_FOUND);
        }

        try
        {
            $webhook = new Entity;
            $webhook->merchant()->associate($merchant);
            $webhook->edit($input);
            $webhook->setId($webhookId);

            $stork->update($webhook);
        }
        catch (Exception\ServerErrorException $e)
        {
            $this->trace->traceException($e);
            throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR_STORK_FAILURE);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_WEBHOOK_VALIDATION_FAILED);
        }

        return  $webhook;
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @param string          $product
     *
     * @return Base\PublicCollection
     * @throws Exception\BadRequestException
     */
    public function fetchFromStork(Merchant\Entity $merchant, $product = null)
    {
        $webhookCollection = (new Base\PublicCollection);

        try
        {
            $stork = new Stork($product);

            $storkResponse = $stork->fetchMultiple($this->merchant);

            if (sizeof($storkResponse) < 1)
            {
                return $webhookCollection;
            }

            $webhookData = $stork->deserializeStorkWebhook($storkResponse['webhooks'][0]);

            $webhook = new Entity;
            $webhook->merchant()->associate($merchant);
            $webhook->edit($webhookData);
            $webhook->setId($storkResponse['webhooks'][0]['id']);
        }
        catch (Exception\ServerErrorException $e)
        {
            $this->trace->traceException($e);
            throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR_STORK_FAILURE);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_WEBHOOK_VALIDATION_FAILED);
        }

        return  $webhookCollection->push($webhook);
    }

    protected function saveWebhookToStork(Merchant\Entity $merchant,
                                          Entity $webhook)
    {
        try
        {
            $this->upsertToStork($merchant, $webhook, Product::PRIMARY);

            // Copy webhook setting for X if business banking is ON for Merchant
            // Don't write to X service on Stork if feature is ON
            // This will go away after complete rollout of webhook separation for X and PG
            if (!$merchant->isFeatureEnabled(Constants::BANKING_STORK_MIGRATION) and
                $merchant->isBusinessBankingEnabled() === true)
            {
                $this->upsertToStork($merchant, $webhook, Product::BANKING);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::STORK_SAVE_SETTING_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
    }

    /**
     * This is temp function for Webhook Separation rollout
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $webhook
     * @param string          $product
     *
     * @return void
     * @throws Exception\ServerErrorException
     */
    public function upsertToStork(Merchant\Entity $merchant,
                                  Entity $webhook,
                                  string $product): void
    {
        $stork = new Stork($product);

        // Stork migration is already done for PRIMARY
        // API DB and PRIMARY Stork PG setting Ids are mapped one to one
        if ($product === Product::PRIMARY)
        {
            // update with db Id
            $stork->update($webhook);
        }
        // Upsert for X
        else if ($product === Product::BANKING)
        {
            // $webhook is being mutated assuming it is local copy.
            $webhook = clone $webhook;

            // This will return multiple webhooks but currently only one is supported for X
            $storkResponse = $stork->fetchMultiple($merchant);

            if (sizeof($storkResponse) < 1)
            {
                // Create new setting on Stork for X
                $webhook->setId(null);
                $stork->create($webhook);
            }
            else
            {
                // Update existing setting on Stork for X
                $webhook->setId($storkResponse['webhooks'][0]['id']);
                $stork->update($webhook);
            }
        }
    }

    /**
     * Copy API Stork setting from stork to RX setting on Stork
     * This is temp function and only used for webhook separation
     *
     * @param  array  $input - List of MIDs
     * @return array         - List of MIDs with successful and failed count
     */
    public function webhookStorkCreateBankingBulk(array $input): array
    {
        $this->trace->info(TraceCode::STORK_WEBHOOK_COPY_API_TO_RX_REQUEST, $input);

        $merchantIds = $input['merchant_ids'] ?? [];

        $successfulIds = [];
        $failedIds = [];
        $noSettingIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                /** @var Merchant\Entity $merchant */
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                if ($merchant->isBusinessBankingEnabled() === true)
                {
                    $webhook = $this->repo->webhook->findByMerchant($merchant);

                    if ($webhook === null)
                    {
                        $noSettingIds[] = $merchantId;
                        continue;
                    }

                    /** @var Entity $webhook */
                    $webhook->setId(null);
                    $res = $this->createToStorkIfNotExists($merchant, $webhook, Product::BANKING);

                    if ($res === null)
                    {
                        $noSettingIds[] = $merchantId;
                        continue;
                    }

                    $successfulIds[] = $merchantId;
                }
                else
                {
                    $noSettingIds[] = $merchantId;
                }
            }

            catch (\Throwable $e)
            {
                $this->trace->traceException($e);
                $failedIds[] = $merchantId;
            }
        }

        $summary = [
            'successful_mids' => $successfulIds,
            'failed_mids'     => $failedIds,
            'no_setting_mids' => $noSettingIds,
        ];

        $this->trace->info(TraceCode::STORK_WEBHOOK_COPY_API_TO_RX_SUMMARY, $summary);

        return $summary;
    }

    /**
     * This is temp function used for webhook separation migration
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $webhook
     * @param string          $product
     *
     * @return Entity|null
     * @throws Exception\ServerErrorException
     */
    public function createToStorkIfNotExists(Merchant\Entity $merchant,
                                             Entity $webhook,
                                             string $product)
    {
        $stork = new Stork($product);

        $storkResponse = $stork->fetchMultiple($merchant);

        if (sizeof($storkResponse) > 0)
        {
            return null;
        }

        $stork->create($webhook);

        return $webhook;
    }
}
