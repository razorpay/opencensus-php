<?php

namespace RZP\Models\CardMandate\CardMandateNotification;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Reminders;
use RZP\Models\CardMandate;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;

class Core extends Base\Core
{
    public function create(Payment\Entity $payment, CardMandate\Entity $cardMandate): Entity
    {
        $cardMandateNotification = (new Entity)->build();

        $cardMandateNotification->merchant()->associate($payment->merchant);
        $cardMandateNotification->cardMandate()->associate($cardMandate);
        $cardMandateNotification->payment()->associate($payment);

        $mandateHub = (new CardMandate\MandateHubs\MandateHubSelector)->GetMandateHubForCardMandate($cardMandate);

        $debitTime = $this->getDebitTime($payment);

        $notification = $mandateHub->CreatePreDebitNotification($cardMandate, $payment, $debitTime);

        $cardMandateNotification->setNotificationId($notification->getId());

        $status = $this->getStatusFromNotificationStatus($notification->getStatus());

        if ($status !== Status::CREATED)
        {
            $cardMandateNotification->setStatus($status);
        }

        if ($status === Status::NOTIFIED)
        {
            $cardMandateNotification->setNotifiedAt($notification->getNotifiedAt());
        }

        $cardMandateNotification->setAfaRequired($notification->getAfaRequired());

        if ($cardMandateNotification->isAfaRequired()) {
            $cardMandateNotification->setAfaStatus($notification->getAfaStatus());
            $cardMandateNotification->setAfaCompletedAt($notification->getAfaCompletedAt());
        }

        $cardMandateNotification->setDebitAt($debitTime);

        $cardMandateNotification->saveOrFail();

        if (!$cardMandateNotification->isAfaRequired() and
            $cardMandateNotification->getStatus() === Status::NOTIFIED)
        {
            $reminderId = $this->setCardAutoRecurringReminder($cardMandateNotification);

            $cardMandateNotification->setReminderId($reminderId);

            $cardMandateNotification->saveOrFail();
        }

        if ((!$cardMandateNotification->isAfaRequired() and $cardMandateNotification->getStatus() === Status::FAILED) or
            ($cardMandateNotification->isAfaRequired() and
                ($cardMandateNotification->getAfaStatus() === AfaStatus::REJECTED ||
                    $cardMandateNotification->getAfaStatus() === AfaStatus::EXPIRED)))
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

        $this->trace->info(TraceCode::CARD_MANDATE_VERIFY_NOTIFICATION_RESPONSE, [
            'payment_id'                   => $payment->getId(),
            'card_mandate_notification_id' => $cardMandateNotification->getId(),
            'status'                       => $cardMandateNotification->getStatus(),
        ]);

        return $cardMandateNotification;
    }

    public function updateNotificationFromCallbackResponse(CardMandate\MandateHubs\Notification $notification): Entity
    {
        $this->trace->info(TraceCode::CARD_MANDATE_NOTIFICATION_PROCESS_CALL_BACK, [
            'notification_id' => $notification->getId(),
        ]);

        $this->mode = Mode::LIVE;

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $cardMandateNotification = $this->repo->card_mandate_notification->findByNotificationId($notification->getId());

        if ($cardMandateNotification === null)
        {
            $this->mode = Mode::TEST;

            $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

            $cardMandateNotification = $this->repo->card_mandate_notification->findByNotificationId($notification->getId());
        }

        if ($cardMandateNotification === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $this->app['basicauth']->setMerchant($cardMandateNotification->merchant);

        $status = $this->getStatusFromNotificationStatus($notification->getStatus());

        $this->repo->transaction(
            function () use ($cardMandateNotification, $status, $notification) {
                $this->repo->card_mandate_notification->lockForUpdateAndReload($cardMandateNotification);

                if ($cardMandateNotification->getStatus() === Status::CREATED or
                    $cardMandateNotification->getStatus() === Status::PENDING)
                {
                    $cardMandateNotification->setStatus($status);

                    if ($cardMandateNotification->getStatus() === Status::NOTIFIED)
                    {
                        $cardMandateNotification->setNotifiedAt($notification->getNotifiedAt());
                    }
                }

                if ($cardMandateNotification->isAfaRequired())
                {
                    $cardMandateNotification->setAfaStatus($notification->getAfaStatus());

                    $cardMandateNotification->setAfaCompletedAt($notification->getAfaCompletedAt());
                }

                $cardMandateNotification->saveOrFail();
            });

        if (!$cardMandateNotification->isAfaRequired() and
            $cardMandateNotification->getStatus() === Status::NOTIFIED)
        {
            $reminderId = $this->setCardAutoRecurringReminder($cardMandateNotification);

            $cardMandateNotification->setReminderId($reminderId);

            $cardMandateNotification->saveOrFail();
        }
        else if ($cardMandateNotification->isAfaRequired() and
            $cardMandateNotification->getAfaStatus() === AfaStatus::APPROVED)
        {
            $namespace  = Reminders\ReminderProcessor::CARD_AUTO_RECURRING;
            $paymentId  = $cardMandateNotification->payment->GetId();
            (new Reminders\CardAutoRecurringReminderProcessor)->process(E::PAYMENT, $namespace, $paymentId, []);
        }

        if ($cardMandateNotification->getStatus() === Status::FAILED)
        {
            $this->handleNotificationFailed($cardMandateNotification, $cardMandateNotification->payment);
        }

        $this->trace->info(TraceCode::CARD_MANDATE_NOTIFICATION_PROCESS_CALL_BACK, [
            'card_mandate_notification_id' => $cardMandateNotification->getId(),
            'status'                       => $cardMandateNotification->getStatus(),
        ]);

        return $cardMandateNotification;
    }

    protected function getDebitTime(Payment\Entity $payment)
    {
        $time = Carbon::now();
        if ($payment->getAmount() > Constants::WITHOUT_AFA_AMOUNT_LIMIT) {
            $time->addDays(3);
        } else {
            $time->addDay();
        }

        return $time->unix();
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

    protected function getStatusFromNotificationStatus($status)
    {
        switch ($status)
        {
            case CardMandate\MandateHubs\NotificationStatus::CREATED:
                return Status::CREATED;
            case CardMandate\MandateHubs\NotificationStatus::PENDING:
                return Status::PENDING;
            case CardMandate\MandateHubs\NotificationStatus::NOTIFIED:
                return Status::NOTIFIED;
            case CardMandate\MandateHubs\NotificationStatus::FAILED:
                return Status::FAILED;
            default:
                throw new LogicException('Should not have reached here. Status: ' . $status);
        }
    }
}
