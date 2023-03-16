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
use RZP\Models\Currency\Currency;
use RZP\Models\Card\IIN\MandateHub;
use RZP\Models\CardMandate\MandateHubs;

class Core extends Base\Core
{
    public function create(CardMandate\Entity $cardMandate, $input = [], $payment = null): Entity
    {
        $cardMandateNotification = (new Entity)->build();

        $cardMandateNotification->merchant()->associate($cardMandate->merchant);

        $cardMandateNotification->cardMandate()->associate($cardMandate);

        if ($payment !== null)
        {
            $cardMandateNotification->payment()->associate($payment);
        }

        $mandateHub = (new CardMandate\MandateHubs\MandateHubSelector)->GetMandateHubForCardMandate($cardMandate);

        if (empty($input[Entity::DEBIT_AT]) === true)
        {
            $input[Entity::DEBIT_AT] = $this->getDebitTime($input, $cardMandate);
        }

        try
        {
            $notification = $mandateHub->CreatePreDebitNotification($cardMandate, $payment, $input);

            (new CardMandate\Metric())->generateMetric(CardMandate\Metric::CARD_MANDATE_CREATE_PDN,
                ['mandate_hub' => $cardMandate->getMandateHub()]);
        }
        catch (\Exception $e)
        {
            $this->handlePreDebitNotificationToHubFailed($cardMandateNotification, $payment, $e);

            (new CardMandate\Metric())->generateMetric(CardMandate\Metric::CARD_MANDATE_CREATE_PDN_ERROR,
                ['mandate_hub' => $cardMandate->getMandateHub()]);

            throw $e;
        }

        $cardMandateNotification->setNotificationId($notification->getId());

        $cardMandateNotification->setAmount($input[Entity::AMOUNT]);

        $currency = empty($input[Entity::CURRENCY]) ? Currency::INR : $input[Entity::CURRENCY];
        $cardMandateNotification->setCurrency($currency);

        if (empty($input[Entity::PURPOSE]) === false)
        {
            $cardMandateNotification->setPurpose($input[Entity::PURPOSE]);
        }

        if (empty($input[Entity::NOTES]) === false)
        {
            $cardMandateNotification->setNotes($input[Entity::NOTES]);
        }

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

        $cardMandateNotification->setDebitAt($input[Entity::DEBIT_AT]);

        $cardMandateNotification->saveOrFail();

        if ($payment !== null and
            !$cardMandateNotification->isAfaRequired() and
            ($cardMandateNotification->getStatus() === Status::NOTIFIED or
                ($cardMandateNotification->getStatus() == Status::CREATED and
                    $cardMandate->getMandateHub() == MandateHub::MANDATE_HQ)) and
            $cardMandateNotification->getAfaStatus() !== AfaStatus::REJECTED)
        {
            $reminderId = $this->setCardAutoRecurringReminder($cardMandateNotification, $cardMandate->getMandateHub());

            $cardMandateNotification->setReminderId($reminderId);

            $cardMandateNotification->saveOrFail();
        }

        if (($payment !== null) and
            (!$cardMandateNotification->isAfaRequired() and
                ($cardMandateNotification->getAfaStatus() === AfaStatus::REJECTED ||
                    $cardMandateNotification->getStatus() === Status::FAILED)) or
            ($cardMandateNotification->isAfaRequired() and
                ($cardMandateNotification->getAfaStatus() === AfaStatus::REJECTED ||
                    $cardMandateNotification->getAfaStatus() === AfaStatus::EXPIRED)))
        {
            $this->handleNotificationFailed($cardMandateNotification, $payment);
        }

        return $cardMandateNotification;
    }

    public function validateAndAssociatePayment(Payment\Entity $payment, $notificationId): Entity
    {
        $cardMandateNotification = $this->repo
                                        ->card_mandate_notification
                                        ->findByPublicIdAndMerchant($notificationId, $payment->merchant);

        $cardMandateNotification = $this->repo->card_mandate_notification->lockForUpdate($cardMandateNotification->getId());

        $errorCode = null;

        if ($cardMandateNotification->payment !== null)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_NOTIFICATION_ALREADY_USED;
        }

