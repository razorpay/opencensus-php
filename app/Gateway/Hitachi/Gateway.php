<?php

namespace RZP\Gateway\Hitachi;

use App;
use Queue;

use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Gateway\Mpi;
use RZP\Models\Admin;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BharatQr;
use RZP\Models\Terminal;
use RZP\Gateway\Paysecure;
use RZP\Constants\HashAlgo;
use RZP\Constants\Timezone;
use RZP\Models\Card\Network;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Mpi\Base\Eci;
use RZP\Constants\Entity as E;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Models\Payment\Verify\Action;
use RZP\Reconciliator\RequestProcessor;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Reconciliator\Base\Reconciliate;

class Gateway extends Base\Gateway
{
    use Base\CardCacheTrait;
    use Base\AuthorizeFailed;
    use Base\GatewayTerminalTrait;

    protected $gateway = 'hitachi';

    protected $secureCacheDriver;

    const CACHE_KEY = 'hitachi_%s_card_details';
    const CARD_CACHE_TTL = 20;
    const TIME_FORMAT               = 'His';
    const DATE_FORMAT               = 'md';
    const DYNAMIC_DESCRIPTOR_PREFIX = 'RAZ*';
    const DEFAULT_CVV_VALUE         = '000';

    const STATUS                    = 'status';

    const PAYSECURE_MID_SWITCH_TIME = 1567612806; // 4 Sept 2019, 4:00 PM

    /*
    * Maintain a list of blacklisted MCCs (merchant categories) in the code(hard coded),
    * and skip Hitachi automatic onboarding for merchants belonging to these categories.
    * Use case is high-risk merchants, who should not be onboarded via Hitachi.
    */
    const BLACKLISTED_MCC = [
        '5962',
        '5966',
        '5967',
        '7995',
        '5912',
        '5122',
        '7273',
        '5993',
    ];

