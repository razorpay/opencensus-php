<?php

namespace RZP\Models\CardMandate;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Card\CardVault;
use RZP\Models\Customer\Token;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Models\CardMandate\MandateHubs;
use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\MandateHQ;
use RZP\Models\CardMandate\MandateHubs\BillDeskSIHub;
use RZP\Models\CardMandate\MandateHubs\MandateStatus;

class Core extends Base\Core
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
     * @param Payment\Entity $payment
     * @param array $input
     * @return Entity
     * @throws \Exception
     */
    public function create(Payment\Entity $payment, $input = []): Entity
    {
        $ex = null;

        $cardMandate = null;

        $this->trace->info(TraceCode::CARD_MANDATE_CREATE_REQUEST, [
            'payment_id' => $payment->getId(),
        ]);

        if ($payment->getCurrency() !== Currency::INR)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED);
        }


        $this->app['diag']->trackPaymentEventV2(
            EventCode::PAYMENT_CARD_MANDATE_CREATE_INITIATED,
            $payment
        );

        if ($payment->merchant->isFeatureEnabled(Feature\Constants::CARD_MANDATE_SKIP_PAGE) === false)
        {
            $input[Entity::SKIP_SUMMARY_PAGE] = true;
        }



        try
        {
            $cardMandate = (new Entity)->build();

            $cardMandate->merchant()->associate($payment->merchant);

            $terminal = (new MandateHubs\MandateHubTerminalSelector)->GetTerminalForPayment($payment, $cardMandate);

            $cardMandate->terminal()->associate($terminal);

            $mandateHub = (new MandateHubs\MandateHubSelector)->getHubInstance($terminal->getGateway());

            $mandate = $mandateHub->RegisterMandate($cardMandate, $payment, $input);

            $this->fillDataFromMandateRegisterResponse($cardMandate, $mandate);

            if ($cardMandate->getMandateHub() === MandateHubs\MandateHubs::RUPAY_SIHUB)
            {
                $cardMandate->setStatus(Status::MANDATE_APPROVED);
            }

            $this->repo->saveOrFail($cardMandate);

            $this->trace->info(
                TraceCode::CARD_MANDATE_CREATED,
                [
                    'merchant_id'     => $payment->merchant->getId(),
                    'card_mandate_id' => $cardMandate->getId(),
                    'mandate_hub'     => $cardMandate->getMandateHub(),
                ]
            );
        }
        catch (\Exception $exception)
        {
            $ex = $exception;

            throw $exception;
        }
        finally
        {
            $this->app['diag']->trackPaymentEventV2(
                EventCode::PAYMENT_CARD_MANDATE_CREATE_PROCESSED,
                $payment,
                $ex,
                [],
                (new EventData)
                    ->withCardMandate($cardMandate)
                    ->toArray()
            );
        }

        return $cardMandate;
    }

    /**
     * @param Payment\Entity $payment
     * @return CardMandateNotification\Entity
     * @throws \Exception
     */
    public function createPreDebitNotification(Payment\Entity $payment): CardMandateNotification\Entity
    {
        $ex = null;

        $cardMandateNotification = null;

        $cardMandate = $payment->localToken->cardMandate;

        $this->trace->info(TraceCode::CARD_MANDATE_PRE_DEBIT_NOTIFICATION_REQUEST, [
            'payment_id'  => $payment->getId(),
            'mandate_id'  => $cardMandate->getId(),
            'mandate_hub' => $cardMandate->getMandateHub(),
        ]);

        $this->app['diag']->trackPaymentEventV2(
            EventCode::PAYMENT_CARD_MANDATE_SUBSEQUENT_INITIATED,
            $payment,
            null,
            [],
            (new EventData)
                ->withCardMandate($cardMandate)
                ->toArray()
        );

        try
        {
            $cardMandateNotification = (new CardMandateNotification\Core)->create($cardMandate, [
                CardMandateNotification\Entity::AMOUNT => $payment->getAmount(),
            ], $payment);

            $cardMandateNotification->payment()->associate($payment);

            $cardMandateNotification->saveOrFail();
            $this->trace->info(TraceCode::CARD_MANDATE_PRE_DEBIT_NOTIFICATION_CREATED, [
                'payment_id'                   => $payment->getId(),
                'card_mandate_notification_id' => $cardMandateNotification->getId(),
                'mandate_hub'                  => $cardMandate->getMandateHub(),
                'reminder_id'                  => $cardMandateNotification->getReminderId(),
            ]);
        }
        catch (\Exception $exception)
        {
            $ex = $exception;

            throw $exception;
        }
        finally {

            $this->app['diag']->trackPaymentEventV2(
                EventCode::PAYMENT_CARD_MANDATE_SUBSEQUENT_PROCESSED,
                $payment,
                $ex,
                [],
                (new EventData)
                    ->withCardMandate($cardMandate)
                    ->withCardMandateNotification($cardMandateNotification)
                    ->toArray()
            );
        }

        return $cardMandateNotification;
    }

    public function validateAutoPaymentCreation(Entity $cardMandate, Payment\Entity $payment)
    {
        $errorCode = null;

        if ($cardMandate->getStatus() === Status::CANCELLED)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_CANCELLED;
        }

        if ($cardMandate->getStatus() === Status::PAUSED)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_PAUSED;
        }

        if ($cardMandate->getStatus() === Status::COMPLETED)
        {
            $errorCode = ErrorCode::BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_EXPIRED;
        }

        if ($payment->getCurrency() !== Currency::INR)
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED;
        }

        if (empty($errorCode) === false)
        {
            throw new BadRequestException($errorCode, null, [
                Payment\Entity::METHOD => Payment\Method::CARD,
            ]);
        }
    }

    public function updateCardMandateAfterMandateAction(Payment\Entity $payment, $hash, $approved)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_ACTION_UPDATE_REQUEST, [
            'payment_id'  => $payment->getId(),
            'is_approved' => $approved,
        ]);

        $this->verifyHash($hash, $payment->getPublicId());

        $cardMandate = $payment->localToken->cardMandate;

        $status = Status::MANDATE_APPROVED;

        if ($approved !== Constants::MANDATE_HQ_TRUE)
        {
            $status = Status::MANDATE_CANCELLED;
        }

        $this->repo->transaction(
            function () use ($cardMandate, $status)
            {
                $this->repo->card_mandate->lockForUpdateAndReload($cardMandate);

                if ($cardMandate->getStatus() !== Status::CREATED)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'mandate is already processed'
                    );
                }

                $cardMandate->setStatus($status);

                $cardMandate->saveOrFail();
            });

        $this->trace->info(TraceCode::CARD_MANDATE_ACTION_UPDATED, [
            'card_mandate_id'     => $cardMandate->getId(),
            'card_mandate_status' => $cardMandate->getStatus(),
        ]);
    }

    public function processSihubWebhook($input)
    {
        if (!is_null($input)) {

            $this->trace->info(TraceCode::CARD_MANDATE_ACTION_PROCESS_CALLBACK, [
                'input' => $input,
            ]);

            try {
                $response = $this->app['gateway']->call(MandateHubs\MandateHubs::BILLDESK_SIHUB, Payment\Action::CARD_MANDATE_UPDATE, $input, $this->mode);

                $this->trace->info(TraceCode::CARD_MANDATE_ACTION_SIHUB_REQUEST, [
                    'payload' => $response,
                ]);

                $mandate = BillDeskSIHub\BillDeskSIHub::getMandateFromSIHubResponse($response['data'], null);
                $mandateId = $mandate->getAttribute(Mandate::MANDATE_ID);

                $cardMandate = $this->repo->card_mandate->findByMandateId($mandateId);
                $merchantID = $cardMandate->merchant->getId();

                if (!is_null($input) && ($this->app['razorx']->getTreatment($merchantID, Merchant\RazorxTreatment::RECURRING_SIHUB_CANCEL_WEBHOOK_ENABLED, $this->mode) === 'on')) {
                    // Update status of token & end webhook to merchant only if experiment is turned on for MID
                    $this->updateMandateFromCallbackResponse($mandate);
                }

            } catch (\Exception $e){
                $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::CARD_MANDATE_SIHUB_TOKEN_UPDATE_FAILED,
                [
                    'mandate_id' => $mandate->getAttribute(Mandate::MANDATE_ID),
                ]);
            }
        }

        // Returning success regardless of any errors on our end

        return [];
    }

    public function processMandateHQCallBack($input)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_ACTION_PROCESS_CALLBACK, [
            'input' => $input,
        ]);

        (new MandateHQ\Validator)->validateInput('process_call_back', $input);

        $contains = $input[MandateHQ\Constants::WEBHOOK_CONTAINS];

        if (in_array(MandateHQ\Constants::WEBHOOK_ENTITY_MANDATE, $contains))
        {
            $mandateResponse = $input[MandateHQ\Constants::WEBHOOK_PAYLOAD][MandateHQ\Constants::WEBHOOK_ENTITY_MANDATE];
            $mandate = MandateHQ\MandateHQ::getMandateFromMandateHqResponse($mandateResponse[MandateHQ\Constants::WEBHOOK_ENTITY]);

            $this->updateMandateFromCallbackResponse($mandate);
        }

        if (in_array(MandateHQ\Constants::WEBHOOK_ENTITY_NOTIFICATION, $contains))
        {
            $notificationResponse = $input[MandateHQ\Constants::WEBHOOK_PAYLOAD][MandateHQ\Constants::WEBHOOK_ENTITY_NOTIFICATION];
            $notification = MandateHQ\MandateHQ::getNotificationFromMandateHqResponse($notificationResponse[MandateHQ\Constants::WEBHOOK_ENTITY]);

            (new CardMandateNotification\Core)->updateNotificationFromCallbackResponse($notification);
        }

        return [];
    }

    protected function updateMandateFromCallbackResponse(Mandate $mandate)
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $mandateId = $mandate->getAttribute(Mandate::MANDATE_ID);

        $cardMandate = $this->repo->card_mandate->findByMandateId($mandateId);

        if ($cardMandate === null)
        {
            $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

            $cardMandate = $this->repo->card_mandate->findByMandateId($mandateId);
        }

        if ($cardMandate === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $previousStatus = $this->repo->transaction(
            function () use ($cardMandate, $mandate)
            {
                $this->repo->card_mandate->lockForUpdateAndReload($cardMandate);

                $previousStatus = $cardMandate->getStatus();

                $cardMandate->setStatus($this->getCardMandateStatusFromMandateStatus($mandate->getStatus()));

                $cardMandate->saveOrFail();

                return $previousStatus;
            });

        $tokenCore = (new Token\Core);
        $tokenId = $cardMandate->token->getId();

        if ($previousStatus != $cardMandate->getStatus())
        {
            switch ($cardMandate->getStatus())
            {
                case Status::ACTIVE:
                    $tokenCore->resumeCardToken($tokenId);
                    break;
                case Status::PAUSED:
                    $tokenCore->pauseCardToken($tokenId);
                    break;
                case Status::CANCELLED:
                    $tokenCore->cancelCardToken($tokenId);
                    break;
                case Status::COMPLETED:
                    $tokenCore->completeCardToken($tokenId, Carbon::now()->unix());
                    break;
                default:
                    throw new Exception\ServerErrorException('should not have reached here',
                        ErrorCode::SERVER_ERROR,
                        [
                            'id'     => $cardMandate->getId(),
                            'status' => $cardMandate->getStatus(),
                        ]);
            }
        }

        return $cardMandate;
    }

    protected function getCardMandateStatusFromMandateStatus($status)
    {
        switch ($status)
        {
            case MandateStatus::CREATED:
                return Status::CREATED;
            case MandateStatus::ACTIVATED:
                return Status::ACTIVE;
            case MandateStatus::PAUSED:
                return Status::PAUSED;
            case MandateStatus::CANCELLED:
                return Status::CANCELLED;
            case MandateStatus::COMPLETED:
                return Status::COMPLETED;
            default:
                throw new Exception\ServerErrorException('should not have reached here',
                    ErrorCode::SERVER_ERROR,
                ['mandate_status' => $status]);
        }
    }

    public function reportInitialPayment(Payment\Entity $payment)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_PAYMENT_INITIAL_REPORT, [
            'payment_id'  => $payment->getId(),
        ]);

        $token = $payment->localToken;

        $cardMandateId = $token->getCardMandateId();

        $cardMandate = $this->repo->card_mandate->findByIdAndMerchant($cardMandateId, $payment->merchant);

        $mandateHub = (new MandateHubs\MandateHubSelector)->GetMandateHubForCardMandate($cardMandate);

        try
        {
            $mandateHub->ReportInitialPayment($cardMandate, $payment);

            if (($payment->isFailed() === false) and
                ($payment->getStatus() !== Payment\Status::REFUNDED))
            {
                $cardMandate->setStatus(Status::ACTIVE);

                $cardMandate->saveOrFail();

                $this->trace->info(TraceCode::CARD_MANDATE_CONFIRMED, [
                    'card_mandate_id'     => $cardMandate->getId(),
                    'card_mandate_status' => $cardMandate->getStatus(),
                ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::CARD_MANDATE_REPORT_INITIAL_PAYMENT_FAILED,
                [
                    'card_mandate_id' => $cardMandate->getId(),
                    'payment_id'      => $payment->getId(),
                    'exception'       => $e->getMessage(),
                ]);
        }
    }

    public function reportSubsequentPayment(Payment\Entity $payment)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_PAYMENT_SUBSEQUENT_REPORT, [
            'payment_id'  => $payment->getId(),
        ]);

        $token = $payment->localToken;

        $cardMandateId = $token->getCardMandateId();

        $cardMandate = $this->repo->card_mandate->findByIdAndMerchant($cardMandateId, $payment->merchant);

        $mandateHub = (new MandateHubs\MandateHubSelector)->GetMandateHubForCardMandate($cardMandate);

        try
        {
            $mandateHub->reportSubsequentPayment($cardMandate, $payment);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::CARD_MANDATE_REPORT_SUBSEQUENT_PAYMENT_FAILED,
                [
                    'card_mandate_id' => $cardMandate->getId(),
                    'payment_id'      => $payment->getId(),
                    'exception'       => $e->getMessage(),
                ]);
        }
    }

    public function cancelMandateBeforeTokenDeletion(Entity $cardMandate)
    {
        if ($cardMandate->getStatus() === Status::CANCELLED)
        {
            return;
        }

        $this->repo->transaction(
            function () use ($cardMandate)
            {
                $this->repo->card_mandate->lockForUpdateAndReload($cardMandate);

                $cardMandate->setStatus(Status::CANCELLED);

                $cardMandate->saveOrFail();
            });

        $mandateHub = (new MandateHubs\MandateHubSelector)->GetMandateHubForCardMandate($cardMandate);

        $mandateHub->cancelMandate($cardMandate);
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

    protected function verifyHash(string $inputHash, string $paymentPublicId)
    {
        $expectedHash = $this->getHashOf($paymentPublicId);

        if (hash_equals($expectedHash, $inputHash) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Callback payment hash does not match. Please notify the admin of this error.');
        }
    }

    protected function fillDataFromMandateRegisterResponse(Entity $cardMandate, MandateHubs\Mandate $mandate)
    {
        $cardMandate->setMandateId($mandate->getAttribute(Mandate::MANDATE_ID));
        $cardMandate->setMandateSummaryUrl($mandate->getAttribute(Mandate::MANDATE_SUMMARY_URL));
        $cardMandate->setMandateCardId($mandate->getAttribute(Mandate::MANDATE_CARD_ID));
        $cardMandate->setMandateCardName($mandate->getAttribute(Mandate::MANDATE_CARD_NAME));
        $cardMandate->setMandateCardLast4($mandate->getAttribute(Mandate::MANDATE_CARD_LAST4));
        $cardMandate->setMandateCardNetwork($mandate->getAttribute(Mandate::MANDATE_CARD_NETWORK));
        $cardMandate->setMandateCardType($mandate->getAttribute(Mandate::MANDATE_CARD_TYPE));
        $cardMandate->setMandateCardIssuer($mandate->getAttribute(Mandate::MANDATE_CARD_ISSUER));
        $cardMandate->setMandateCardInternational($mandate->getAttribute(Mandate::MANDATE_CARD_INTERNATIONAL));
        $cardMandate->setDebitType($mandate->getAttribute(Mandate::DEBIT_TYPE));
        $cardMandate->setCurrency($mandate->getAttribute(Mandate::CURRENCY));
        $cardMandate->setMaxAmount($mandate->getAttribute(Mandate::MAX_AMOUNT));
        $cardMandate->setAmount($mandate->getAttribute(Mandate::AMOUNT));
        $cardMandate->setStartAt($mandate->getAttribute(Mandate::START_AT));
        $cardMandate->setEndAt($mandate->getAttribute(Mandate::END_AT));
        $cardMandate->setTotalCycles($mandate->getAttribute(Mandate::TOTAL_CYCLES));
        $cardMandate->setMandateInterval($mandate->getAttribute(Mandate::MANDATE_INTERVAL));
        $cardMandate->setFrequency($mandate->getAttribute(Mandate::FREQUENCY));

        $cardMandate->setMandateHub($mandate->getMandateHub());
    }

    public function updateTokenisedCardTokenInMandate($cardMandate, $input)
    {
        $mandateHub = (new MandateHubs\MandateHubSelector)->GetMandateHubForCardMandate($cardMandate);

        $mandateHub->updateTokenisedCardTokenInMandate($cardMandate, $input);
    }

    public function storeVaultTokenPan($cardMandate, $input)
    {
        if(isset($input['token']['number']) and
           !empty($input['token']['number']))
        {
            try
            {
                $vaultTokenPAN = (new CardVault)->getVaultToken(["card" => $input['token']['number']], [], true);

                $cardMandate->setVaultTokenPan($vaultTokenPAN);

                $this->repo->card_mandate->saveOrFail($cardMandate);
            }
            catch (\Throwable $e)
            {
                $this->trace->info(TraceCode::MISC_TRACE_CODE, [
                    'errorInStoreVaultTokenPan'      => $e->getMessage(),
                    'input'                          => $input
                ]);
            }
        }
    }
}