        if ($cardMandateNotification->getAmount() !== $payment->getAmount())
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_NOTIFICATION_PAYMENT_AMOUNT_MISMATCH;
        }

        $paymentCurrency = empty($payment->getCurrency()) ? Currency::INR : $payment->getCurrency();
        if ($cardMandateNotification->getCurrency() !== $paymentCurrency)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_NOTIFICATION_PAYMENT_CURRENCY_MISMATCH;
        }

        $cardMandate = $cardMandateNotification->cardMandate;

        if ($cardMandate->isActive() === false)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_MANDATE_NOT_ACTIVE;
        }

        if (!$cardMandateNotification->isAfaRequired() and
            $cardMandateNotification->getStatus() !== Status::NOTIFIED)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_NOTIFIED;
        }

        if (!$cardMandateNotification->isAfaRequired() and
            $cardMandateNotification->getAfaStatus() === AfaStatus::REJECTED)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_OPTED_OUT_OF_PAYMENT;
        }

        if ($cardMandateNotification->isAfaRequired() and
            $cardMandateNotification->getAfaStatus() !== AfaStatus::APPROVED)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_CUSTOMER_NOT_APPROVED;
        }

        if (empty($errorCode) === false)
        {
            throw new Exception\BadRequestException($errorCode, null, [
                'id' => $notificationId,
                Payment\Entity::METHOD => Payment\Method::CARD,
            ]);
        }

        $cardMandateNotification->setPaymentId($payment->getId());

        $cardMandateNotification->saveOrFail();

        return $cardMandateNotification;
    }

    public function verifyNotification(Payment\Entity $payment): Entity
    {
        $this->trace->info(TraceCode::CARD_MANDATE_VERIFY_NOTIFICATION_REQUEST, [
            'payment_id' => $payment->getId(),
        ]);

        if ($payment->cardMandateNotification === null)
        {
            throw new LogicException('card mandate notification for payment can\'t be null');
        }

        $cardMandateNotification = $payment->cardMandateNotification;

        $cardMandate = $cardMandateNotification->cardMandate;

        $mandateHub = (new CardMandate\MandateHubs\MandateHubSelector)->GetMandateHubForCardMandate($cardMandate);

        $forceCard = true;

        if(($cardMandate->getMandateHub() === MandateHubs\MandateHubs::BILLDESK_SIHUB) and
           ($payment->localToken->card->isRzpSavedCard() === false))
        {
            $variant = $this->app['razorx']->getTreatment(
                $payment->getMerchantId(),
                Merchant\RazorxTreatment::SIHUB_VALIDATION_FORCE_CARD_INSTRUMENT_FIRST,
                $this->mode
            );

            if (strtolower($variant) !== 'control')
            {
                $forceCard = (strtolower($variant) === 'true');

                try
                {
                    $validationResponse = $mandateHub->getValidationBeforeSubsequentPayment($cardMandate, $payment, [
                        CardMandate\MandateHubs\Notification::NOTIFICATION_ID => $cardMandateNotification->getNotificationId(),
                        CardMandate\MandateHubs\Notification::AMOUNT          => $cardMandateNotification->getAmount()
                    ],$forceCard);

                    $this->trace->info(TraceCode::CARD_MANDATE_VERIFY_NOTIFICATION_RESPONSE, [
                        'payment_id'                   => $payment->getId(),
                        'card_mandate_notification_id' => $cardMandateNotification->getId(),
                        'status'                       => $cardMandateNotification->getStatus(),
                        'response'                     => $validationResponse,
                    ]);
                    $cardMandateNotification['validate_payment'] = $validationResponse;
                    return $cardMandateNotification;
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException($e,
                        null,
                        TraceCode::MISC_TRACE_CODE,
                        [
                            "failed validating after sending card instrument details" => $forceCard,
                            "paymentId" => $payment->getId()
                        ]);

                    $retryForErrors = [ErrorCode::BAD_REQUEST_TOKEN_BASED_CARD_MANDATE, ErrorCode::BAD_REQUEST_TOKEN_NOT_REPORTED_TO_MANDATE_HUB];

                    if(in_array($e->getCode(),$retryForErrors) === false)
                    {
                        $this->trace->info(
                            TraceCode::MISC_TRACE_CODE,
                            [
                                "no retry after actual expected errors" => $e->getMessage(),
                                "paymentId" => $payment->getId()
                            ]);

                        throw $e;
                    }

                    $forceCard = !$forceCard;

                    $this->trace->info(
                        TraceCode::MISC_TRACE_CODE,
                        [
                            "second try. trying validating with card now" => $forceCard
                        ]);
                }
            }
        }

        $validationResponse = $mandateHub->getValidationBeforeSubsequentPayment($cardMandate, $payment, [
            CardMandate\MandateHubs\Notification::NOTIFICATION_ID => $cardMandateNotification->getNotificationId(),
            CardMandate\MandateHubs\Notification::AMOUNT          => $cardMandateNotification->getAmount()
        ],$forceCard);

        $this->trace->info(TraceCode::CARD_MANDATE_VERIFY_NOTIFICATION_RESPONSE, [
            'payment_id'                   => $payment->getId(),
            'card_mandate_notification_id' => $cardMandateNotification->getId(),
            'status'                       => $cardMandateNotification->getStatus(),
            'response'                     => $validationResponse,
        ]);
        $cardMandateNotification['validate_payment'] = $validationResponse;

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

        $isApproved = false;

        $isNotified = false;

        $this->repo->transaction(
            function () use ($cardMandateNotification, $status, $notification, &$isApproved, &$isNotified) {
                $this->repo->card_mandate_notification->lockForUpdateAndReload($cardMandateNotification);

                if (($cardMandateNotification->getStatus() === Status::CREATED or
                    $cardMandateNotification->getStatus() === Status::PENDING) and
                    $cardMandateNotification->getStatus() !== $status)
                {
                    $cardMandateNotification->setStatus($status);

                    if ($cardMandateNotification->getStatus() === Status::NOTIFIED)
                    {
                        $cardMandateNotification->setNotifiedAt($notification->getNotifiedAt());

                        $isNotified = true;
                    }
                }

                if ($cardMandateNotification->isAfaRequired() and
                    $cardMandateNotification->getAfaStatus() === AfaStatus::CREATED and
                    $cardMandateNotification->getAfaStatus() !== $notification->getAfaStatus())
                {
                    $cardMandateNotification->setAfaStatus($notification->getAfaStatus());

                    if ($cardMandateNotification->getAfaStatus() === AfaStatus::APPROVED)
                    {
                        $isApproved = true;
                    }

                    $cardMandateNotification->setAfaCompletedAt($notification->getAfaCompletedAt());
                }

                $cardMandateNotification->saveOrFail();
            });

        $payment = $cardMandateNotification->payment;

        if ($payment !== null)
        {
            if (($cardMandateNotification->isAfaRequired() and
                    $cardMandateNotification->getAfaStatus() === AfaStatus::APPROVED and
                    $isApproved === true) and ($payment->getStatus() === Payment\Status::CREATED))
            {
                $namespace  = Reminders\ReminderProcessor::CARD_AUTO_RECURRING;
                $paymentId  = $cardMandateNotification->payment->GetId();
                (new Reminders\CardAutoRecurringReminderProcessor)->process(E::PAYMENT, $namespace, $paymentId, []);
            }

            if (((!$cardMandateNotification->isAfaRequired() and
                    ($cardMandateNotification->getAfaStatus() === AfaStatus::REJECTED ||
                        $cardMandateNotification->getStatus() === Status::FAILED)) or
                    ($cardMandateNotification->isAfaRequired() and
                        ($cardMandateNotification->getAfaStatus() === AfaStatus::REJECTED ||
                            $cardMandateNotification->getAfaStatus() === AfaStatus::EXPIRED))) and
                ($payment->getStatus() === Payment\Status::CREATED))
            {
                $this->handleNotificationFailed($cardMandateNotification, $cardMandateNotification->payment);
            }
        }

        $this->trace->info(TraceCode::CARD_MANDATE_NOTIFICATION_PROCESS_CALL_BACK, [
            'card_mandate_notification_id' => $cardMandateNotification->getId(),
            'status'                       => $cardMandateNotification->getStatus(),
        ]);

        return $cardMandateNotification;
    }

    protected function getDebitTime($input, CardMandate\Entity $cardMandate)
    {
        $time = Carbon::now();

        if ($cardMandate->getMandateHub() === MandateHubs\MandateHubs::BILLDESK_SIHUB) {
            $time->addDay();
        } else {
            if ($input[Payment\Entity::AMOUNT] > Constants::WITHOUT_AFA_AMOUNT_LIMIT) {
                $time->addDays(3);
            } else {
                $time->addDay();
            }
        }

        return $time->unix();
    }

    protected function handlePreDebitNotificationToHubFailed(Entity $notification, Payment\Entity $payment, \Exception $e)
    {
        $processor = new Payment\Processor\Processor($notification->merchant);

        $processor->failPreDebitNotificationDeliveryToHubFailedCardAutoRecurringPayment($payment, $e);
    }

    protected function handleNotificationFailed(Entity $notification, Payment\Entity $payment)
    {
        $processor = new Payment\Processor\Processor($notification->merchant);

        $processor->failNotificationNotSentCardAutoRecurringPayment($payment);
    }

    protected function setCardAutoRecurringReminder(Entity $cardMandateNotification, $mandateHub = MandateHubs\MandateHubs::MANDATE_HQ)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_NOTIFICATION_REMINDER_CREATE_REQUEST, [
            'id' => $cardMandateNotification->getId(),
            'mandate_id'  => $cardMandateNotification->cardMandate->getId(),
            'mandate_hub' => $cardMandateNotification->cardMandate->getMandateHub(),
        ]);

        $reminderData = [
            'remind_at' => $cardMandateNotification->getRemindAt($mandateHub),
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
            'mandate_id'  => $cardMandateNotification->cardMandate->getId(),
            'mandate_hub' => $cardMandateNotification->cardMandate->getMandateHub(),
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
            case CardMandate\MandateHubs\NotificationStatus::SUCCESS:
                return Status::NOTIFIED;
            case CardMandate\MandateHubs\NotificationStatus::FAILED:
            case CardMandate\MandateHubs\NotificationStatus::FAILURE:
                return Status::FAILED;
            default:
                throw new LogicException('Should not have reached here. Status: ' . $status);
        }
    }
}
