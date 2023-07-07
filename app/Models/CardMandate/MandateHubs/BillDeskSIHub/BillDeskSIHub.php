<?php

namespace RZP\Models\CardMandate\MandateHubs\BillDeskSIHub;

use Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Carbon\Carbon;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\CardMandate;
use RZP\Models\Plan\Subscription;
use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\Notification;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Merchant;

class BillDeskSIHub extends CardMandate\MandateHubs\BaseHub
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

    /**
     * @param CardMandate\Entity $cardMandate
     * @param Payment\Entity $payment
     * @param array $input
     * @return Mandate
     * @throws \Exception
     */
    public function RegisterMandate(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = []): Mandate
    {
        $billDeskInput = $this->getRegisterInput($payment, $cardMandate);

        $response = $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB, Payment\Action::CARD_MANDATE_CREATE, $billDeskInput, $this->mode);

        // TODO add appropriate checks
        $cardMandate->setStatus(CardMandate\Status::MANDATE_APPROVED);

        if(array_key_exists(Constants::CARD, $billDeskInput)) {
            return $this->getMandateFromSIHubResponse($response['data'], $billDeskInput['card']);
        }

        return $this->getMandateFromSIHubResponse($response['data']);
    }

    /**
     * @param CardMandate\Entity $cardMandate
     * @param Payment\Entity $payment
     * @return mixed
     * @throws BadRequestException
     */
    public function ReportInitialPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        $authorizationData = [];

        $authenticationData = (new Payment\Service)->getAuthenticationEntity($payment->getPublicId());

        if ($payment->isFailed() === false)
        {
            $authorizationData = (new Payment\Service)->getAuthorizationEntity($payment->getPublicId());
        }

        $billDeskInput = $this->getReportInitialPaymentInput($payment, $cardMandate, $authenticationData, $authorizationData);

        (new CardMandate\Core())->storeVaultTokenPan($cardMandate, $billDeskInput);

        return $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB, Payment\Action::REPORT_PAYMENT, $billDeskInput, $this->mode);
    }

    public function getValidationBeforeSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = [], $forceCard = true)
    {
        $validationInput = $this->getValidationInput($payment, $cardMandate, $forceCard);

        $response = $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB, Payment\Action::CARD_MANDATE_VERIFY, $validationInput, $this->mode);

        $status = $response['data'][Constants::STATUS];

        if ($status !== 'success')
        {
            throw new BadRequestValidationFailureException('Subsequent payment validation failed.');
        }

        if (isset($response['data'][Constants::XID]) &&
            isset($response['data'][Constants::CAVV2]))
        {
            $response[Constants::XID] = $response['data'][Constants::XID];
            $response[Constants::CAVV2] = $response['data'][Constants::CAVV2];
            $response[Constants::GATEWAY] = Constants::BILLDESK_SIHUB;
        }

        return $response;
    }

    /**
     * @param CardMandate\Entity $cardMandate
     * @param Payment\Entity $payment
     * @return mixed
     */
    public function reportSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        $authorizationData = [];

        if ($payment->isFailed() === false)
        {
            $authorizationData = (new Payment\Service)->getAuthorizationEntity($payment->getPublicId());
        }

        $billDeskInput = $this->getReportSubsequentPaymentInput($payment, $cardMandate, $authorizationData);

        return $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB, Payment\Action::REPORT_PAYMENT, $billDeskInput, $this->mode);
    }

    /**
     * @param CardMandate\Entity $cardMandate
     * @param Payment\Entity $payment
     * @param $input
     * @return Notification
     * @throws LogicException
     */
    public function CreatePreDebitNotification(CardMandate\Entity $cardMandate, ?Payment\Entity $payment, $input): Notification
    {
        $billDeskInput = $this->getCreatePreDebitNotificationInput($payment, $cardMandate, $input);

        $response = $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB,
            Payment\Action::CARD_MANDATE_PRE_DEBIT_NOTIFY, $billDeskInput, $this->mode);

        return $this->getNotificationFromBilldeskResponse($response['data']);
    }

    /**
     * @param CardMandate\Entity $cardMandate
     * @return Mandate|null
     */
    public function CancelMandate(CardMandate\Entity $cardMandate): ?Mandate
    {
        $billDeskInput = $this->getCreateCancelMandateInput($cardMandate);

        $response = $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB,
            Payment\Action::CARD_MANDATE_CANCEL, $billDeskInput, $this->mode);

        return $this->getMandateFromSIHubResponse($response['data'],null);
    }

    /**
     * @param CardMandate\Entity $cardMandate
     * @param Payment\Entity $payment
     * @return array|null
     */
    public function getRedirectResponseIfApplicable(CardMandate\Entity $cardMandate, Payment\Entity $payment) : ?array
    {
        return null;
    }

    /**
     * @param Payment\Entity $payment
     * @param CardMandate\Entity $cardMandate
     * @return array
     * @throws \Exception
     */
    protected function getRegisterInput(Payment\Entity $payment, CardMandate\Entity $cardMandate): array
    {

        $variant = $this->app['razorx']->getTreatment(
            $payment->getMerchantId(),
            Merchant\RazorxTreatment::SIHUB_DISABLE_CARD_FLOW_POST_TOKENIZATION,
            $this->mode
        );

        $cardData = [];

        if (strtolower($variant) !== 'on')
        {
            $card = $payment->card;

            $cardData = $card->toArray();

            $cardData[Constants::CARD_NUMBER] = $this->getCardNumber($card,$payment->getGateway());
        }

        $startTime = $payment->localToken->getStartTime();

        if ($startTime === null) {
            $startTime = Carbon::now()->addDay()->getTimestamp();
        }

        $tokenData = array_merge($payment->localToken->toArray(), [
            'frequency' =>  $payment->localToken->getFrequency(),
            'start_time' => $startTime,
        ]);

        if($tokenData['frequency'] === null)
        {
            $tokenData['frequency'] = Constants::FREQUENCY_AS_PRESENTED;
        }

        $endTime = $payment->localToken->getExpiredAt();

        if ($endTime === null)
        {
            $endTime = $card->getExpiryTimestamp();
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

                $tokenData['frequency'] = $subscriptionData['frequency'] ?? Constants::FREQUENCY_AS_PRESENTED;

                $tokenData['max_amount'] = $subscriptionData['max_amount'] ?? $tokenData['max_amount'];

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
            Constants::PAYMENT          => $payment->toArray(),
            Constants::TERMINAL         => $payment->terminal ? $payment->terminal->toArray() : null,
            Constants::GATEWAY          => MandateHubs::BILLDESK_SIHUB,
            Constants::MERCHANT         => $payment->merchant->toArray(),
            Constants::TOKEN            => $tokenData,
            Constants::CARD             => $cardData ?? null,
            Constants::CARD_MANDATE     => $cardMandate->toArray(),
            Constants::END_TIME         => $endTime,
        ];

        return $this->getTokenDetails($payment, $inputResponse);
    }

    /**
     * @param Payment\Entity $payment
     * @param CardMandate\Entity $cardMandate
     * @param array $authenticationData
     * @param array $authorizationData
     * @return array
     */
    protected function getReportInitialPaymentInput(Payment\Entity $payment,
                                                    CardMandate\Entity $cardMandate, array $authenticationData, array $authorizationData): array
    {
        $inputResponse = [
            Constants::PAYMENT              => $payment->toArray(),
            Constants::GATEWAY              => MandateHubs::BILLDESK_SIHUB,
            Constants::CARD                 => $payment->card->toArray(),
            Constants::TERMINAL             => $payment->terminal ? $payment->terminal->toArray() : null,
            Constants::MERCHANT             => $payment->merchant->toArray(),
            Constants::RECURRING_DEBIT_TYPE => Constants::RECURRING_DEBIT_TYPE_INITIAL,
            Constants::AUTHENTICATION       => $authenticationData,
            Constants::AUTHORIZATION        => $authorizationData,
            Constants::CARD_MANDATE         => $cardMandate->toArray(),
        ];

        $token = $payment->localToken;

        if ($token->card->isRzpSavedCard() == false)
        {
            try {
                $tokenInput = $token->card->buildTokenisedTokenForMandateHub();
                $networkToken = $tokenInput['token'];
                $inputResponse[Constants::TOKEN] = $networkToken;

                return $inputResponse;

            } catch (Exception $e) {

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKEN_CRYPTOGRAM_EXCEPTION);
            }
        }

        return $inputResponse;
    }

    /**
     * @param CardMandate\Entity $cardMandate
     * @return array
     */
    protected function getCreateCancelMandateInput(CardMandate\Entity $cardMandate)
    {
        return [
            Constants::GATEWAY      => MandateHubs::BILLDESK_SIHUB,
            Constants::PAYMENT      => [
                Constants::GATEWAY => MandateHubs::BILLDESK_SIHUB,
                Constants::ID      => null,
            ],
            Constants::TERMINAL     => $cardMandate->terminal ? $cardMandate->terminal->toArray() : null,
            Constants::CARD_MANDATE => $cardMandate->toArray(),
        ];
    }

    /**
     * @param Payment\Entity $payment
     * @param CardMandate\Entity $cardMandate
     * @param bool $forceCard
     * @return array
     */
    protected function getValidationInput(Payment\Entity $payment, CardMandate\Entity $cardMandate, $forceCard = true)
    {
        if($forceCard === true)
        {
            $card = $payment->localToken->card;

            $cardData = $card->toArray();

            $cardData[Constants::CARD_NUMBER] = $this->getCardNumber($card, $payment->getGateway());
        }

        $inputResponse = [
            Constants::PAYMENT      => $payment->toArray(),
            Constants::TERMINAL     => $payment->terminal ? $payment->terminal->toArray() : null,
            Constants::GATEWAY      => MandateHubs::BILLDESK_SIHUB,
            Constants::NOTIFICATION => $payment->cardMandateNotification->toArray(),
            Constants::CARD         => $cardData ?? null,
            Constants::TOKEN        => $payment->localToken->toArray(),
            Constants::MERCHANT     => $payment->merchant->toArray(),
            Constants::CARD_MANDATE => $cardMandate->toArray(),
        ];

        if ($forceCard === true)
        {
            return $inputResponse;
        }

        return $this->getTokenDetails($payment, $inputResponse);
    }

    protected function getTokenDetails(Payment\Entity $payment, array $inputResponse) {

        $token = $payment->localToken;

        if ($token->card->isRzpSavedCard() == false)
        {
            try {
                if((isset($token->cardMandate)) and
                   ($token->cardMandate !== null) and
                   ($token->cardMandate->getVaultTokenPan() !== null))
                {
                    $recurringTokenNumber = (new Card\CardVault)->getCardNumber($token->cardMandate->getVaultTokenPan(),[],null,true);

                    $tokenInput = $token->card->buildTokenisedTokenForMandateHub($recurringTokenNumber);
                }
                else
                {
                    $tokenInput = $token->card->buildTokenisedTokenForMandateHub();

                    if((isset($token->cardMandate)) and
                       ($token->cardMandate !== null))
                    {
                        (new CardMandate\Core())->storeVaultTokenPan($token->cardMandate, $tokenInput);
                    }
                }
                $networkToken = $tokenInput['token'];
                $tokenData = array_merge($inputResponse[Constants::TOKEN], $networkToken);
                $inputResponse[Constants::TOKEN] = $tokenData;

                if (isset($inputResponse[Constants::CARD]))
                {
                    unset($inputResponse[Constants::CARD]);
                }

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

    /**
     * @param Payment\Entity $payment
     * @param CardMandate\Entity $cardMandate
     * @param array $authorizationData
     * @return array
     */
    protected function getReportSubsequentPaymentInput(Payment\Entity $payment, CardMandate\Entity $cardMandate, array $authorizationData): array
    {
        return [
            Constants::PAYMENT              => $payment->toArray(),
            Constants::GATEWAY              => MandateHubs::BILLDESK_SIHUB,
            Constants::CARD                 => $payment->card->toArray(),
            Constants::TERMINAL             => $payment->terminal ? $payment->terminal->toArray() : null,
            Constants::MERCHANT             => $payment->merchant->toArray(),
            Constants::NOTIFICATION         => $payment->cardMandateNotification->toArray(),
            Constants::RECURRING_DEBIT_TYPE => Constants::RECURRING_DEBIT_TYPE_SUBSEQUENT,
            Constants::AUTHENTICATION       => null,
            Constants::AUTHORIZATION        => $authorizationData,
            Constants::CARD_MANDATE         => $cardMandate->toArray(),
        ];
    }

    /**
     * @param $response
     * @param $card
     * @return Mandate
     */
    public static function getMandateFromSIHubResponse($response ,$card=null): Mandate {

        if (isset($response[Constants::AMOUNT]) === true){
            $response[Constants::AMOUNT]= $response[Constants::AMOUNT]*100;
        }

        $mandateAttributes = [
            Mandate::MANDATE_ID                 => $response[Constants::ID] ?? null,
            Mandate::MANDATE_CARD_ID            => $card ? $card[Constants::CARD_ID] : null,
            Mandate::MANDATE_CARD_NAME          => $card ? $card[Constants::CARD_NAME] : null,
            Mandate::MANDATE_CARD_LAST4         => $card ? $card[Constants::CARD_LAST4] : null,
            Mandate::MANDATE_CARD_NETWORK       => $card ? $card[Constants::CARD_NETWORK] : null,
            Mandate::MANDATE_CARD_TYPE          => $card ? $card[Constants::CARD_TYPE] : null,
            Mandate::MANDATE_CARD_ISSUER        => $card ? $card[Constants::CARD_ISSUER] : null,
            Mandate::MANDATE_CARD_INTERNATIONAL => $card ? $card[Constants::CARD_INTERNATIONAL] : null,
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

        return (new Mandate(MandateHubs::BILLDESK_SIHUB, $mandateAttributes));
    }

    /**
     * @param $response
     * @return Notification
     * @throws LogicException
     */
    protected function getNotificationFromBilldeskResponse($response): Notification {
        $notificationAttributes = [
            Notification::NOTIFICATION_ID => $response[Constants::ID],
            Notification::NOTIFIED_AT     => $response[Constants::DELIVERED_AT],
            Notification::STATUS          => self::getNotificationStatusFromBDNotificationStatus($response[Constants::STATUS]),
        ];

        return (new Notification($notificationAttributes));
    }

    /**
     * @param $status
     * @return string
     * @throws LogicException
     */
    protected static function getNotificationStatusFromBDNotificationStatus($status) : string
    {
        switch ($status)
        {
            case NotificationStatus::CREATED:
                return CardMandate\MandateHubs\NotificationStatus::CREATED;
            case NotificationStatus::PENDING:
                return CardMandate\MandateHubs\NotificationStatus::PENDING;
            case NotificationStatus::FAILED:
                return CardMandate\MandateHubs\NotificationStatus::FAILED;
            case NotificationStatus::NOTIFIED:
                return CardMandate\MandateHubs\NotificationStatus::NOTIFIED;
            default:
                throw new LogicException('should not have reached here');
        }
    }

    /**
     * @param Payment\Entity $payment
     * @param CardMandate\Entity $cardMandate
     * @param $input
     * @return array
     */
    protected function getCreatePreDebitNotificationInput(Payment\Entity $payment, CardMandate\Entity $cardMandate, $input)
    {
        return [
            Constants::PAYMENT      => $payment->toArray(),
            Constants::TERMINAL     => $payment->terminal ? $payment->terminal->toArray() : null,
            Constants::GATEWAY      => MandateHubs::BILLDESK_SIHUB,
            Constants::CARD         => $payment->card->toArray(),
            Constants::MERCHANT     => $payment->merchant->toArray(),
            Constants::CARD_MANDATE => $cardMandate->toArray(),
            Constants::TOKEN        => $payment->localToken->toArray(),
            Constants::DEBIT_TIME   => $input['debit_at'],
        ];
    }

    /**
     * @param Card\Entity $card
     * @return string
     * @throws \Exception
     */
    protected function getCardNumber(Card\Entity $card , $gateway=null) : string
    {
        $cardToken = $card->getCardVaultToken();

        return (new Card\CardVault)->getCardNumber($cardToken,$card->toArray(),$gateway);
    }

    public function updateTokenisedCardTokenInMandate($cardMandate, $input)
    {
        $billDeskInput = $this->getUpdateTokenInput($cardMandate, $input);

        return $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB,
            Payment\Action::CARD_MANDATE_UPDATE_TOKEN, $billDeskInput, $this->mode);
    }

    protected function getUpdateTokenInput($cardMandate, $input)
    {
        return [
            Constants::PAYMENT      => [
                Constants::GATEWAY => MandateHubs::BILLDESK_SIHUB,
                Constants::ID      => null,
            ],
            Constants::TERMINAL     => [],
            Constants::GATEWAY      => MandateHubs::BILLDESK_SIHUB,
            Constants::CARD_MANDATE => $cardMandate,
            Constants::TOKEN        => $input[Constants::TOKEN],
        ];
    }
}
