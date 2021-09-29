<?php

namespace RZP\Models\CardMandate\MandateHubs\MandateHQ;

use Carbon\Carbon;

use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\CardMandate;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\Notification;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;

class MandateHQ extends CardMandate\MandateHubs\BaseHub
{
    /**
     * Api Route instance
     *
     * @var \RZP\Http\Route
     */
    protected $route;

    public function __construct()
    {
        parent::__construct();

        $this->route = $this->app['api.route'];
    }

    public function CancelMandate(CardMandate\Entity $cardMandate): Mandate
    {
        $response = $this->app->mandateHQ->cancelMandate($cardMandate->getMandateId());

        return self::getMandateFromMandateHqResponse($response);
    }

    public function RegisterMandate(Payment\Entity $payment): Mandate
    {
        $mandateHqInput = $this->getRegisterInput($payment);

        $response = $this->app->mandateHQ->registerMandate($mandateHqInput);

        return $this->getMandateFromMandateHqResponse($response);
    }

    public function ReportInitialPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        $mandateHqInput = $this->getReportInitialPaymentInput($payment);

        return $this->app->mandateHQ->reportPayment($cardMandate->getMandateId(), $mandateHqInput);
    }

    public function reportSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        $mandateHqInput = $this->getReportSubsequentPaymentInput($payment);

        return $this->app->mandateHQ->reportPayment($cardMandate->getMandateId(), $mandateHqInput);
    }

    public function CreatePreDebitNotification(CardMandate\Entity $cardMandate, Payment\Entity $payment, $debitTime): Notification
    {
        $mandateHqInput = $this->getCreatePreDebitNotificationInput($payment, $debitTime);

        $response = $this->app->mandateHQ->createPreDebitNotification($cardMandate->getMandateId(), $mandateHqInput);

        return self::getNotificationFromMandateHqResponse($response);
    }

    public static function getMandateFromMandateHqResponse($response): Mandate {
        $mandateAttributes = [
            Mandate::MANDATE_ID                 => $response[Constants::ID] ?? null,
            Mandate::MANDATE_CARD_ID            => $response[Constants::CARD][Constants::CARD_ID] ?? null,
            Mandate::MANDATE_CARD_NAME          => $response[Constants::CARD][Constants::CARD_NAME] ?? null,
            Mandate::MANDATE_CARD_LAST4         => $response[Constants::CARD][Constants::CARD_LAST4] ?? null,
            Mandate::MANDATE_CARD_NETWORK       => $response[Constants::CARD][Constants::CARD_NETWORK] ?? null,
            Mandate::MANDATE_CARD_TYPE          => $response[Constants::CARD][Constants::CARD_TYPE] ?? null,
            Mandate::MANDATE_CARD_ISSUER        => $response[Constants::CARD][Constants::CARD_ISSUER] ?? null,
            Mandate::MANDATE_CARD_INTERNATIONAL => $response[Constants::CARD][Constants::CARD_INTERNATIONAL] ?? null,
            Mandate::MANDATE_SUMMARY_URL        => $response[Constants::REDIRECT_URL] ?? null,
            Mandate::STATUS                     => $response[Constants::STATUS] ?? null,
            Mandate::DEBIT_TYPE                 => $response[Constants::DEBIT_TYPE] ?? null,
            Mandate::CURRENCY                   => $response[Constants::CURRENCY] ?? null,
            Mandate::MAX_AMOUNT                 => $response[Constants::MAX_AMOUNT] ?? null,
            Mandate::AMOUNT                     => $response[Constants::AMOUNT] ?? null,
            Mandate::START_AT                   => $response[Constants::START_TIME] ?? null,
            Mandate::END_AT                     => $response[Constants::END_TIME] ?? null,
            Mandate::TOTAL_CYCLES               => $response[Constants::TOTAL_CYCLES] ?? null,
            Mandate::MANDATE_INTERVAL           => $response[Constants::INTERVAL] ?? null,
            Mandate::FREQUENCY                  => $response[Constants::FREQUENCY] ?? null,
            Mandate::PAUSED_BY                  => $response[Constants::PAUSED_BY] ?? null,
            Mandate::CANCELLED_BY               => $response[Constants::CANCELLED_BY] ?? null,
        ];

        return (new Mandate(MandateHubs::MANDATE_HQ, $mandateAttributes));
    }

    public static function getNotificationFromMandateHqResponse($response): Notification {
        $notificationAttributes = [
            Notification::NOTIFICATION_ID  => $response['id'] ?? '',
            Notification::NOTIFIED_AT      => $response['delivered_at'] ?? '',
            Notification::STATUS           => self::getNotificationStatusFromHQNotificationStatus($response['status'] ?? ''),
            Notification::AFA_STATUS       => $response['afa_status'],
            Notification::AFA_REQUIRED     => $response['afa_required'],
            Notification::AFA_COMPLETED_AT => $response['afa_completed_at'],
        ];

        return (new Notification($notificationAttributes));
    }

    protected function getCreatePreDebitNotificationInput(Payment\Entity $payment, $debitTime)
    {
        $debitTimeCarbon = Carbon::createFromTimestamp($debitTime, Timezone::IST);

        return [
            Constants::NOTIFICATION_TYPE => Constants::NOTIFICATION_TYPE_PRE_DEBIT,
            Constants::NOTIFICATION_PRE_DEBIT_DETAILS => [
                Constants::NOTIFICATION_PRE_DEBIT_DETAILS_AMOUNT => $payment->getAmount(),
                Constants::NOTIFICATION_PRE_DEBIT_DETAILS_DEBIT_DAY => $debitTimeCarbon->day,
                Constants::NOTIFICATION_PRE_DEBIT_DETAILS_DEBIT_MONTH => $debitTimeCarbon->month,
            ]
        ];
    }

    protected static function getNotificationStatusFromHQNotificationStatus($status)
    {
        switch ($status)
        {
            case NotificationStatus::CREATED:
                return CardMandate\MandateHubs\NotificationStatus::CREATED;
            case NotificationStatus::PENDING:
                return CardMandate\MandateHubs\NotificationStatus::PENDING;
            case NotificationStatus::DELIVERED:
                return CardMandate\MandateHubs\NotificationStatus::NOTIFIED;
            case NotificationStatus::FAILED:
                return CardMandate\MandateHubs\NotificationStatus::FAILED;
            default:
                throw new LogicException('should not have reached here');
        }
    }

    protected function getReportSubsequentPaymentInput(Payment\Entity $payment): array
    {
        $paymentStatus = Payment\Status::CAPTURED;
        if ($payment->isFailed() === true)
        {
            $paymentStatus = Payment\Status::FAILED;
        }

        return [
            Constants::NOTIFICATION_ID      => $payment->cardMandateNotification->getNotificationId(),
            Constants::RECURRING_DEBIT_TYPE => Constants::RECURRING_DEBIT_TYPE_SUBSEQUENT,
            Constants::CURRENCY             => $payment->getCurrency(),
            Constants::AMOUNT               => $payment->getAmount(),
            Constants::PAYMENT_STATUS       => $paymentStatus,
            Constants::FAILURE_CODE         => $payment->getErrorCode(),
            Constants::FAILURE_DESCRIPTION  => $payment->getErrorDescription(),
            Constants::CAPTURED_AT          => $payment->getAuthorizeTimestamp(),
            Constants::AUTHENTICATION       => [
                Constants::AUTHENTICATION_STATUS          => null,
                Constants::AUTHENTICATION_ECI             => null,
                Constants::AUTHENTICATION_IS_3DS_ENROLLED => null,
                Constants::AUTHENTICATION_CAVV            => null,
                Constants::AUTHENTICATION_CAVV_ALGORITHM  => null,
                Constants::AUTHENTICATION_XID             => null,
                Constants::AUTHENTICATION_STATUS_3DS      => null,
            ],
            Constants::AUTHORIZATION        => [
                Constants::AUTHORIZATION_STATUS        => null,
                Constants::AUTHORIZATION_AUTHORIZED_AT => $payment->getAuthenticatedTimestamp(),
                Constants::AUTHORIZATION_GATEWAY       => $payment->getAuthenticationGateway(),
                Constants::AUTHORIZATION_AUTH_CODE     => null,
                Constants::AUTHORIZATION_RRN           => null,
            ],
        ];
    }

    protected function getReportInitialPaymentInput(Payment\Entity $payment): array
    {
        $paymentStatus = Payment\Status::CAPTURED;
        if ($payment->isFailed() === true)
        {
            $paymentStatus = Payment\Status::FAILED;
        }

        return [
            Constants::RECURRING_DEBIT_TYPE => Constants::RECURRING_DEBIT_TYPE_INITIAL,
            Constants::CURRENCY             => $payment->getCurrency(),
            Constants::AMOUNT               => $payment->getAmount(),
            Constants::PAYMENT_STATUS       => $paymentStatus,
            Constants::FAILURE_CODE         => $payment->getErrorCode(),
            Constants::FAILURE_DESCRIPTION  => $payment->getErrorDescription(),
            Constants::CAPTURED_AT          => $payment->getAuthorizeTimestamp(),
            Constants::AUTHENTICATION       => [
                Constants::AUTHENTICATION_STATUS          => null,
                Constants::AUTHENTICATION_ECI             => null,
                Constants::AUTHENTICATION_IS_3DS_ENROLLED => null,
                Constants::AUTHENTICATION_CAVV            => null,
                Constants::AUTHENTICATION_CAVV_ALGORITHM  => null,
                Constants::AUTHENTICATION_XID             => null,
                Constants::AUTHENTICATION_STATUS_3DS      => null,
            ],
            Constants::AUTHORIZATION        => [
                Constants::AUTHORIZATION_STATUS        => null,
                Constants::AUTHORIZATION_AUTHORIZED_AT => $payment->getAuthenticatedTimestamp(),
                Constants::AUTHORIZATION_GATEWAY       => $payment->getAuthenticationGateway(),
                Constants::AUTHORIZATION_AUTH_CODE     => null,
                Constants::AUTHORIZATION_RRN           => null,
            ],
        ];
    }

    protected function getRegisterInput(Payment\Entity $payment): array
    {
        $url = $this->getRedirectUrlForPayment($payment->getPublicId());

        $token = $payment->localToken;

        $card = $payment->card;

        $maxAmount = $token->getMaxAmount();

        if ($maxAmount === null)
        {
            $maxAmount = Constants::MAX_AMOUNT_DEFAULT;
        }

        $startTime = $token->getStartTime();
        if ($startTime === null) {
            $startTime = Carbon::now()->addDay()->getTimestamp();
        }

        $endTime = $token->getExpiredAt();
        if ($endTime === null)
        {
            $endTime = $token->card->getExpiryTimestamp();
        }

        return [
            Constants::AMOUNT       => $payment->getAmount(),
            Constants::CURRENCY     => $payment->getCurrency(),
            Constants::METHOD       => Constants::METHOD_CARD,
            Constants::DEBIT_TYPE   => Constants::DEBIT_TYPE_VARIABLE_AMOUNT,
            Constants::BUSINESS     => $payment->merchant->getName(),
            Constants::MAX_AMOUNT   => $maxAmount,
            Constants::START_TIME   => $startTime,
            Constants::END_TIME     => $endTime,
            Constants::FREQUENCY    => Constants::FREQUENCY_AS_PRESENTED,
            Constants::CALLBACK_URL => $url,
            Constants::CARD         => [
                Constants::CARD_NUMBER       => $this->getCardNumber($card),
                Constants::CARD_NAME         =>  $card->getName(),
                Constants::CARD_EXPIRY_MONTH => stringify($card->getExpiryMonth()),
                Constants::CARD_EXPIRY_YEAR  => substr(stringify($card->getExpiryYear()), -2)
            ],
        ];
    }

    public function getRedirectUrlForPayment($paymentId)
    {
        $params = [
            'id'   => $paymentId,
            'hash' => $this->getHashOf($paymentId),
        ];

        $redirectRouteName = Constants::MANDATE_HQ_REDIRECT_ROUTE_NAME;

        return $this->route->getUrlWithPublicAuthInQueryParam(
            $redirectRouteName,
            $params);
    }

    /**
     * Returns a hash of a string.
     *
     * @param string $string
     * @return string Hash of the string
     */
    protected function getHashOf(string $string): string
    {
        $secret = $this->app->config->get('app.key');

        return hash_hmac('sha1', $string, $secret);
    }

    protected function getCardNumber(Card\Entity $card)
    {
        $cardToken = $card->getCardVaultToken();

        return (new Card\CardVault)->getCardNumber($cardToken);
    }
}
