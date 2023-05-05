<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use Carbon\Carbon;
use Monolog\Logger;
use RZP\Models\Base;
use RZP\Services\Stork;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Illuminate\Cache\RedisStore;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;
use RZP\Models\Dispute;

class Freshdesk extends Base\Core
{
    /** @var $cache RedisStore */
    protected $cache;

    /**
     * @var Entity
     */
    protected $entity;

    protected $batchId;

    public function __construct($entity, $batchId = null)
    {
        parent::__construct();

        $this->entity = $entity;

        $this->batchId = $batchId;

        $this->cache = $this->app['cache'];
    }

    public function notify(array $aggregatedData, array &$output = null)
    {
        // determine request type to select email template later
        $isCardNetworkRequest = (isset($this->batchId) === true);
        foreach ($aggregatedData as $merchantId => $merchantData)
        {
            if ($this->shouldSkipNotificationForBatch($merchantId) === true)
            {
                $this->trace->info(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_SKIPPED, [
                    'batch_id'    => $this->batchId,
                    'merchant_id' => $merchantId,
                ]);

                continue;
            }
            try
            {
                $fdTicketId = $this->notifySingle($merchantData, $merchantId, $isCardNetworkRequest);

                if (isset($output) === true)
                {
                    $this->setMerchantOutputRows($fdTicketId, $output[$merchantId]);
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Logger::ERROR, TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_FRESHDESK_REQUEST_FAILED, [
                    'entity_id'     => $this->batchId ?? $this->entity->getId(),
                    'merchant_id'   => $merchantId,
                    'merchant_data' => $merchantData,
                ]);

                foreach ($output[$merchantId] as &$merchantOutputRow)
                {
                    $merchantOutputRow[Constants::OUTPUT_KEY_ERROR] = $e->getMessage();
                }
            }
        }
    }

    protected function sendNotificationForMobileSignup($merchant, $merchantData, $isCardNetworkRequest = false)
    {
        try
        {
            $requestParams = [
                'type'            => 'Service request',
                'priority'        => 3,
                'tags'            => ['bulk_fraud_email'],
                'groupId'        => $this->getGroupId($merchantData[0][Constants::MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION]),
            ];

            $fdTicket = (new Merchant\RiskMobileSignupHelper())->createFdTicket($merchant,
                                                                                null,
                                                                                $this->getEmailSubject($merchant, $isCardNetworkRequest),
                                                                                $merchantData,
                                                                                $requestParams,
                                                                                $this->renderBody($merchantData, $isCardNetworkRequest));

            $supportTicketLink = (new Merchant\RiskMobileSignupHelper())->getSupportTicketLink($fdTicket, $merchant);

            $params = [
                'supportTicketLink' =>  $supportTicketLink,
                'merchantName'      =>  $merchant->getName(),
            ];

            $contactNo = $merchant->merchantDetail->getContactMobile();

            $this->app['raven']->sendSms([
                                             'receiver' => $contactNo,
                                             'template' => Constants::SMS_TEMPLATE,
                                             'source'   => Merchant\Constants::SMS_SOURCE,
                                             'params'   => $params,
                                         ]);

            (new Stork)->sendWhatsappMessage(
                $this->mode,
                Constants::WHATSAPP_TEMPLATE,
                $contactNo, [
                    'ownerId'       => $merchant->getId(),
                    'ownerType'     => 'merchant',
                    'template_name' => Constants::WHATSAPP_TEMPLATE_NAME,
                    'params'        => $params
                ]
            );

            return (int) $fdTicket['ticket_id'];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BULK_FRAUD_NOTIFY_MOBILE_SIGNUP_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]);

