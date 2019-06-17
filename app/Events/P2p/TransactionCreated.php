<?php

namespace RZP\Events\P2p;

use App;
use RZP\Models\P2p\Transaction\Entity;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use RZP\Models\P2p\Base\Libraries\Context;

class TransactionCreated extends Event implements ShouldQueue
{
    use SerializesModels;

    public function getName()
    {
        return 'customer.transaction.created';
    }

    public function getWebhookPaylaod()
    {
        return;
    }

    public function getNotificationPayload()
    {
        $entity = $this->getEntity();

        if ($entity->isPendingCollect() === true)
        {
            $payeeName    = strtoupper($entity->payee->getBeneficiaryName());
            $amount       = amount_format_IN($entity->getAmount());
            $appName      = $entity->device->getAppFullName();

            return [
                'receiver' => $entity->device->getContact(),
                'source'   => "api.{$this->context->getMode()}.p2p",
                // The template for invoice & payment_link is same, we are continuing to use the same for now
                'template' => 'sms.p2p',
                'params'   => [
                    'content' => "Hello, $payeeName has requested money on your $appName app." .
                        "On approving, Rs. $amount will be debited from your account.",
                ],
            ];
        }
    }
}
