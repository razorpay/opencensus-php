<?php

namespace RZP\Events\P2p;

use App;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Client;
use RZP\Models\P2p\Transaction\Entity;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;

class TransactionCreated extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'customer.transaction.created';
    }

    public function getWebhookPaylaod()
    {
        $entity = $this->getEntity();

        if ($entity->isPendingCollect() === true)
        {
            return $entity->toArrayPartner();
        }
    }

    public function getNotificationPayload()
    {
        $entity = $this->getEntity();

        if ($entity->isPendingCollect() === true)
        {
            $handle = $this->context->getHandle();

            /**
             * @var $client Client\Entity
             */
            $client = $entity->device->client($handle);

            $payeeName    = strtoupper($entity->payee->getBeneficiaryName());
            $currency     = $entity->getCurrency();
            $amount       = $entity->getAmount();

            $appName        = $client->getConfigValue(Client\Config::APP_FULL_NAME);
            $sender         = $client->getConfigValue(Client\Config::SMS_SENDER);
            $smsSignature   = $client->getConfigValue(Client\Config::SMS_SIGNATURE);
            $appCollectLink = $client->getConfigValue(Client\Config::APP_COLLECT_LINK);

            $template = (empty($appCollectLink)) ? 'sms.p2p.collect' : 'sms.p2p.collect_with_link';

            $payload = [
                'receiver' => $entity->device->getFormattedContact(),
                'source'   => "api.{$this->context->getMode()}.p2p",
                'template' => $template,
                'sender'   => $sender,
                'params'   => [
                    'payee_name'        => $payeeName,
                    'app_name'          => $appName,
                    'currency'          => $currency,
                    'currency_label'    => 'Rs.',
                    'amount'            => $amount,
                    'formatted_amount'  => number_format($amount / 100, 2, '.', ''),
                    'sms_signature'     => $smsSignature,
                    'app_collect_link'  => $appCollectLink ?? '',
                ],
            ];

            $orgId = $entity->getMerchantOrgId();

            // appending orgId in stork context to be used on stork to select org specific sms gateway.
            if (empty($orgId) === false)
            {
                $payload['stork']['context']['org_id'] = $orgId;
            }

            return $payload;
        }
    }

    public function getReminderPayload()
    {
        return;
    }

    public function postHandle()
    {
        /**
         * @var $transaction Entity
         */
        $transaction = $this->getEntity();

        (new TransactionMetric($transaction, $this->original))->pushCount();
    }

    protected function setOriginal(array $original = null)
    {
        $this->original = $original;

        return $this;
    }
}