            return (int) $fdTicket['ticket_id'] ?? null;
        }
    }

    public function notifySingle(array $merchantData, string $merchantId,  bool $isCardNetworkRequest = false)
    {
        $redisKey = sprintf(Constants::REDIS_KEY_FMT, Carbon::now(Timezone::IST)->format("d_m_Y"), $merchantId);

        $notifyCount = $this->validateLastNotified($redisKey);

        /** @var Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $fdOutboundEmailRequest = $this->getFdRequestPayload($merchant, $merchantData, $isCardNetworkRequest);

        $this->trace->debug(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_FRESHDESK_REQUEST, [
            'entity_id'       => $this->batchId ?? $this->entity->getId(),
            'request_payload' => $fdOutboundEmailRequest,
            'isCardNetworkRequest' => $isCardNetworkRequest
        ]);

        if (Merchant\RiskMobileSignupHelper::isEligibleForMobileSignUp($merchant) === true)
        {
            $fdTicketId = $this->sendNotificationForMobileSignup($merchant, $merchantData, $isCardNetworkRequest);
        }
        else
        {
            $response = $this->app['freshdesk_client']->sendOutboundEmail($fdOutboundEmailRequest, FreshdeskConstants::URLIND);

            $this->trace->debug(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_FRESHDESK_RESPONSE, [
                'entity_id' => $this->batchId ?? $this->entity->getId(),
                'response'  => $response,
            ]);

            $fdTicketId = $response['id'] ?? null;
        }


        $this->cache->set($redisKey, $notifyCount + 1, Constants::REDIS_KEY_TTL);

        return $fdTicketId;
    }

    private function renderBody(array $merchantData,  bool $isCardNetworkRequest): string
    {
        return ($isCardNetworkRequest === false)
            ? \View::make('merchant.fraud.bulk_notification')->with(['merchantDataTable' => $merchantData])->render()
            : \View::make('merchant.fraud.bulk_notification_card_network')->with(['merchantDataTable' => $merchantData])->render();
    }

    private function validateLastNotified(string $redisKey): int
    {
        $notifyCount = (int) $this->cache->get($redisKey);

        if ($notifyCount > Constants::MAX_NOTIFY_COUNT_PER_DAY_PER_MERCHANT - 1)
        {
            $message = sprintf("Merchant was already notified %d times. Can not notify more than %d times in 24 hours. Please try again later.", $notifyCount, Constants::MAX_NOTIFY_COUNT_PER_DAY_PER_MERCHANT);

            throw new \Exception($message);
        }

        return $notifyCount;
    }

    private function getEmailIds(Merchant\Entity $merchant): array
    {
        $emailIds = (new Merchant\Email\Service)->fetchEmailByMerchantIdsAndTypes([$merchant->getId()], [Merchant\Email\Type::CHARGEBACK]);

        if (isset($emailIds[$merchant->getId()][Merchant\Email\Type::CHARGEBACK]) === true)
        {
            $emailIds = $emailIds[$merchant->getId()][Merchant\Email\Type::CHARGEBACK];
        }
        else
        {
            $emailIds = [$merchant->getEmail()];
        }

        $emailIds = array_unique($emailIds);

        if (count($emailIds) < 1)
        {
            $message = sprintf("No email_id found for merchant - %s", $merchant->getId());

            throw new \Exception($message);
        }

        return $emailIds;
    }

    private function getGroupId($source): ?int
    {
        $groupId = null;

        if ($source === Constants::SOURCE_BANK)
        {
            $groupId = (int) $this->app['config']->get('applications.freshdesk')['group_ids']['rzpind']['merchant_risk_transaction'];
        }
        else if ($source === Constants::SOURCE_CYBERCELL)
        {
            $groupId = (int) $this->app['config']->get('applications.freshdesk')['group_ids']['rzpind']['byers_risk'];
        }

        return $groupId;
    }

    private function setMerchantOutputRows($fdTicketId, array &$merchantOutput)
    {
        if (is_null($fdTicketId) === false and isset($merchantOutput) === true)
        {
            foreach ($merchantOutput as &$merchantOutputRow)
            {
                $merchantOutputRow[Constants::OUTPUT_KEY_FD_TICKET_ID] = $fdTicketId;
            }
        }
    }

    protected function getEmailSubject($merchant, $isCardNetworkRequest): string
    {
        return ($isCardNetworkRequest === false)
            ? sprintf(Constants::FRESHDESK_EMAIL_SUBJECT_DEFAULT, $merchant->getName(), $merchant->getId(), Carbon::now(Timezone::IST)->format('d/m/Y'))
            : sprintf(Constants::FRESHDESK_EMAIL_SUBJECT_CARD_NETWORK, $merchant->getId(), $merchant->getName());
    }

    private function getFdRequestPayload(Merchant\Entity $merchant, array $merchantData, bool $isCardNetworkRequest = false): array
    {
        $mailSubject = $this->getEmailSubject($merchant, $isCardNetworkRequest);

        $mailBody = $this->renderBody($merchantData, $isCardNetworkRequest);

        $emailIds = $this->getEmailIdsWithSalesPOC($merchant);

        $primaryEmailId = array_shift($emailIds);

        $groupId = $this->getGroupId($merchantData[0][Constants::MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION]);

        $emailConfigId = (int) $this->app['config']->get('applications.freshdesk')['email_config_ids']['rzpind']['risk_notification'];

        $fdOutboundEmailRequest = [
            'subject'         => $mailSubject,
            'description'     => $mailBody,
            'status'          => 6,
            'type'            => 'Service request',
            'priority'        => 3,
            'email'           => $primaryEmailId,
            'tags'            => ['bulk_fraud_email'],
            'group_id'        => $groupId,
            'email_config_id' => $emailConfigId,
            'custom_fields'   => [
                'cf_ticket_queue' => 'Merchant',
                'cf_merchant_id'  => $merchant->getId(),
                'cf_category'     => 'Risk Report_Merchant',
                'cf_subcategory'  => 'Fraud alerts',
                'cf_product'      => 'Payment Gateway',
            ],
        ];

        if (empty($emailIds) === false)
        {
            $fdOutboundEmailRequest['cc_emails'] = $emailIds;
        }

        return $fdOutboundEmailRequest;
    }

    public function getEmailIdsWithSalesPOC($merchant): array
    {
        $emailIds = $this->getEmailIds($merchant);

        return (new Dispute\Service)->addSalesPOCToCCEmails($merchant->getId(),$emailIds);
    }

    protected function shouldSkipNotificationForBatch(string $merchantId): bool
    {
        $redisKey = $this->getSkipNotificationForBatchRediskKey();

        return $this->app['redis']->sismember($redisKey, $merchantId) === 1;
    }

    public function getSkipNotificationForBatchRediskKey(): string
    {
        $id = $this->app['request']->getTaskId();

        if ($this->batchId !== null)
        {
            $id = $this->batchId;
        }

        return sprintf(Constants::BULK_FRAUD_NOTIFICATION_DISABLE_MID_SET, $id);
    }
}