    protected $map = [
        ResponseFields::RETRIEVAL_REF_NUM   => Entity::RRN,
        ResponseFields::STATUS              => Entity::STATUS,
        ResponseFields::RESPONSE_CODE       => Entity::RESPONSE_CODE,
        ResponseFields::REQUEST_ID          => Entity::REQUEST_ID,
        ResponseFields::MERCHANT_REFERENCE  => Entity::MERCHANT_REFERENCE,
        ResponseFields::AUTH_ID             => Entity::AUTH_ID,
    ];

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->secureCacheDriver = $this->getDriver($input);
    }

    protected function getMappedAttributes($attributes)
    {
        $attr = [];

        $map = $this->map;

        foreach ($attributes as $key => $value)
        {
            if ((isset($value) === true) and
                ($value !== '') and
                (isset($map[$key])))
            {
                $newKey = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    public function otpGenerate(array $input)
    {
        if ((isset($input['otp_resend']) === true) and
            ($input['otp_resend'] === true))
        {
            return $this->otpResend($input);
        }

        return $this->authorize($input);
    }

    public function otpResend(array $input)
    {
        parent::action($input, Base\Action::OTP_RESEND);

        $mpiEntity = $this->app['repo']
                          ->mpi
                          ->findByPaymentIdAndActionOrFail($input['payment']['id'], Base\Action::AUTHORIZE);

        if ($mpiEntity->getGateway() !== Payment\Gateway::MPI_ENSTAGE)
        {
            //
            // This error is consistent with error thrown in otpResend trait
            throw new Exception\LogicException(
                'Gateway does not support OTP resend',
                null,
                ['payment_id' => $input['payment']['id']]);
        }

        $authenticationGateway = $mpiEntity->getGateway();

        $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

        return $authResponse;
    }

    protected function isFirstRecurringMcPaymentRequest($input)
    {
        if (($input['payment']['recurring'] === true) and
            ($input['payment']['recurring_type'] === 'initial') and
            ($input['card']['network_code']  === Card\Network::MC))
        {
            return true;
        }
        return false;
    }

    public function authorize(array $input)
    {
        parent::action($input, Base\Action::AUTHORIZE);

        if ($this->isBharatQrPayment() === true)
        {
            $this->createGatewayPaymentEntityForQr($input);

            return null;
        }

        if ($this->isSecondRecurringPaymentRequest($input) === true)
        {
            return $this->authorizeRecurring($input);
        }

        if ($this->isMotoTransactionRequest($input) === true)
        {
            return $this->authorizeMoto($input);
        }

        $authenticationGateway = $this->decideAuthenticationGateway($input);

        $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

        if ($authResponse !== null)
        {
            $storeCvv = ($this->isRupayTransaction($input) === false);

            // 1. For all transactions other than Rupay, we need to store it in cache
            // 2. For Rupay transactions, store it ONLY if we're not storing card in vault
            if (($this->isRupayTransaction($input) === false) or
                (empty($input['card']['vault_token']) === true))
            {
                $this->persistCardDetailsTemporarily($input, $storeCvv);
            }

            return $authResponse;
        }

        // Risk validation for international payments after authentication response 'N'
        if (isset($input['payment_analytics']['risk_score']) === true )
        {
            if (($input['payment_analytics']['risk_engine'] === Payment\Analytics\Metadata::SHIELD_V2) or
                ($input['payment_analytics']['risk_engine'] === Payment\Analytics\Metadata::MAXMIND_V2))
            {
                $this->validateRiskScore($input);
            }
        }

        return $this->authorizeNotEnrolled($input);
    }

    public function callbackOtpSubmit(array $input)
    {
        return $this->callback($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        if ($this->isRupayTransaction($input) === true)
        {
            $callbackData = $this->callAuthenticationGateway($input, Payment\Gateway::PAYSECURE);

            return $callbackData;
        }

        $mpiEntity = $this->app['repo']
                          ->mpi
                          ->findByPaymentIdAndActionGetLastOrFail($input['payment']['id'], Base\Action::AUTHORIZE);

        $authenticationGateway = $mpiEntity->getGateway() ?: Payment\Gateway::MPI_BLADE;

        $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

        $this->setCardNumberAndCvv($input);

       //Risk validation for international payments with Pares response as 'A'
        if (isset($input['payment_analytics']['risk_score']) === true)
        {
            if (($input['payment_analytics']['risk_engine'] === Payment\Analytics\Metadata::SHIELD_V2) or
                ($input['payment_analytics']['risk_engine'] === Payment\Analytics\Metadata::MAXMIND_V2))
            {
                $this->decideRiskValidationStep($input, $authResponse);
            }
        }

        $gatewayEntity = $this->authorizeEnrolled($input, $authResponse);

        $acquirerData = $this->getAcquirerData($input, $gatewayEntity);

        // TODO: Add authenticate data for 2FA
        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        if ($this->isRupayTransaction($input) === true)
        {
            $this->call(Base\Action::ADVICE, $input);

            return;
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                            $input['payment']['id'], Base\Action::AUTHORIZE);

        $request = $this->getCaptureRequestArray($input, $gatewayPayment);

        $inputTrace = $input;

        unset($inputTrace['email'], $inputTrace['password']);

        $this->traceGatewayPaymentRequest($request, $inputTrace, TraceCode::PAYMENT_CAPTURE_REQUEST);

        $captureEntity = $this->createGatewayPaymentEntity($input, [], Base\Action::CAPTURE);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_CAPTURE_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $attributes[Entity::RECEIVED] = true;

        $captureEntity->fill($attributes);

        $this->repo->saveOrFail($captureEntity);

        $this->checkErrorsAndThrowException($response);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $request = $this->getRefundRequestArray($input);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $refundEntity = $this->createGatewayRefundEntity($input);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $attributes = $this->getAttributesFromRefundReverseResponse($response);

        $this->updateGatewayRefundEntity($refundEntity, $attributes, false);

        $this->checkErrorsAndThrowException($response);

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
        ];
    }

    public function reverse(array $input)
    {
        parent::reverse($input);

        $repo = $this->repo;

        if ($this->isRupayTransaction($input) === true)
        {
            $repo = $this->app['repo']->paysecure;
        }

        $gatewayPayment = $repo->findByPaymentIdAndActionOrFail(
                            $input['payment']['id'], Base\Action::AUTHORIZE);

        $reverseEntity = $this->createGatewayPaymentEntity($input, [], Base\Action::REVERSE);

        $request = $this->getReverseRequestArray($input, $gatewayPayment);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REVERSE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_REVERSE_RESPONSE);

        $attributes = $this->getAttributesFromRefundReverseResponse($response);

        $reverseEntity->fill($attributes);

        $this->repo->saveOrFail($reverseEntity);

        $this->checkErrorsAndThrowException($response);

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
        ];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        // For RuPay transactions, route it to PaySecure
        if ($this->isRupayTransaction($input) === true)
        {
            try
            {
                return $this->app['gateway']->call(
                    Payment\Gateway::PAYSECURE,
                    $this->action,
                    $input,
                    $this->mode);
            }
            catch (Exception\PaymentVerificationException $e)
            {
                // If a terminal mode is purchase, we send the advice message during the processing of callback
                // But, in the case of late authorized payments, this callback would not be processed and hence
                // advice messages would not be sent
                // Hence, in this case(ie, gatewaySuccess is true, but apiSuccess is false), during verify
                // we need to send an advice message separately before throwing the exception to the api side
                $verify = $e->getVerifyObject();

                if (($verify->gatewaySuccess === true) and
                    ($verify->apiSuccess === false) and
                    ($this->input['terminal']['mode'] === Terminal\Mode::PURCHASE)
                )
                {
                    $this->call(Base\Action::ADVICE, $this->input);
                }

                throw $e;
            }
        }

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function preProcessServerCallback($input, $isBharatQr = false): array
    {
        if ($isBharatQr === true)
        {
            $input = $this->processInput($input);

            $qrData = $this->validateChecksumAndGetQrData($input);

            return [
                'qr_data'           => $qrData,
                'callback_data'     => $input,
            ];
        }

        return $input;
    }

    public function getStringToHashForBharatQr($content)
    {
        $salt = $this->config['bharatqr_salt'];

        $stringToHash =  urldecode(http_build_query($content));

        return $salt . '|' . $stringToHash . '&';
    }

    public function getHashOfString($str)
    {
        return hash(HashAlgo::SHA256, $str);
    }

    protected function validateChecksumAndGetQrData($input)
    {
        //
        // While trying to create unexpected Hitachi BQR payment
        // via recon (dashboard file upload by FinOps), we don't
        // have checksum. So we use this flag $isReconRunning to decide
        // whether to skip or continue with the checksum validation.
        //
        // In normal flow when actual callback comes from outside,
        // $isReconRunning will be false and checksum will be validated.
        //

        if (Reconciliate::$isReconRunning === false)
        {
            $actualChecksum = array_pull($input, ResponseFields::CHECKSUM);

            $hashString = $this->getStringToHashForBharatQr($input);

            $expectedChecksum = $this->getHashOfString($hashString);

            $this->compareHashes($actualChecksum, $expectedChecksum);
        }

        $this->checkForBharatQrFailure($input);

        $maskedPan = $input[ResponseFields::MASKED_CARD_NUMBER];

        $formattedAmount = $this->getIntegerFormattedAmount($input[ResponseFields::AMOUNT]);

        $cardBin = substr($maskedPan, 0, 6);

        $cardNetwork = Network::detectNetwork($cardBin);

        $qrData = [
            BharatQr\GatewayResponseParams::AMOUNT                => $formattedAmount,
            BharatQr\GatewayResponseParams::CARD_FIRST6           => $cardBin,
            BharatQr\GatewayResponseParams::CARD_LAST4            => substr($maskedPan, -4),
            BharatQr\GatewayResponseParams::SENDER_NAME           => $input[ResponseFields::SENDER_NAME],
            BharatQr\GatewayResponseParams::METHOD                => Payment\Method::CARD,
            BharatQr\GatewayResponseParams::GATEWAY_MERCHANT_ID   => $input[ResponseFields::MID],
            BharatQr\GatewayResponseParams::PROVIDER_REFERENCE_ID => $input[ResponseFields::RRN],
        ];

        // The purchase field can come with any number of zeroes prepended to it based on network and issuer.
        // We are trimming all left zeroes to ensure our exact qr code id is picked up
        switch ($cardNetwork)
        {
            case Network::VISA :
                $trimmedQrId = ltrim($input[ResponseFields::PURCHASE_ID], '0');
                $merchantReference = substr($trimmedQrId, 0, 14);
                break;

            default :
                $merchantReference = substr($input[ResponseFields::PURCHASE_ID], 0, 14);
                break;
        }

        $qrData[BharatQr\GatewayResponseParams::MERCHANT_REFERENCE] = $merchantReference;

        return $qrData;
    }

    protected function checkForBharatQrFailure($input)
    {
        if ($input[ResponseFields::STATUS_CODE] !== Status::SUCCESS_CODE)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_BQR_PAYMENT_FAILED,
                null,
                null,
                [
                    'notification_request' => $input,
                    'gateway'              => $this->gateway
                ]);
        }
    }

    protected function getIntegerFormattedAmount(string $amount)
    {
        return (int) number_format($amount, 0, '.', '');
    }

    protected function createGatewayPaymentEntityForQr($input)
    {
        $attributes = $this->getAttributesForQrResponse($input);

        $payment = $this->createGatewayPaymentEntity($input, $attributes);

        return $payment;
    }

    /**
     * Hitachi does not do to the authentication step of the payment itself.
     * It calls Blade to authenticate, then uses the response to authorize.
     *
     * @param array $input
     * @return array|null
     */
    protected function callAuthenticationGateway(array $input, $authenticationGateway)
    {
        return $this->app['gateway']->call(
            $authenticationGateway,
            $this->action,
            $input,
            $this->mode);
    }

    /**
     * For certain flows (like Axis Expresspay), Enstage should be used for authentication.
     * In such cases, the authentication gateway should be set to mpi_enstage.
     * Else Blade will be the default authentication gateway.
     *
     * @param $input
     * @return string
     */

    protected function decideAuthenticationGateway($input)
    {
        if ($this->isRupayTransaction($input) === true)
        {
            $authenticationGateway = Payment\Gateway::PAYSECURE;
        }
        else if ((isset($input['authenticate']['gateway']) === true) and
            ($input['authenticate']['gateway'] === Payment\Gateway::MPI_ENSTAGE))
        {
            $authenticationGateway = Payment\Gateway::MPI_ENSTAGE;
        }
        else
        {
            $authenticationGateway = Payment\Gateway::MPI_BLADE;
        }

        return $authenticationGateway;
    }

    protected function isRupayTransaction($input): bool
    {
        return (
            ($input['card'][Card\Entity::NETWORK_CODE] === Network::RUPAY) and
            (($input['payment'][Payment\Entity::METHOD] === Payment\Method::CARD) or
                ($input['payment'][Payment\Entity::METHOD] === Payment\Method::EMI)) and
            (empty($input['payment'][Payment\Entity::RECEIVER_TYPE]) === true)
        );
    }

    protected function authorizeMoto(array $input)
    {
        $request = $this->getAuthorizeRequestArrayForMoto($input);

        $hitachiEntity = $this->createGatewayPaymentEntity($input, [], Base\Action::AUTHORIZE);

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_MOTO_AUTH_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $attributes[Entity::RECEIVED] = true;

        $this->updateGatewayPaymentEntity($hitachiEntity, $attributes, false);

        $this->checkErrorsAndThrowException($response);

        return $this->getAcquirerData($input, $hitachiEntity);
    }

    // used only by paysecure authorized Rupay payment to capture payments
    public function advice(array $input)
    {
        parent::advice($input);

        $request = $this->getAdviceRequestArrayForPaysecure($input);

        $hitachiEntity = $this->createGatewayPaymentEntity($input, [], Base\Action::CAPTURE);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_PAYSECURE_AUTH_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $attributes[Entity::RECEIVED] = true;

        $this->updateGatewayPaymentEntity($hitachiEntity, $attributes, false);

        $this->checkErrorsAndThrowException($response);
    }

    protected function authorizeRecurring(array $input)
    {
        $request = $this->getAuthorizeRequestArrayForRecurring($input);

        $hitachiEntity = $this->createGatewayPaymentEntity($input, [], Base\Action::AUTHORIZE);

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_RECURRING_AUTH_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $attributes[Entity::RECEIVED] = true;

        $this->updateGatewayPaymentEntity($hitachiEntity, $attributes, false);

        $this->checkErrorsAndThrowException($response);
    }

    protected function authorizeNotEnrolled(array $input)
    {
        $request = $this->getAuthorizeRequestArrayForNotEnrolled($input);

        $hitachiEntity = $this->createGatewayPaymentEntity($input, [], Base\Action::AUTHORIZE);

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $attributes[Entity::RECEIVED] = true;

        $this->updateGatewayPaymentEntity($hitachiEntity, $attributes, false);

        $this->checkErrorsAndThrowException($response);
    }

    protected function authorizeEnrolled(array $input, array $authResponse)
    {
        $request = $this->getAuthorizeRequestArrayForEnrolled($input, $authResponse);

        $gatewayEntity = $this->createGatewayPaymentEntity($input,[],Base\Action::AUTHORIZE);

        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $attributes[Entity::RECEIVED] = true;

        $gatewayEntity->fill($attributes);

        $this->repo->saveOrFail($gatewayEntity);

        $this->checkErrorsAndThrowException($response);

        return $gatewayEntity;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        if ((empty($gatewayPayment->getRRN()) === true) and
            ($gatewayPayment->getResponseCode() === '30'))
        {
            $verify->apiStatus = false;

            $verify->gatewayStatus = false;

            $verify->match = true;

            return [];
        }

        $request = $this->getVerifyRequestArray($input, $gatewayPayment, 'payment');

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $response;
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new Base\ScroogeResponse();

        $verifyRefundRequest = $this->getVerifyRequestArray($input, null, 'refund');

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'request'     => $verifyRefundRequest,
                'gateway'     => $this->gateway,
                'refund_id'   => $input['refund']['id'],
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        $verifyRefundResponse = $this->sendGatewayRequest($verifyRefundRequest);

        $scroogeResponse->setGatewayVerifyResponse($verifyRefundResponse)
                        ->setGatewayKeys($this->getGatewayData($verifyRefundResponse));

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'response'    => $verifyRefundResponse,
                'gateway'     => $this->gateway,
                'refund_id'   => $input['refund']['id'],
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        if ((isset($verifyRefundResponse[ResponseFields::STATUS]) === true) and
            ($verifyRefundResponse[ResponseFields::STATUS] === Status::SUCCESS))
        {
            $this->checkErrorsAndThrowException($verifyRefundResponse);

            $gatewayEntity = $this->repo->findByRefundId($input['refund']['id']);

            $attributes = $this->getMappedAttributes($verifyRefundResponse);

            if ($gatewayEntity !== null)
            {
                $gatewayEntity->fill($attributes);

                $this->repo->saveOrFail($gatewayEntity);
            }
            else
            {
                $this->createGatewayRefundEntity($input, $attributes, 'refund');
            }

            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                               ->toArray();
    }

    protected function verifyPayment(Verify $verify)
    {
        $verify->payment = $this->saveVerifyResponseIfNeeded($verify);

        $this->checkResponseAndThrowExceptionIfRequired($verify);

        $this->setVerifyStatus($verify);
    }

    protected function checkResponseAndThrowExceptionIfRequired($verify)
    {
        $content = $verify->verifyResponseContent;

        /**
         * For response codes 05, 51, N7 , we are confirmed that the payment has failed. So there is no point calling
         * verify for such payments repeatedly.
         */

        $definiteErrorCodes = ['05', '51', 'N7'];

        if ((empty($content[ResponseFields::RESPONSE_CODE]) === false) and
            (in_array($content[ResponseFields::RESPONSE_CODE], $definiteErrorCodes)) === true)
        {
            throw new Exception\PaymentVerificationException(
                $verify->getDataToTrace(),
                $verify,
                Action::FINISH);
        }
    }

    protected function setVerifyStatus(Verify $verify)
    {
        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MISMATCH;

        if ($verify->apiSuccess === $verify->gatewaySuccess)
        {
            $status = VerifyResult::STATUS_MATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $verify->status = $status;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        // TODO: Need to confirm that it is S or RespCode 00
        if ((isset($content[ResponseFields::STATUS])) and
            ($content[ResponseFields::STATUS] === Status::SUCCESS) and
            ($content[ResponseFields::RESPONSE_CODE] === Status::SUCCESS_CODE))
        {
            $verify->gatewaySuccess = true;
        }
    }

    public function alreadyRefunded(array $input)
    {
        $paymentId = $input['payment_id'];
        $refundAmount = $input['refund_amount'];
        $refundId = $input['refund_id'];

        $refundedEntities = $this->repo->findSuccessfulRefundByRefundId($refundId);

        if ($refundedEntities->count() === 0)
        {
            return false;
        }

        $refundEntity = $refundedEntities->first();

        $refundEntityPaymentId = $refundEntity->getPaymentId();
        $refundEntityRefundAmount = $refundEntity->getAmount();

        $this->trace->info(
            TraceCode::GATEWAY_ALREADY_REFUNDED_INPUT,
            [
                'input'                 => $input,
                'refund_payment_id'     => $refundEntityPaymentId,
                'gateway_refund_amount' => $refundEntityRefundAmount
            ]);

        if (($refundEntityPaymentId !== $paymentId) or
            ($refundEntityRefundAmount !== $refundAmount))
        {
            return false;
        }

        return true;
    }

    // ----------------------------------------- Request Arrays --------------------------------------------------------

    protected function getAuthorizeRequestArrayForEnrolled(array $input, array $authResponse)
    {
        $content = $this->getDefaultAuthorizeRequestArray($input);

        $content[RequestFields::AUTH_STATUS] = $authResponse[Mpi\Base\Entity::STATUS];
        $content[RequestFields::ECI]         = $authResponse[Mpi\Base\Entity::ECI];
        $content[RequestFields::XID]         = $authResponse[Mpi\Base\Entity::XID];
        $content[RequestFields::ALGORITHM]   = $authResponse[Mpi\Base\Entity::CAVV_ALGORITHM];

        $networkCode = $this->input['card']['network_code'];

        if ($networkCode === Card\Network::VISA)
        {
            $content[RequestFields::CAVV2] = $authResponse[Mpi\Base\Entity::CAVV];
        }
        else if (($networkCode === Card\Network::MC) or ($networkCode === Card\Network::MAES))
        {
            $content[RequestFields::UCAF] = $authResponse[Mpi\Base\Entity::CAVV];
        }
        else
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID);
        }

        $traceContent = $content;

        $content += $this->getCardDataForAuthorizeRequestArray($input);

        $request = $traceRequest = $this->getStandardRequestArray($content);

        $traceRequest['content'] = $traceContent;

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => 'hitachi',
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        return $request;
    }

    protected function getAuthorizeRequestArrayForRecurring(array $input)
    {
        $content = $this->getDefaultAuthorizeRequestArray($input);

        $content[RequestFields::TRANSACTION_TYPE] = 'SI';

        $networkCode = $input['card']['network_code'];

        if (($networkCode === Card\Network::VISA) or
            ($networkCode === Card\Network::MC))
        {
            $content[RequestFields::ECI] = Eci::getEciValueforSI($networkCode);
        }
        else
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID);
        }

        $traceContent = $content;

        $content += $this->getCardDataForAuthorizeRequestArray($input);

        $request = $traceRequest = $this->getStandardRequestArray($content);

        $traceRequest['content'] = $traceContent;

        $this->trace->info(TraceCode::GATEWAY_RECURRING_AUTH_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => 'hitachi',
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        return $request;
    }

    protected function getAuthorizeRequestArrayForMoto(array $input)
    {
        $content = $this->getDefaultAuthorizeRequestArray($input);

        $content[RequestFields::TRANSACTION_TYPE] = TransactionType::MOTO;

        $networkCode = $input['card']['network_code'];

        $content[RequestFields::ECI] = Eci::getEciValueForMoto($networkCode);

        $content[RequestFields::AUTH_STATUS] = Mpi\Base\AuthenticationStatus::N;

        $traceContent = $content;

        $content += $this->getCardDataForAuthorizeRequestArray($input);

        $request = $traceRequest = $this->getStandardRequestArray($content);

        $traceRequest['content'] = $traceContent;

        $this->trace->info(TraceCode::GATEWAY_MOTO_AUTH_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => 'hitachi',
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        return $request;
    }

    protected function getAdviceRequestArrayForPaysecure(array $input)
    {
        $content = $this->getDefaultAuthorizeRequestArray($input);

        $content[RequestFields::TERMINAL_ID] = $this->getTerminalId();

        $content[RequestFields::TRANSACTION_TYPE] = TransactionType::RUPAY;

        $content[RequestFields::ECI] = '07';

        $paysecureEntity = $this->app['repo']->paysecure->findByPaymentIdAndActionOrFail($input['payment']['id'], Base\Action::AUTHORIZE);

        $content[RequestFields::TRANSACTION_DATE] = $paysecureEntity['tran_date'];

        $content[RequestFields::TRANSACTION_TIME] = $paysecureEntity['tran_time'];

        $content[RequestFields::AUTH_ID] = $paysecureEntity['apprcode'];

        $content[RequestFields::RETRIEVAL_REF_NUM] = $paysecureEntity['rrn'];

        // Use 6012 in UAT
        $mcc = (($this->mode === Mode::TEST) ? '6012' : ($this->input['merchant']['category']));

        $content[RequestFields::MCC] = Paysecure\Mcc::getMappedMcc($mcc);

        $traceContent = $content;

        $content += $this->getCardDataForAuthorizeRequestArray($input);

        $request = $traceRequest = $this->getStandardRequestArray($content);

        $traceRequest['content'] = $traceContent;

        $this->trace->info(TraceCode::GATEWAY_PAYSECURE_AUTH_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => 'hitachi',
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        return $request;
    }

    protected function getAuthorizeRequestArrayForNotEnrolled(array $input)
    {
        $content = $this->getDefaultAuthorizeRequestArray($input);

        $networkCode = $input['card']['network_code'];

        $eciValues = [
            Card\Network::VISA => '07',
            Card\Network::MAES => '00',
            Card\Network::MC   => '00',
        ];

        $content[RequestFields::ECI] = $eciValues[$networkCode];

        $traceContent = $content;

        $content += $this->getCardDataForAuthorizeRequestArray($input);

        $request = $traceRequest = $this->getStandardRequestArray($content);

        $traceRequest['content'] = $traceContent;

        $this->trace->info(TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => 'hitachi',
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        return $request;
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->trace->info($traceCode,
            [
                'request'     => $request,
                'gateway'     => 'hitachi',
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);
    }

    protected function traceGatewayPaymentResponse(
        $response,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_RESPONSE)
    {
        unset($response[ResponseFields::CARD_NUMBER]);

        $this->trace->info($traceCode,
            [
                'response'    => $response,
                'gateway'     => 'hitachi',
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);
    }

    protected function getDefaultAuthorizeRequestArray(array $input)
    {
        $time = Carbon::now(Timezone::IST)->format(self::TIME_FORMAT);
        $date = Carbon::now(Timezone::IST)->format(self::DATE_FORMAT);

        $currencyCode = Currency::getIsoCode($input['payment']['currency']);
        $dynamicMerchantName = self::DYNAMIC_DESCRIPTOR_PREFIX . $this->getDynamicMerchantName($input['merchant'], 16);

        $content = [
            RequestFields::TRANSACTION_TYPE         => TransactionType::AUTH,
            RequestFields::TRANSACTION_AMOUNT       => $this->getFormattedAmount($input['payment']['amount']),
            RequestFields::TRANSACTION_TIME         => $time,
            RequestFields::TRANSACTION_DATE         => $date,
            RequestFields::MERCHANT_ID              => $this->getMerchantId(),
            RequestFields::MERCHANT_REF_NUMBER      => $input['payment']['id'],
            RequestFields::AUTH_STATUS              => '',
            RequestFields::ECI                      => '',
            RequestFields::XID                      => '',
            RequestFields::ALGORITHM                => '',
            RequestFields::CAVV2                    => '',
            RequestFields::UCAF                     => '',
            RequestFields::CURRENCY_CODE            => $currencyCode,
        ];

//        if ((bool) Admin\ConfigKey::get(Admin\ConfigKey::HITACHI_DYNAMIC_DESCR_ENABLED, false) === true)
//        {
//            $content[RequestFields::DYNAMIC_MERCHANT_NAME] = $dynamicMerchantName;
//        }

        if ($this->isFirstRecurringMcPaymentRequest($input) === true)
        {
            //ToDo:Fix this after 3DS 2 is live.
            $content[RequestFields::MC_PROTOCOL_VERSION] = 1;
            // $content[RequestFields::MC_DS_TRANSACTION_ID] = $mcProtocolVersion;
        }

        return $content;
    }

    protected function getCardDataForAuthorizeRequestArray(array $input)
    {
        $card = $input['card'];

        $expiry = substr($card['expiry_year'], 2) . str_pad($card['expiry_month'], 2, '0', STR_PAD_LEFT);

        if ($this->isRupayTransaction($input) === true)
        {
            if (empty($card['vault_token']) === false)
            {
                $input['card']['number'] = (new Card\CardVault)->getCardNumber($card['vault_token']);
            }
            else
            {
                $this->setCardNumberAndCvv($input);
            }

            $data = [
                RequestFields::CARD_NUMBER         => $input['card']['number'],
                RequestFields::EXPIRY_DATE         => $expiry,
                RequestFields::CVV2                => self::DEFAULT_CVV_VALUE,
            ];
        }
        else
        {
            $data = [
                RequestFields::CARD_NUMBER         => $input['card']['number'],
                RequestFields::EXPIRY_DATE         => $expiry,
            ];
        }

        if (($this->isSecondRecurringPaymentRequest($input) === false) and
            ($this->isMotoTransactionRequest($input) === false) and
            ($this->isRupayTransaction($input) === false))
        {
            $data[RequestFields::CVV2] = $input['card']['cvv'];
        }

        return $data;
    }

    protected function getCaptureRequestArray(array $input, Entity $gatewayPayment)
    {
        $time = Carbon::now(Timezone::IST)->format(self::TIME_FORMAT);
        $date = Carbon::now(Timezone::IST)->format(self::DATE_FORMAT);

        $content = [
            RequestFields::TRANSACTION_TYPE    => TransactionType::CAPTURE,
            RequestFields::REQUEST_ID          => UniqueIdEntity::generateUniqueId(),
            RequestFields::TRANSACTION_AMOUNT  => $this->getFormattedAmount($input['payment']['amount']),
            RequestFields::TRANSACTION_TIME    => $time,
            RequestFields::TRANSACTION_DATE    => $date,
            RequestFields::RETRIEVAL_REF_NUM   => $gatewayPayment->getRrn(),
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::MERCHANT_REF_NUMBER => $gatewayPayment->getMerchantReference() ?? $input['payment']['id'],
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getRefundRequestArray(array $input)
    {
        if ($this->isRupayTransaction($input) === true)
        {
            return $this->getPaysecureRefundRequestArray($input);
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Base\Action::AUTHORIZE);

        $createdAt = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST);

        $time = $createdAt->format(self::TIME_FORMAT);
        $date = $createdAt->format('dmY');

        $content = [
            RequestFields::TRANSACTION_TYPE    => TransactionType::REFUND,
            RequestFields::TRANSACTION_AMOUNT  => $this->getFormattedAmount($input['refund']['amount']),
            RequestFields::TRANSACTION_TIME    => $time,
            RequestFields::TRANSACTION_DATE    => $date,
            RequestFields::RETRIEVAL_REF_NUM   => $gatewayPayment->getRrn(),
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::TERMINAL_ID         => $this->getTerminalId(),
            RequestFields::MERCHANT_REF_NUMBER => $input['refund']['id'],
            RequestFields::REQUEST_ID          => UniqueIdEntity::generateUniqueId(),
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getPaysecureRefundRequestArray(array $input)
    {
        $gatewayPayment = $this->app['repo']->paysecure->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Base\Action::AUTHORIZE);

        $createdAt = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST);

        $time = $createdAt->format(self::TIME_FORMAT);
        $date = $createdAt->format('dmY');

        $content = [
            RequestFields::TRANSACTION_TYPE    => TransactionType::REFUND,
            RequestFields::TRANSACTION_AMOUNT  => $this->getFormattedAmount($input['refund']['amount']),
            RequestFields::TRANSACTION_TIME    => $time,
            RequestFields::TRANSACTION_DATE    => $date,
            RequestFields::RETRIEVAL_REF_NUM   => $gatewayPayment['rrn'],
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::TERMINAL_ID         => $this->getTerminalId(),
            RequestFields::MERCHANT_REF_NUMBER => $input['refund']['id'],
            RequestFields::REQUEST_ID          => UniqueIdEntity::generateUniqueId(),
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getReverseRequestArray(array $input, Base\Entity $gatewayPayment)
    {
        $createdAt = Carbon::createFromTimestamp($input['payment']['created_at'], Timezone::IST);

        $time = $createdAt->format(self::TIME_FORMAT);
        $date = $createdAt->format(self::DATE_FORMAT);

        $content = [
            RequestFields::TRANSACTION_TYPE    => TransactionType::VOID,
            RequestFields::TRANSACTION_AMOUNT  => $this->getFormattedAmount($input['refund']['amount']),
            RequestFields::TRANSACTION_TIME    => $time,
            RequestFields::TRANSACTION_DATE    => $date,
            RequestFields::RETRIEVAL_REF_NUM   => $gatewayPayment->getRrn(),
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::MERCHANT_REF_NUMBER => $input['payment']['id'],
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function getVerifyRequestArray(array $input, $gatewayPayment, $entity)
    {
        $content = [
            RequestFields::TRANSACTION_TYPE    => TransactionType::VERIFY,
            RequestFields::REQUEST_ID          => UniqueIdEntity::generateUniqueId(),
            RequestFields::TRANSACTION_AMOUNT  => $this->getFormattedAmount($input[$entity]['amount']),
            RequestFields::MERCHANT_ID         => $this->getMerchantId(),
            RequestFields::TERMINAL_ID         => $this->getTerminalId(),
            RequestFields::MERCHANT_REF_NUMBER => $input[$entity]['id']
        ];


        if (($entity === 'payment') and ($this->isBharatQrPayment() === true))
        {
            $content[RequestFields::MERCHANT_REF_NUMBER] = $gatewayPayment->getRrn();
        }

        return $this->getStandardRequestArray($content);
    }

    protected function getFormattedAmount($amount)
    {
        return str_pad($amount, 12, '0', STR_PAD_LEFT);
    }

    // ----------------------------------------- Get Attributes --------------------------------------------------------

    protected function getAttributesForQrResponse(array $response)
    {
        $attributes = [
            Entity::RECEIVED           => true,
            Entity::MASKED_CARD_NUMBER => $response[ResponseFields::MASKED_CARD_NUMBER],
            Entity::CARD_NETWORK       => $response[ResponseFields::CARD_NETWORK],
            Entity::AMOUNT             => $this->getIntegerFormattedAmount($response[ResponseFields::AMOUNT]),
            Entity::RRN                => $response[ResponseFields::RRN],
            Entity::AUTH_ID            => $response[ResponseFields::AUTHORIZATION_ID],
            Entity::STATUS             => $response[ResponseFields::STATUS_CODE],
            Entity::MERCHANT_REFERENCE => substr($response[ResponseFields::PURCHASE_ID], 0 , 14),
        ];

        return $attributes;
    }

    protected function getAttributesFromRefundReverseResponse(array $response) : array
    {
        if ((isset($response['response_code']) === true) and
            ($response['response_code'] === '30'))
        {
            $attributes = [
                Entity::RRN           => $response[ResponseFields::RETRIEVAL_REF_NUM] ?? null,
                Entity::RESPONSE_CODE => $response[ResponseFields::RESPONSE_CODE] ?? $response['response_code'],
            ];
        }
        else
        {
            $attributes = [
                Entity::RRN           => $response[ResponseFields::RETRIEVAL_REF_NUM] ?? null,
                Entity::RESPONSE_CODE => $response[ResponseFields::RESPONSE_CODE],
            ];
        }

        $attributes += $this->getMappedAttributes($response);

        return $attributes;
    }

    protected function saveVerifyResponseIfNeeded(Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        if (isset($content[ResponseFields::STATUS]))
        {
            $gatewayAttributes = $this->getMappedAttributes($content);

            $gatewayPayment->fill($gatewayAttributes);

            $this->repo->saveOrFail($gatewayPayment);
        }

        return $gatewayPayment;
    }

    // ----------------------------------------- Gateway Payment -------------------------------------------------------

    protected function createGatewayPaymentEntity(array $input, array $attributes = [], $action = null)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $action = $action ?: $this->action;

        $acquirer = $input['terminal']->getGatewayAcquirer();

        $gatewayPayment->setAcquirer($acquirer);

        $gatewayPayment->setAction($action);

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->setCurrency($input['payment']['currency']);

        $gatewayPayment->setAmount($input['payment']['amount']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity(array $input, array $attributes = [], $action = null)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        if (isset($input['terminal']) === true)
        {
            $acquirer = $input['terminal']->getGatewayAcquirer();

            $gatewayPayment->setAcquirer($acquirer);
        }

        $action = $action ?: $this->action;

        $gatewayPayment->setAction($action);

        $gatewayPayment->setPaymentId($input['payment']['id']);

        $gatewayPayment->setRefundId($input['refund']['id']);

        $gatewayPayment->setCurrency($input['payment']['currency']);

        $gatewayPayment->setAmount($input['refund']['amount']);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function updateGatewayRefundEntity(
        Entity $gatewayPayment,
        array $attributes,
        bool $mapped = true)
    {
        if ($mapped === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    // --------------------------------------------- Helpers------------------------------------------------------------

    protected function checkErrorsAndThrowException(array $response)
    {
        $respCode = '';

        if (isset($response[ResponseFields::RESPONSE_CODE]) === true)
        {
            $respCode = $response[ResponseFields::RESPONSE_CODE];
        }
        else if (isset($response[ResponseFields::FAILED_RESPONSE_CODE]) === true)
        {
            $respCode = $response[ResponseFields::FAILED_RESPONSE_CODE];

            $response[ResponseFields::RESPONSE_CODE] = $respCode;
        }

        $responseKey = ($this->action === Base\Action::VERIFY) ? Payment\Gateway::GATEWAY_VERIFY_RESPONSE :
                                                                    Payment\Gateway::GATEWAY_RESPONSE;

        $errorCode = ErrorCodes\ErrorCodes::getInternalErrorCode($response);

        $message = ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($response);

        unset($response[ResponseFields::CARD_NUMBER]);

        if ($respCode !== Status::SUCCESS_CODE)
        {
            throw new Exception\GatewayErrorException(
                $errorCode,
                $respCode,
                $message,
                [
                    $responseKey                  => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS => $this->getGatewayData($response)
                ]);
        }
    }

    protected function getStringToHash($content, $glue = '')
    {
        $array = [
            $this->getSecret(),
            $content[RequestFields::MERCHANT_REF_NUMBER],
            $this->getMerchantId(),
            $this->getSecret2()
        ];

        return parent::getStringToHash($array, $glue);
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $body = json_encode($content);

        $request = parent::getStandardRequestArray($body, $method, $type);

        $request['headers']['checksum'] = $this->generateHash($content);
        $request['headers']['Content-Type'] = 'application/json';

        $request['options'] = [
            'timeout'         => 60,
            'connect_timeout' => 10,
            'verify'          => false,
        ];

        return $request;
    }

    protected function sendGatewayRequest($request)
    {
        $response = parent::sendGatewayRequest($request);

        $body = $response->body;

        return $this->parseResponseBody($body);
    }

    protected function parseResponseBody(string $body)
    {
        // TODO: Ask hitachi to fix it.
        try
        {
            $responseArray = $this->jsonToArray($body);
        }
        catch (Exception\RuntimeException $e)
        {
            // This happens because gateway return two json instead of one
            // Till they fix this, we need to apply this hack

            try
            {
                $explodedbody = explode('}', $body)[0] . '}';

                $responseArray = $this->jsonToArray($explodedbody);
            }
            catch (\Exception $e)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_INVALID_JSON,
                    null,
                    'Failed to convert json to array',
                    [
                        'json' => $body,
                    ],
                    $e);
            }

        }

        return $responseArray;
    }

    protected function getUrl($type = null)
    {
        if ($this->isLiveMode() === true)
        {
            if (($this->isBharatQrPayment() === true) and ($this->action === Base\Action::VERIFY))
            {
                return 'https://172.16.18.40:10010/RZPTSAPI/PaymentGateway.aspx';
            }

            else
            {
                return 'https://172.16.18.40:10010/PaymentGateway.aspx';
            }

//            if ((bool) Admin\ConfigKey::get(Admin\ConfigKey::HITACHI_NEW_URL_ENABLED, false) === true)
//            {
//                return 'https://172.18.24.213:10010/PaymentGateway.aspx';
//            }
//            else
//            {
//
//            }
        }

        return constant(Url::class . '::' . strtoupper($this->mode));
    }

    protected function getMerchantId()
    {
        if (($this->isRupayTransaction($this->input) === true) and
            ($this->input['payment'][Payment\Entity::CREATED_AT] <= self::PAYSECURE_MID_SWITCH_TIME))
        {
            return '38RR00000000001';
        }

        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }

    protected function getTerminalId()
    {
        if (($this->isRupayTransaction($this->input) === true) and
            ($this->input['payment'][Payment\Entity::CREATED_AT] <= self::PAYSECURE_MID_SWITCH_TIME))
        {
            return '38R00001';
        }

        $terminalId = $this->terminal['gateway_terminal_id'];

        if ($this->mode === Mode::TEST)
        {
            $terminalId = $this->config['test_terminal_id'];
        }

        return $terminalId;
    }

    protected function getLiveSecret()
    {
        return $this->config['gateway_salt'];
    }

    protected function getSecret2()
    {
        $secret2 = $this->config['gateway_salt2'];

        if ($this->mode === Mode::TEST)
        {
            $secret2 = $this->config['test_hash_secret2'];
        }

        return $secret2;
    }

    protected function getCaInfo()
    {
        $clientCertPath = dirname(__FILE__) . '/cainfo/cainfo.pem';

        return $clientCertPath;
    }

    /*
     * Hitachi might send the request in a plain string format, to process the request we need an array.
     * If the input is a plain text with no header set, we get the content(body)
     * of the request and convert it into an array. For case where input header is set, input will come
     * in the form of an array.
     */

    protected function processInput($input)
    {
        if (empty($input['content']) === true)
        {
            $inputArray = [];

            // Hitachi has sent a plain text without header,
            // so parsing the request body into an array for processing
            parse_str($input['raw'], $inputArray);

            return $inputArray;
        }

        return $input['content'];
    }

    protected function getGatewayData(array $refundFields)
    {
        return [
            ResponseFields::REQUEST_ID        => $refundFields[ResponseFields::REQUEST_ID] ?? null,
            ResponseFields::MERCHANT_ID       => $refundFields[ResponseFields::MERCHANT_ID] ?? null,
            ResponseFields::RESPONSE_CODE     => $refundFields[ResponseFields::RESPONSE_CODE] ?? null,
            ResponseFields::TRANSACTION_TYPE  => $refundFields[ResponseFields::TRANSACTION_TYPE] ?? null,
            ResponseFields::RETRIEVAL_REF_NUM => $refundFields[ResponseFields::RETRIEVAL_REF_NUM] ?? null,
        ];
    }

    public function forceAuthorizeFailed(array $input)
    {
        if ($this->isRoutedThroughCardPayments($input))
        {
            return $this->forceAuthorizeFailedViaCps($input);
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Base\Action::AUTHORIZE);

        if (($gatewayPayment[Entity::RESPONSE_CODE] === Status::SUCCESS_CODE) and
            ($gatewayPayment[Entity::RECEIVED] === true))
        {
            return true;
        }

        $attr = [
            Entity::RRN                 =>  $input['gateway'][Entity::RRN],
            Entity::AUTH_ID             =>  $input['gateway'][Entity::AUTH_ID],
            Entity::MERCHANT_REFERENCE  =>  $input['gateway'][Entity::MERCHANT_REFERENCE],
            Entity::RESPONSE_CODE       =>  Status::SUCCESS_CODE,
            Entity::STATUS              =>  Status::SUCCESS,
        ];

        $gatewayPayment->fill($attr);

        $gatewayPayment->saveOrFail();

        return true;
    }

    public function isRoutedThroughCardPayments($input): bool
    {
        /**
         * This checks if the current request has to be routed to
         * card payment service or not.
         */
        if ((is_array($input) === true) and
            (isset($input[E::PAYMENT]) === true) and
            ($input[E::PAYMENT][Payment\Entity::CPS_ROUTE] === Payment\Entity::CARD_PAYMENT_SERVICE))
        {
            return true;
        }

        return false;
    }

    /*
     * For CPS payments, we dont have entry in Hitachi table.
     * so push the relevant param in the CPS queue to that
     * CPS service can mark the payment as authorized.
     */
    protected function forceAuthorizeFailedViaCps(array $input)
    {
        // Fetch auth response and check authorize status
        // in CPS gateway entity
        $paymentId = $input['payment']['id'];

        $request = [
            'fields'        => [self::STATUS],
            'payment_ids'   => [$paymentId],
        ];

        $this->trace->info(
            TraceCode::PAYMENT_RECON_QUEUE_CPS_REQUEST,
            $request
        );

        $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'     => InfoCode::CPS_RESPONSE_AUTHORIZATION_DATA,
                'response'      => $response,
            ]);

        if (empty($response[$paymentId]) === false)
        {
            if ($response[$paymentId][self::STATUS] === Status::FAILED)
            {
                // Push to queue in order to update/force auth
                // Note : This push part we can do in async way and
                // just return true here, as there is no failure case ahead.

                $entity = [
                    self::RRN           =>  $input['gateway'][Entity::RRN],
                    self::AUTH_CODE     =>  $input['gateway'][Entity::AUTH_ID],
                    self::RECON_ID      =>  $input['gateway'][Entity::MERCHANT_REFERENCE]
                ];

                $attr = [
                    self::PAYMENT_ID    =>  $paymentId,
                    self::ENTITY_TYPE   =>  self::GATEWAY,
                    self::GATEWAY       =>  $entity
                ];

                $queueName = $this->app['config']->get('queue.payment_card_api_reconciliation.' . $this->mode);

                Queue::pushRaw(json_encode($attr), $queueName);

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'info_code' => InfoCode::RECON_CPS_QUEUE_DISPATCH,
                        'message'   => 'Update gateway data in order to Force Authorize payment',
                        'payment_id'=> $paymentId,
                        'queue'     => $queueName,
                        'payload'   => json_encode($attr),
                    ]
                );
            }
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => InfoCode::CPS_PAYMENT_AUTH_DATA_ABSENT,
                    'payment_id'    => $paymentId,
                    'gateway'       => RequestProcessor\Base::HITACHI,
                ]);

            return false;
        }

        return true;
    }

    protected function getCacheKey($input)
    {
        if ((isset($input['card'][Card\Entity::NETWORK_CODE]) === true) and
            ($input['card'][Card\Entity::NETWORK_CODE] === Card\Network::RUPAY))
        {
            return sprintf(Paysecure\Gateway::CACHE_KEY, $input['payment']['id']);
        }

            return sprintf(static::CACHE_KEY, $input['payment']['id']);
    }

    protected function getCardCacheTtl($input)
    {
        if ((isset($input['card'][Card\Entity::NETWORK_CODE]) === true) and
            ($input['card'][Card\Entity::NETWORK_CODE] === Card\Network::RUPAY))
        {
            return 60 * 24 * 10;
        }

        return static::CARD_CACHE_TTL;
    }
}
