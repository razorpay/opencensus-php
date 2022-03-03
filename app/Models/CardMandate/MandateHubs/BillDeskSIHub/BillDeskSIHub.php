<?php

namespace RZP\Models\CardMandate\MandateHubs\BillDeskSIHub;

use Carbon\Carbon;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\CardMandate;
use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\Notification;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Trace\TraceCode;

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

        return $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB, Payment\Action::REPORT_PAYMENT, $billDeskInput, $this->mode);
    }

    public function getValidationBeforeSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = [])
    {
        $validationInput = $this->getValidationInput($payment, $cardMandate);

        $response = $this->app['gateway']->call(MandateHubs::BILLDESK_SIHUB, Payment\Action::CARD_MANDATE_VERIFY, $validationInput, $this->mode);

        $status = $response['data'][Constants::STATUS];

        if ($status !== 'success')
        {
            throw new BadRequestValidationFailureException('Subsequent payment validation failed.');
        }
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

        return $this->getMandateFromSIHubResponse($response['data']);
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
        $card = $payment->card;

        $cardData = $card->toArray();

        $cardData[Constants::CARD_NUMBER] = $this->getCardNumber($card);

        $startTime = $payment->localToken->getStartTime();

        if ($startTime === null) {
            $startTime = Carbon::now()->addDay()->getTimestamp();
        }

        $tokenData = array_merge($payment->localToken->toArray(), [
            'frequency' =>  $payment->localToken->getFrequency(),
            'start_time' => $startTime,
        ]);

        return [
            Constants::PAYMENT          => $payment->toArray(),
            Constants::TERMINAL         => $cardMandate->terminal ? $cardMandate->terminal->toArray() : null,
            Constants::GATEWAY          => MandateHubs::BILLDESK_SIHUB,
            Constants::MERCHANT         => $payment->merchant->toArray(),
            Constants::TOKEN            => $tokenData,
            Constants::CARD             => $cardData,
            Constants::CARD_MANDATE     => $cardMandate->toArray(),
        ];
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
        return [
            Constants::PAYMENT              => $payment->toArray(),
            Constants::GATEWAY              => MandateHubs::BILLDESK_SIHUB,
            Constants::CARD                 => $payment->card->toArray(),
            Constants::TERMINAL             => $cardMandate->terminal ? $cardMandate->terminal->toArray() : null,
            Constants::MERCHANT             => $payment->merchant->toArray(),
            Constants::RECURRING_DEBIT_TYPE => Constants::RECURRING_DEBIT_TYPE_INITIAL,
            Constants::AUTHENTICATION       => $authenticationData,
            Constants::AUTHORIZATION        => $authorizationData,
            Constants::CARD_MANDATE         => $cardMandate->toArray(),
        ];
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
     * @return array
     */
    protected function getValidationInput(Payment\Entity $payment, CardMandate\Entity $cardMandate)
    {

        $card = $payment->card;

        $cardData = $card->toArray();

        $cardData[Constants::CARD_NUMBER] = $this->getCardNumber($card);

        return [
            Constants::PAYMENT      => $payment->toArray(),
            Constants::TERMINAL     => $cardMandate->terminal ? $cardMandate->terminal->toArray() : null,
            Constants::GATEWAY      => MandateHubs::BILLDESK_SIHUB,
            Constants::NOTIFICATION => $payment->cardMandateNotification->toArray(),
            Constants::CARD         => $cardData,
            Constants::TOKEN        => $payment->localToken->toArray(),
            Constants::MERCHANT     => $payment->merchant->toArray(),
            Constants::CARD_MANDATE => $cardMandate->toArray(),
        ];
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
            Constants::TERMINAL             => $cardMandate->terminal ? $cardMandate->terminal->toArray() : null,
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
     * @return Mandate
     */
    public static function getMandateFromSIHubResponse($response): Mandate {

        if (isset($response[Constants::AMOUNT]) === true){
            $response[Constants::AMOUNT]= $response[Constants::AMOUNT]*100;
        }

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
            Constants::TERMINAL     => $cardMandate->terminal ? $cardMandate->terminal->toArray() : null,
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
    protected function getCardNumber(Card\Entity $card) : string
    {
        $cardToken = $card->getCardVaultToken();

        return (new Card\CardVault)->getCardNumber($cardToken);
    }
}
