<?php

namespace RZP\Models\CardMandate\MandateHubs\MandateHQ;

use Crypt;
use Exception;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Card;
use RZP\Models\CardMandate\Entity;
use RZP\Models\CardMandate\Status;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\CardMandate;
use RZP\Constants\Timezone;
use RZP\Models\Plan\Subscription;
use RZP\Exception\LogicException;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
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

    public function RegisterMandate(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = []): Mandate
    {
        $mandateHqInput = $this->getRegisterInput($payment, $input);

        $response = $this->app->mandateHQ->registerMandate($mandateHqInput);

        if ((empty($input[Entity::SKIP_SUMMARY_PAGE]) === false) and
            ($input[Entity::SKIP_SUMMARY_PAGE] === true))
        {
            $cardMandate->setStatus(Status::MANDATE_APPROVED);
        }

        return $this->getMandateFromMandateHqResponse($response);
    }

    public function ReportInitialPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        $mandateHqInput = $this->getReportInitialPaymentInput($payment);

        (new CardMandate\Core())->storeVaultTokenPan($cardMandate, $mandateHqInput);

        return $this->app->mandateHQ->reportPayment($cardMandate->getMandateId(), $mandateHqInput);
    }

    public function reportSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        $mandateHqInput = $this->getReportSubsequentPaymentInput($payment);

        return $this->app->mandateHQ->reportPayment($cardMandate->getMandateId(), $mandateHqInput);
    }

    public function CreatePreDebitNotification(CardMandate\Entity $cardMandate, ?Payment\Entity $payment, $input): Notification
    {
        $mandateHqInput = $this->getCreatePreDebitNotificationInput($input);

        $response = $this->app->mandateHQ->createPreDebitNotification($cardMandate->getMandateId(), $mandateHqInput);

        return self::getNotificationFromMandateHqResponse($response);
    }

    public function getRedirectResponseIfApplicable(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        if ($cardMandate->isCustomerConsentRequired() === false)
        {
            return null;
        }

        $mandateUrl = $cardMandate->getMandateSummaryUrl();

        if ($this->app['basicauth']->isPrivateAuth() === true)
        {
            return [
                'razorpay_payment_id' => $payment->getPublicId(),
                'next'                => [
                    [
                        'action' => 'redirect',
                        'url'    => $mandateUrl,
                    ],
                ],
            ];
        }

        return [
            'request' => [
                'url'     => $mandateUrl,
                'method'  => 'get',
                'content' => [],
            ],
            'version'    => 1,
            'type'       => 'first',
            'payment_id' => $payment->getPublicId(),
            'gateway'    => Crypt::encrypt('mandate_hq__' . Carbon::now()->unix()),
        ];
    }

    public function getValidationBeforeSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = [], $forceCard = true)
    {
        return $this->app->mandateHQ->validatePayment($cardMandate->getMandateId(), $input);
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
            Notification::AFA_STATUS       => $response['afa_status'] ?? '',
            Notification::AFA_REQUIRED     => $response['afa_required'] ?? '',
            Notification::AFA_COMPLETED_AT => $response['afa_completed_at'] ?? '',
            Notification::AMOUNT           => $response['amount'] ?? 0,
            Notification::CURRENCY         => $response['currency'] ?? null,
            Notification::PURPOSE          => $response['purpose'] ?? null,
            Notification::NOTES            => $response['notes'] ?? [],
        ];

        return (new Notification($notificationAttributes));
    }

    protected function getCreatePreDebitNotificationInput($input)
    {
        $debitTimeCarbon = Carbon::createFromTimestamp($input['debit_at'] ?? Carbon::now()->addDay()->timestamp,
            Timezone::IST);

        $currency = empty($input['currency']) ? Currency::INR : $input['currency'];
        $payment_id = empty($input['payment_id']) ? null: $input['payment_id'];
        return [
            Constants::NOTIFICATION_TYPE              => Constants::NOTIFICATION_TYPE_PRE_DEBIT,
            Constants::PAYMENT_ID                     => $payment_id,
            Constants::NOTIFICATION_PRE_DEBIT_DETAILS => [
                Constants::NOTIFICATION_PRE_DEBIT_DETAILS_AMOUNT      => $input['amount'],
                Constants::NOTIFICATION_PRE_DEBIT_DETAILS_PURPOSE     => $input['purpose'] ?? null,
                Constants::NOTIFICATION_PRE_DEBIT_DETAILS_DEBIT_DAY   => $debitTimeCarbon->day,
                Constants::NOTIFICATION_PRE_DEBIT_DETAILS_DEBIT_MONTH => $debitTimeCarbon->month,
                Constants::CURRENCY                                   => $currency,
            ],
            Constants::NOTES                          => empty($input['notes']) ? null : $input['notes'],
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
        $card = $payment->card;

        $token = $payment->localToken;

        $paymentStatus = Payment\Status::CAPTURED;
        $paymentErrorCode = $payment->getErrorCode();
        $paymentErrorDescription = $payment->getErrorDescription();

        if ($payment->isFailed() === true)
        {
            $paymentStatus = Payment\Status::FAILED;
        }

        if ($payment->getStatus() === Payment\Status::REFUNDED)
        {
            $paymentStatus = Payment\Status::FAILED;
            $paymentErrorCode = ErrorCode::BAD_REQUEST_ERROR;
            // This error description will be updated when we receive proper
            // descriptions from product, will handle this error case scenario separately.
            $paymentErrorDescription = 'Failed to tokenised the card';
        }

        $inputResponse = [
            Constants::RECURRING_DEBIT_TYPE => Constants::RECURRING_DEBIT_TYPE_INITIAL,
            Constants::CURRENCY             => $payment->getCurrency(),
            Constants::AMOUNT               => $payment->getAmount(),
            Constants::PAYMENT_STATUS       => $paymentStatus,
            Constants::FAILURE_CODE         => $paymentErrorCode,
            Constants::FAILURE_DESCRIPTION  => $paymentErrorDescription,
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

        if ($token->card->isRzpSavedCard() === false)
        {
            try {
                $tokenInput = $token->card->buildTokenisedTokenForMandateHub();
                $networkToken = $tokenInput['token'];
                $inputResponse[Constants::TOKEN] = $networkToken;

                return $inputResponse;

            } catch (Exception $e){

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKEN_CRYPTOGRAM_EXCEPTION);
            }
        }

        return $inputResponse;
    }

    protected function getRegisterInput(Payment\Entity $payment, $input = []): array
    {
        $url = $this->getRedirectUrlForPayment($payment->getPublicId());

        $token = $payment->localToken;

        $card = $payment->card;

        $maxAmount = $token->getMaxAmount();

        $frequency = $token->getFrequency();

        if ($maxAmount === null)
        {
            $maxAmount = Constants::MAX_AMOUNT_DEFAULT;
        }

        if ($frequency === null)
        {
            $frequency = Constants::FREQUENCY_AS_PRESENTED;
        }

        $startTime = $token->getStartTime();
        if ($startTime === null)
        {
            $startTime = Carbon::now()->addDay()->getTimestamp();
        }

        $endTime = $token->getExpiredAt();
        if ($endTime === null)
        {
            $endTime = $token->card->getExpiryTimestamp();
        }

        $debitType = Constants::DEBIT_TYPE_VARIABLE_AMOUNT;
        if (empty($input['debit_type']) === false)
        {
            $debitType = $input['debit_type'];
        }

        $skipSummaryPage = false;
        if (empty($input['skip_summary_page']) === false)
        {
            $skipSummaryPage = $input['skip_summary_page'];
        }

        if (($this->app['razorx']->getTreatment($payment->merchant->getId(), Merchant\RazorxTreatment::CARD_MANDATE_CORRECT_DETAILS_FETCH, $this->app['rzp.mode']) === 'on') and
            ($payment->getSubscriptionId() !== null))
        {
            try
            {
                $input = [
                    Payment\Entity::SUBSCRIPTION_ID => Subscription\Entity::getSignedId($payment->getSubscriptionId())
                ];

                $subscriptionData = $this->app['module']->subscription->fetchSubscriptionInfoCardMandate($input, $payment->merchant);

                $frequency = $subscriptionData['frequency'] ?? Constants::FREQUENCY_AS_PRESENTED;

                $maxAmount = $subscriptionData['max_amount'] ?? $maxAmount;

                $endTime = $subscriptionData['end_time'] ?? $endTime;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::CARD_MANDATE_SUBSCRIPTIONS_FETCH_FAILURE);
            }
        }


        $inputResponse = [
            Constants::AMOUNT       => $payment->getAmount(),
            Constants::CURRENCY     => $payment->getCurrency(),
            Constants::METHOD       => Constants::METHOD_CARD,
            Constants::DEBIT_TYPE   => $debitType,
            Constants::BUSINESS     => $payment->merchant->getName(),
            Constants::MCC          => $payment->merchant->getCategory(),
            Constants::MAX_AMOUNT   => $maxAmount,
            Constants::START_TIME   => $startTime,
            Constants::END_TIME     => $endTime,
            Constants::FREQUENCY    => $frequency,
            Constants::CALLBACK_URL => $url,
            Constants::SKIP_SUMMARY_PAGE => $skipSummaryPage,
            Constants::CARD         => [
                Constants::CARD_NUMBER       => $this->getCardNumber($card,$payment->getGateway()),
                Constants::CARD_NAME         =>  $card->getName(),
                Constants::CARD_EXPIRY_MONTH => stringify($card->getExpiryMonth()),
                Constants::CARD_EXPIRY_YEAR  => substr(stringify($card->getExpiryYear()), -2)
            ],
            Constants::NOTES       => empty($input['notes']) ? null : $input['notes'],
        ];

        $isTokenPan = $token->card->isTokenPan();

        if (($token->card->isRzpSavedCard() === false) or
                ($isTokenPan === true))
        {
            try {
                if ($isTokenPan === true){
                    // In case of token requester merchant
                    $tokenInput = $token->card->buildTokenisedTokenForMandateHub($this->getCardNumber($token->card,$payment->getGateway()));
                } else {
                    $tokenInput = $token->card->buildTokenisedTokenForMandateHub();
                }
                $networkToken = $tokenInput['token'];
                $inputResponse[Constants::TOKEN] = $networkToken;
                unset($inputResponse[Constants::CARD]);

                return $inputResponse;

            } catch (Exception $e){

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKEN_CRYPTOGRAM_EXCEPTION);
            }
        }

        return $inputResponse;
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

    protected function getCardNumber(Card\Entity $card,$gateway=null)
    {
        $cardToken = $card->getCardVaultToken();

        return (new Card\CardVault)->getCardNumber($cardToken,$card->toArray(),$gateway);
    }

    public function updateTokenisedCardTokenInMandate($cardMandate, $input)
    {
        $this->app->mandateHQ->updateTokenisedCardTokenInMandate($cardMandate->getMandateId(), $input);
    }
}
