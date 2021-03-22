<?php

namespace RZP\Models\CardMandate\CardMandateNotification;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Reminders;
use RZP\Models\CardMandate;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    public function create(Payment\Entity $payment, CardMandate\Entity $cardMandate): Entity
    {
        $cardMandateNotification = (new Entity)->build();

        $cardMandateNotification->merchant()->associate($payment->merchant);
        $cardMandateNotification->cardMandate()->associate($cardMandate);
        $cardMandateNotification->payment()->associate($payment);

        $mandateHqResponse = $this->app->mandateHQ->createPreDebitNotification($cardMandate->getMandateId(),
            [
                Constants::MANDATE_HQ_AMOUNT => $payment->getAmount(),
            ]);

        $mandateHQstatus = $mandateHqResponse[Constants::MANDATE_HQ_STATUS];

        $notificationId = $mandateHqResponse[Constants::MANDATE_HQ_NOTIFICATION_ID];

        $cardMandateNotification->setNotificationId($notificationId);

        $status = $this->getStatusFromMandateHQStatus($mandateHQstatus);

        $cardMandateNotification->setStatus($status);

        if ($cardMandateNotification->getStatus() === Status::NOTIFIED)
        {
            $cardMandateNotification->setNotifiedAt(Carbon::now()->timestamp);
        }

        $cardMandateNotification->saveOrFail();

        if ($cardMandateNotification->getStatus() === Status::NOTIFIED)
        {
            $reminderId = $this->setCardAutoRecurringReminder($cardMandateNotification);

            $cardMandateNotification->setReminderId($reminderId);

            $cardMandateNotification->saveOrFail();
        }

        if ($cardMandateNotification->getStatus() === Status::FAILED)
        {
            $this->handleNotificationFailed($cardMandateNotification, $payment);
        }

        return $cardMandateNotification;
    }

    public function verifyNotification(Payment\Entity $payment): Entity
    {
        $this->trace->info(TraceCode::CARD_MANDATE_VERIFY_NOTIFICATION_REQUEST, [
            'payment_id' => $payment->getId(),
        ]);

        $cardMandateNotification = $payment->cardMandateNotification;

        $mandateId = $cardMandateNotification->cardMandate->getMandateId();

        $verifyInput = [
            Constants::MANDATE_HQ_AMOUNT          => $payment->getAmount(),
            Constants::MANDATE_HQ_NOTIFICATION_ID => $cardMandateNotification->getNotificationId(),
        ];

        $mandateHqResponse = $this->app->mandateHQ->verifyNotification($mandateId, $verifyInput);

        if ($mandateHqResponse[Constants::MANDATE_HQ_SUCCESS] === true)
        {
            $cardMandateNotification->setStatus(Status::VERIFIED);

            $cardMandateNotification->setVerifiedAt(Carbon::now()->timestamp);
        }
        else
        {
            $cardMandateNotification->setStatus(Status::VERIFICATION_FAILED);
        }

        $cardMandateNotification->saveOrFail();

        $this->trace->info(TraceCode::CARD_MANDATE_VERIFY_NOTIFICATION_RESPONSE, [
            'payment_id'                   => $payment->getId(),
            'card_mandate_notification_id' => $cardMandateNotification->getId(),
            'status'                       => $cardMandateNotification->getStatus(),
        ]);

        return $cardMandateNotification;
    }

    public function notifyAfterDebit(Payment\Entity $payment)
    {
        $cardMandateNotification = $payment->cardMandateNotification;

        if ($cardMandateNotification === null)
        {
            return;
        }

        if ($cardMandateNotification->getStatus() !== Status::VERIFIED)
        {
            throw new BadRequestValidationFailureException(
                'card mandate notification is not in proper status'
            );
        }

        $mandateId = $cardMandateNotification->cardMandate->getMandateId();
        $notificationId = $cardMandateNotification->getNotificationId();

        $this->app->mandateHQ->postDebitNotify($mandateId, $notificationId);

        $this->repo->transaction(
            function () use ($cardMandateNotification)
            {
                $this->repo->card_mandate_notification->lockForUpdateAndReload($cardMandateNotification);

                if ($cardMandateNotification->getStatus() !== Status::VERIFIED)
                {
                    throw new BadRequestValidationFailureException(
                        'card mandate notification is not in proper status'
                    );
                }

                $cardMandateNotification->setStatus(Status::POST_DEBIT_NOTIFIED);

                $cardMandateNotification->saveOrFail();
            });
    }

    public function processCallBack($notificationId, $status): Entity
    {
        $this->trace->info(TraceCode::CARD_MANDATE_NOTIFICATION_PROCESS_CALL_BACK, [
            'notification_id' => $notificationId,
        ]);

        $cardMandateNotification = $this->repo->card_mandate_notification->findByNotificationIdOrFail($notificationId);

        $status = $this->getStatusFromMandateHQStatus($status);

        $this->repo->transaction(
            function () use ($cardMandateNotification, $status) {
                $this->repo->card_mandate_notification->lockForUpdateAndReload($cardMandateNotification);

                $cardMandateNotification->setStatus($status);

                if ($cardMandateNotification->getStatus() === Status::NOTIFIED)
                {
                    $cardMandateNotification->setNotifiedAt(Carbon::now()->timestamp);
                }

                $cardMandateNotification->saveOrFail();
            });

        if ($cardMandateNotification->getStatus() === Status::NOTIFIED)
        {
            $reminderId = $this->setCardAutoRecurringReminder($cardMandateNotification);

            $cardMandateNotification->setReminderId($reminderId);

            $cardMandateNotification->saveOrFail();
        }

        if  ($cardMandateNotification->getStatus() === Status::FAILED)
        {
            $this->handleNotificationFailed($cardMandateNotification, $cardMandateNotification->payment);
        }

        $this->trace->info(TraceCode::CARD_MANDATE_NOTIFICATION_PROCESS_CALL_BACK, [
            'card_mandate_notification_id' => $cardMandateNotification->getId(),
            'status'                       => $cardMandateNotification->getStatus(),
        ]);

        return $cardMandateNotification;
    }

    protected function handleNotificationFailed(Entity $notification, Payment\Entity $payment)
    {
        $processor = new Payment\Processor\Processor($notification->merchant);

        $processor->failNotificationNotSentCardAutoRecurringPayment($payment);
    }

    protected function setCardAutoRecurringReminder(Entity $cardMandateNotification)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_NOTIFICATION_REMINDER_CREATE_REQUEST, [
            'id' => $cardMandateNotification->getId(),
        ]);

        $reminderData = [
            'remind_at' => $cardMandateNotification->getRemindAt(),
        ];

        $namespace  = Reminders\ReminderProcessor::CARD_AUTO_RECURRING;
        $merchantId = Merchant\Account::SHARED_ACCOUNT;
        $paymentId  = $cardMandateNotification->payment->GetId();
        $url = sprintf('reminders/send/%s/payment/%s/%s', $this->mode, $namespace, $paymentId);

        $request = [
            'namespace'     => $namespace,
            'entity_id'     => $paymentId,
            'entity_type'   => E::PAYMENT,
            'reminder_data' => $reminderData,
            'callback_url'  => $url,
        ];

        $response = $this->app['reminders']->createReminder($request, $merchantId);

        $reminderId = array_get($response, Entity::ID);

        $this->trace->info(TraceCode::CARD_MANDATE_NOTIFICATION_REMINDER_CREATE_RESPONSE, [
            'id'          => $cardMandateNotification->getId(),
            'reminder_id' => $reminderId,
        ]);

        return $reminderId;
    }

    protected function getStatusFromMandateHQStatus($status)
    {
        switch ($status)
        {
            case Constants::MANDATE_HQ_STATUS_CREATED:
            case Constants::MANDATE_HQ_STATUS_PENDING:
                return Status::PENDING;
            case Constants::MANDATE_HQ_STATUS_DEBIT_PENDING:
                return Status::NOTIFIED;
            case Constants::MANDATE_HQ_STATUS_COMPLETED:
                return Status::POST_DEBIT_NOTIFIED;
            case Constants::MANDATE_HQ_STATUS_FAILED:
                return Status::FAILED;
            default:
                throw new LogicException('Should not have reached here. Status: ' . $status);
        }
    }
}
