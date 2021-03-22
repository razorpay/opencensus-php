<?php

namespace RZP\Models\CardMandate;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

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

    public function create(Payment\Entity $payment): Entity
    {
        $this->trace->info(TraceCode::CARD_MANDATE_CREATE_REQUEST, [
            'payment_id' => $payment->getId(),
        ]);

        $cardMandate = (new Entity)->build();

        $cardMandate->merchant()->associate($payment->merchant);

        $mandateHqInput = $this->getMandateHQRegisterInput($payment);

        $mandateHqResponse = $this->app->mandateHQ->registerMandate($mandateHqInput);

        $cardMandate->setMandateRegisterId($mandateHqResponse[Constants::MANDATE_HQ_MANDATE_REGISTER_ID]);

        $cardMandate->setMandateSummaryUrl($mandateHqResponse[Constants::MANDATE_HQ_REDIRECT_URL]);

        $this->repo->saveOrFail($cardMandate);

        $this->trace->info(
            TraceCode::CARD_MANDATE_CREATED,
            [
                'merchant_id'     => $payment->merchant->getId(),
                'card_mandate_id' => $cardMandate->getId(),
            ]
        );

        return $cardMandate;
    }

    public function createPreDebitNotification(Payment\Entity $payment): CardMandateNotification\Entity
    {
        $this->trace->info(TraceCode::CARD_MANDATE_PRE_DEBIT_NOTIFICATION_REQUEST, [
            'payment_id' => $payment->getId(),
        ]);

        $token = $payment->localToken;

        $cardMandateId = $token->getCardMandateId();

        $cardMandate = $this->repo->card_mandate->findByIdAndMerchant($cardMandateId, $payment->merchant);

        $cardMandateNotification = (new CardMandateNotification\Core)->create($payment, $cardMandate);

        $this->trace->info(TraceCode::CARD_MANDATE_PRE_DEBIT_NOTIFICATION_CREATED, [
            'payment_id'                   => $payment->getId(),
            'card_mandate_notification_id' => $cardMandateNotification->getId(),
        ]);

        return $cardMandateNotification;
    }

    public function validateAutoPaymentCreation(Entity $cardMandate, Payment\Entity $payment)
    {
        if ($cardMandate->getStatus() === Status::CANCELLED)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_CANCELLED);
        }

        if ($cardMandate->getStatus() === Status::PAUSED)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_PAUSED);
        }

        if ($cardMandate->getStatus() === Status::EXPIRED)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_EXPIRED);
        }

        if ($payment->getAmount() > $cardMandate->getMaxAmount())
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_AMOUNT_GREATER_THAN_CARD_MANDATE_MAX_AMOUNT);
        }
    }

    public function updateCardMandateAfterMandateAction(Payment\Entity $payment, $hash, $approved)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_ACTION_UPDATE_REQUEST, [
            'payment_id'  => $payment->getId(),
            'is_approved' => $approved,
        ]);

        $this->verifyHash($hash, $payment->getPublicId());

        $token = $payment->localToken;

        $cardMandateId = $token->getCardMandateId();

        $cardMandate = $this->repo->card_mandate->findByIdAndMerchant($cardMandateId, $payment->merchant);

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

    public function processCallBack($mandateId, $status)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_ACTION_PROCESS_CALLBACK, [
            'mandate_id'  => $mandateId,
            'status'      => $status,
        ]);

        $cardMandate = $this->repo->card_mandate->findByMandateIdOrFail($mandateId);

        $this->repo->transaction(
            function () use ($cardMandate, $status)
            {
                $this->repo->card_mandate->lockForUpdateAndReload($cardMandate);

                $cardMandate->setStatus($status);

                $cardMandate->saveOrFail();
            });

        return $cardMandate;
    }

    public function postAuthorizeConfirmMandate(Payment\Entity $payment)
    {
        $this->trace->info(TraceCode::CARD_MANDATE_CONFIRM_REQUEST, [
            'payment_id'  => $payment->getId(),
        ]);

        $token = $payment->localToken;

        $cardMandateId = $token->getCardMandateId();

        $cardMandate = $this->repo->card_mandate->findByIdAndMerchant($cardMandateId, $payment->merchant);

        $mandateHqResponse = $this->app->mandateHQ->confirmMandate($cardMandate->getMandateRegisterId());

        $cardMandate->setMandateId($mandateHqResponse[Constants::MANDATE_HQ_MANDATE_ID]);

        $cardMandate->setStatus(Status::ACTIVE);

        $cardMandate->saveOrFail();

        $this->trace->info(TraceCode::CARD_MANDATE_CONFIRMED, [
            'card_mandate_id'     => $cardMandate->getId(),
            'card_mandate_status' => $cardMandate->getStatus(),
        ]);
    }

    protected function getMandateHQRegisterInput(Payment\Entity $payment)
    {
        $url = $this->getRedirectUrlForPayment($payment->getPublicId());

        $token = $payment->localToken;

        $card = $payment->card;

        $maxAmount = $token->getMaxAmount();

        if ($maxAmount === null)
        {
            $maxAmount = Constants::MANDATE_HQ_MAX_AMOUNT_DEFAULT;
        }

        $endTime = $token->card->getExpiryTimestamp();

        return [
            Constants::MANDATE_HQ_INSTRUMENT => [
                Constants::MANDATE_HQ_INSTRUMENT_ID     => $this->getCardNumber($card),
                Constants::MANDATE_HQ_INSTRUMENT_EXPIRY => $card->getExpiryMonth() . '/' . substr($card->getExpiryYear(), -2),
                Constants::MANDATE_HQ_INSTRUMENT_METHOD => Constants::MANDATE_HQ_INSTRUMENT_METHOD_CARD,
                Constants::MANDATE_HQ_INSTRUMENT_TYPE   => Constants::MANDATE_HQ_INSTRUMENT_TYPE_CARD
            ],
            Constants::MANDATE_HQ_MERCHANT                => $payment->merchant->getName(),
            Constants::MANDATE_HQ_MAX_AMOUNT              => $maxAmount,
            Constants::MANDATE_HQ_AMOUNT                  => $payment->getAmount(),
            Constants::MANDATE_HQ_CURRENCY                => $payment->getCurrency(),
            Constants::MANDATE_HQ_FREQUENCY               => Constants::MANDATE_HQ_FREQUENCY_AD_HOC,
            Constants::MANDATE_HQ_CALLBACK                => $url,
            Constants::MANDATE_HQ_END_TIME                => $endTime,
            Constants::MANDATE_HQ_DEBIT_TYPE              => Constants::MANDATE_HQ_DEBIT_TYPE_MAX_AMOUNT
        ];
    }

    protected function getCardNumber(Card\Entity $card)
    {
        $cardToken = $card->getCardVaultToken();

        return (new Card\CardVault)->getCardNumber($cardToken);
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

    protected function verifyHash(string $inputHash, string $paymentPublicId)
    {
        $expectedHash = $this->getHashOf($paymentPublicId);

        if (hash_equals($expectedHash, $inputHash) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Callback payment hash does not match. Please notify the admin of this error.');
        }
    }
}
