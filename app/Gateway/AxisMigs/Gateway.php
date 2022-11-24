<?php

namespace RZP\Gateway\AxisMigs;

use Str;
use Carbon\Carbon;
use \WpOrg\Requests\Hooks as Requests_Hooks;

use RZP\Diag\EventCode;
use RZP\Gateway\Mpi;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Constants\Mode;
use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Payment;
use RZP\Gateway\AxisMigs;
use RZP\Models\Payment\Processor\Notify;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use Base\CardCacheTrait;
    use Base\AuthorizeFailed;

    protected $gateway = 'axis_migs';

    protected $authorize = true;

    protected $secureCacheDriver;

    const CHECKSUM_ATTRIBUTE = 'vpc_SecureHash';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentAuthorizeRequestContent($input);

        $this->gatewayEntity = $this->createGatewayPaymentEntity($content, $input);

        $this->addSubMerchantDetails($content, $input);

        $content['vpc_SecureHash'] = $this->generateHash($content);
        $content['vpc_SecureHashType'] = strtoupper(HashAlgo::SHA256);

        if ($this->isSecondRecurringPaymentRequest($input) === true)
        {
            return $this->authorizeRecurring($content, $input);
        }

        $authenticationGateway = $this->decideAuthenticationGateway($input);

        switch ($authenticationGateway)
        {
            case Payment\Gateway::MPI_BLADE:
            case Payment\Gateway::MPI_ENSTAGE:
                $authResponse = $this->callAuthenticationGateway($input, $authenticationGateway);

                if ($authResponse === null)
                {
                    $this->traceGatewayPaymentRequest([], $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);
                }
                else
                {
                    $this->traceGatewayPaymentRequest($authResponse, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

                    $this->persistCardDetailsTemporarily($input);

                    return $authResponse;
                }

                return $this->authorizeNotEnrolled($content, $input);
                break;
            default:
                $request = $this->getAuthRequestArray($content);

                $traceRequest = $request;
                unset($traceRequest['content']['vpc_SecureHash']);
                unset($traceRequest['content']['vpc_SecureHashType']);
                unset($traceRequest['content']['vpc_Card']);
                unset($traceRequest['content']['vpc_CardNum']);
                unset($traceRequest['content']['vpc_CardExp']);
                unset($traceRequest['content']['vpc_CardSecurityCode']);
                unset($traceRequest['content']['vpc_AccessCode']);
                break;
        }

        $this->traceGatewayPaymentRequest($traceRequest, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

        return $request;
    }

    protected function authorizeRecurring(array $content, array $input)
    {
        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        unset($content['vpc_CardSecurityCode'], $content['vpc_Card']);
        unset($content['vpc_ReturnURL'], $content['vpc_gateway']);

        $response = $this->postAmaTransactionRequestAndGetContent($content, $input);

        $this->traceGatewayPaymentResponse(
            $response, $input, TraceCode::GATEWAY_RECURRING_AUTH_RESPONSE);

        $response['received'] = '1';

        $this->gatewayEntity->fill($response);

        $this->repo->saveOrFail($this->gatewayEntity);

        $this->checkTransactionResponse($response, $input);
    }

    protected function authorizeNotEnrolled(array $content, array $input)
    {
        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        $response = $this->postAmaTransactionRequestAndGetContent($content, $input);

        $this->traceGatewayPaymentResponse(
            $response, $input, TraceCode::GATEWAY_NOT_ENROLLED_REQUEST);

        $response['received'] = '1';

        $this->gatewayEntity->fill($response);

        $this->repo->saveOrFail($this->gatewayEntity);

        $this->checkTransactionResponse($response, $input);
    }

    protected function authorizeEnrolled(array $input, array $authResponse)
    {
        $this->app['diag']->trackGatewayPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_INITIATED, $input);

        // Adding dummy callback URL
        $input['callbackUrl'] = '';

        $content = $this->getPaymentAuthorizeRequestContent($input);

        $this->addAuthenticationData($content, $authResponse);

        $content['vpc_SecureHash'] = $this->generateHash($content);
        $content['vpc_SecureHashType'] = strtoupper(HashAlgo::SHA256);

        $gatewayEntity = $this->createGatewayPaymentEntity($content, $input);

        $response = $this->postAmaTransactionRequestAndGetContent($content, $input);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $response['received'] = '1';

        $gatewayEntity->fill($response);

        $this->repo->saveOrFail($gatewayEntity);

        $this->checkTransactionResponse($response, $input);

        return $response;
    }

    protected function addAuthenticationData(&$content, $authResponse)
    {
        $content['vpc_3DSECI'] = $authResponse[Mpi\Base\Entity::ECI];
        $content['vpc_3DSXID'] = $authResponse[Mpi\Base\Entity::XID];
        $content['vpc_3DSenrolled'] = $authResponse[Mpi\Base\Entity::ENROLLED];
        $content['vpc_3DSstatus'] = $authResponse[Mpi\Base\Entity::STATUS];
        $content['vpc_VerToken'] = $authResponse[Mpi\Base\Entity::CAVV];
        $content['vpc_VerType'] = '3DS';

        unset($content['vpc_ReturnURL'], $content['vpc_gateway']);
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

    public function callback(array $input)
    {
        parent::callback($input);

        switch ($input['payment'][Payment\Entity::AUTHENTICATION_GATEWAY])
        {
            case Payment\Gateway::MPI_BLADE:
            case Payment\Gateway::MPI_ENSTAGE:
                parent::callback($input);

                $authResponse = $this->callAuthenticationGateway($input,
                                                    $input['payment'][Payment\Entity::AUTHENTICATION_GATEWAY]);

                $this->setCardNumberAndCvv($input);

                $input['gateway'] = $this->authorizeEnrolled($input, $authResponse);
                break;

            default:
                if (isset($input['gateway']['vpc_MerchTxnRef']) === false)
                {
                    // Payment fails since vpc_MerchTxnRef not set, throw exception
                    throw new Exception\GatewayErrorException(
                        Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
                }

                $this->assertPaymentId($input['payment']['id'], $input['gateway']['vpc_MerchTxnRef']);

                $expectedAmount = (string) $input['payment']['amount'];
                $this->assertAmount($expectedAmount, $input['gateway']['vpc_Amount']);

                $this->verifySecureHash($input['gateway']);
                break;
        }

        $gatewayPayment = $this->repo->findByMerchantTxnRefAndCommand(
                    $input['gateway']['vpc_MerchTxnRef'], Command::PAY);

        $input['gateway']['received'] = 1;
        $gatewayPayment->fill($input['gateway']);
        $gatewayPayment->saveOrFail();

        return $this->verifyPaymentCallbackResponse($gatewayPayment, $input);
    }

    public function callbackOtpSubmit(array $input)
    {
        return $this->callback($input);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        if ($this->authorize === true)
        {
            return $this->captureAuthorizedPayment($input);
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $payment = $this->repo->findByPaymentIdAndCommandOrFail(
                                $input['payment']['id'], Command::PAY);

        $content = $this->getPaymentRefundRequestContent($input, $payment);

        $toSaveContent = $content;
        $toSaveContent['refund_id'] = $input['refund']['id'];
        $toSaveContent['terminal_id'] = $input['terminal']['id'];

        $refund = $this->createGatewayPaymentEntity($toSaveContent, $input);

        $content = $this->postAmaTransactionRequestAndGetContent($content, $input);

        $content['received'] = 1;
        $refund->fill($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REFUND,
            ['content' => $content,
            'action' => $this->action,
            'payment' => $input['payment'],
            'refund' => $input['refund']]);

        $this->checkTransactionResponse($content, $input);

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($content),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($content)
        ];
    }

    protected function getGatewayData(array $refundFields)
    {
        return [
            Entity::VPC_ACQ_RESPONSE_CODE   => $refundFields[Entity::VPC_ACQ_RESPONSE_CODE] ?? null,
            Entity::VPC_BATCH_NO            => $refundFields[Entity::VPC_BATCH_NO] ?? null,
            Entity::VPC_MESSAGE             => $refundFields[Entity::VPC_MESSAGE] ?? null,
            Entity::VPC_SHOP_TRANSACTION_NO => $refundFields[Entity::VPC_SHOP_TRANSACTION_NO] ?? null,
            Entity::VPC_TRANSACTION_NO      => $refundFields[Entity::VPC_TRANSACTION_NO] ?? null,
            Entity::VPC_TXN_RESPONSE_CODE   => $refundFields[Entity::VPC_TXN_RESPONSE_CODE] ?? null,
        ];
    }

    public function verifyInternalRefund(array $input)
    {
        $isRefundRequired = $this->isRefundRequired($input);

        if ($isRefundRequired)
        {
            $this->refund($input);

            // Verified and refund performed
            return false;
        }

        // Verified to not require any refund
        return true;
    }

    public function alreadyRefunded(array $input)
    {
        $paymentId = $input['payment_id'];
        $refundAmount = $input['refund_amount'];
        $refundId = $input['refund_id'];

        $refundedEntities = $this->repo->getSuccessfullyRefundedEntities($paymentId, $refundAmount);

        foreach ($refundedEntities as $refundedEntity)
        {
            if ($refundedEntity->getRefundId() === $refundId)
            {
                return true;
            }
        }

        return false;
    }

    protected function isRefundRequired(array $input, bool $checkTransaction = true)
    {
        $paymentId = $input['payment']['id'];
        $refundAmount = $input['amount'];

        //
        // Gets all the refund entities with the given amount and payment id and which were successful.
        // This is not an absolute check since for the same payment id, two partial refunds of the same
        // amount could be successful.
        //
        $refundedEntities = $this->repo->getSuccessfullyRefundedEntities($paymentId, $refundAmount);

        $verify = new Base\Verify($this->gateway, $input);

        $verifyContent = $this->sendPaymentVerifyRequest($verify);

        //
        // If the payment transaction id is present, we assume that the payment
        // has been captured on the gateway.
        // We also check for the refund entities, if present. If refund entity is present
        // we assume that the payment has been refunded on gateway and refund should
        // not be called again.
        //
        if ((($input['payment']['transaction_id'] === null) and
             ($checkTransaction === true)) or
            ($refundedEntities->count() > 0) or
            ((isset($verifyContent['vpc_RefundedAmount']) === true) and
             ($verifyContent['vpc_RefundedAmount'] !== '0')))
        {
            return false;
        }

        return true;
    }

    public function reverse(array $input)
    {
        parent::reverse($input);

        $payment = $this->repo->findByPaymentIdAndCommandOrFail(
                                $input['payment']['id'], Command::PAY);

        $content = $this->getPaymentReversalRequestContent($input, $payment);

        $toSaveContent = $content;
        $toSaveContent['refund_id'] = $input['refund']['id'];
        $toSaveContent['terminal_id'] = $input['terminal']['id'];
        $toSaveContent['vpc_Amount'] = $input['refund']['amount'];

        $refund = $this->createGatewayPaymentEntity($toSaveContent, $input);

        $content = $this->postAmaTransactionRequestAndGetContent($content, $input);

        $content['received'] = 1;
        $refund->fill($content);

        $this->trace->info(
            TraceCode::GATEWAY_REVERSE_RESPONSE,
            ['content' => $content,
            'action' => $this->action,
            'payment' => $input['payment'],
            'refund' => $input['refund']]);

        $this->checkTransactionResponse($content, $input);

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => json_encode($content),
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($content)
        ];
    }

    public function forceAuthorizeFailed($input)
    {
        $repo = $this->repo;

        $gatewayPayment = $repo->findByPaymentIdAndCommandOrFail($input['payment']['id'], Command::PAY);

        // If it's already authorized on axis side, there's nothing to do here. We just return back.
        if (($gatewayPayment->getTransactionId() !== null) and
            ($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getVpcTransactionCode() === '0'))
        {
            return true;
        }

        // assert ($payment['received'] === false);
        // assert ($payment['vpc_TxnResponseCode'] !== '0');

        if (isset($input['gateway']['vpc_TransactionNo']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Correct field not present for the required operation');
        }

        $txnNo = $input['gateway']['vpc_TransactionNo'];
        $txnNo = (int) $txnNo;
        assertTrue (strlen($txnNo) === 10);
        assertTrue (is_integer($txnNo) === true);

        $terminalId = $input['terminal']['id'];

        $count = $repo->countPaymentsNearTransactionNo($txnNo, $terminalId);

        if ($count === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'No migs payments with nearby vpc_TransactionNo found');
        }

        $gatewayPayment->setVpcTransactionNo($txnNo);
        $gatewayPayment['vpc_TxnResponseCode'] = '0';

        $repo->saveOrFail($gatewayPayment);

        return true;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new Base\ScroogeResponse();

        $unprocessedRefunds = $this->getUnprocessedRefunds();

        $processedRefunds = $this->getProcessedRefunds();

        if (in_array($input['refund']['id'], $unprocessedRefunds) === true)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::REFUND_MANUALLY_CONFIRMED_UNPROCESSED)
                                   ->toArray();
        }

        if (in_array($input['refund']['id'], $processedRefunds) === true)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        // Adding a check for 8th May 2017 as track id was
        // changed in migs refund from payment id to refund id

        if ($input['refund']['created_at'] < 1494268200)
        {
            throw new Exception\LogicException(
                'Unable to verify migs refund',
                ErrorCode::GATEWAY_VERIFY_OLDER_REFUNDS_DISABLED,
                [
                    'payment_id'    => $input['refund']['payment_id'],
                    'refund_id'     => $input['refund']['id'],
                ]);
        }

        $content = $this->sendVerifyRequest($input, 'refund');

        $scroogeResponse->setGatewayVerifyResponse($content)
                        ->setGatewayKeys($this->getGatewayData($content));

        if ((isset($content['vpc_DRExists']) === false) or (isset($content['vpc_FoundMultipleDRs']) === false))
        {
            throw new Exception\LogicException(
                'Unexpected gateway verify refund response',
                ErrorCode::GATEWAY_ERROR_UNEXPECTED_STATUS,
                [
                    Payment\Gateway::GATEWAY_VERIFY_RESPONSE  => json_encode($content),
                    Payment\Gateway::GATEWAY_KEYS             => $this->getGatewayData($content)
                ]
            );
        }

        // vpc_DRExists can be 'N' in two cases:
        // 1. If refund is older than 5 days (MiGS doesn't allow txn query on txns older than 5 days)
        //    We throw exception in this case as it has to be manually reviewed
        // 2. If refund request didn't reach them (Host not found, Domain resolution failed etc.)
        //    We return false here since the refund request didn't reach them and it needs to be
        //    retried
        if ($content['vpc_DRExists'] === 'N')
        {
            if ($input['refund']['created_at'] > Carbon::now(Timezone::IST)->subDays(5)->getTimestamp())
            {
                return $scroogeResponse->setSuccess(false)
                                       ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                       ->toArray();
            }

            throw new Exception\LogicException(
                'Cannot verify old MiGS refunds',
                ErrorCode::GATEWAY_VERIFY_OLDER_REFUNDS_DISABLED,
                [
                    Payment\Gateway::GATEWAY_VERIFY_RESPONSE  => json_encode($content),
                    Payment\Gateway::GATEWAY_KEYS             => $this->getGatewayData($content)
                ]
            );
        }

        if (($content['vpc_FoundMultipleDRs'] === 'N') and
            (((int) $content['vpc_RefundedAmount']) === $input['refund']['base_amount']))
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }
        else if ($content['vpc_FoundMultipleDRs'] === 'Y')
        {
            throw new Exception\LogicException(
                'Shouldn\'t reach here - FoundMultipleDRs',
                ErrorCode::GATEWAY_ERROR_MULTIPLE_REFUNDS_FOUND,
                [
                    'payment_id' => $input['refund']['payment_id'],
                    'refund_id'  => $input['refund']['id'],
                ]);
        }

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                               ->toArray();
    }

    protected function captureAuthorizedPayment(array $input)
    {
        assertTrue ($input['payment']['status'] === 'authorized');

        $gatewayPayment = $this->repo->findByPaymentIdAndCommandOrFail(
            $input['payment']['id'], Command::PAY);

        $capturedAmount = (int) $gatewayPayment['vpc_CapturedAmount'];

        if ($capturedAmount === $input['payment']['amount'])
        {
            //
            // Looks like the payment has already been captured on gateway,
            // but due to some previous error, this has not been recorded
            // on api.
            //
            // In this case we will silently return implying payment has
            // been captured on gateway
            //

            return;
        }

        $content = $this->getPaymentCaptureRequestContent($input, $gatewayPayment);

        $gatewayCapturedPayment = $this->createGatewayPaymentEntity($content, $input);

        $content = $this->postAmaTransactionRequestAndGetContent($content, $input);

        if (isset($content['vpc_TxnResponseCode']) === false)
        {
            $this->trace->error(
                TraceCode::PAYMENT_CAPTURE_FAILURE,
                [
                    'payment_id' => $input['payment']['id'],
                    'gateway' => $this->gateway,
                    'vpc_TxnResponseCode' => null,
                    'content' => $content,
                ]
            );

            $content['vpc_TxnResponseCode'] = '?';
            $content['vpc_MerchTxnRef'] = $input['payment']['id'];
        }

        $content['received'] = 1;
        $gatewayCapturedPayment->fill($content)->saveOrFail();

        $this->checkTransactionResponse($content, $input);
    }

    protected function sendVerifyRequest($input, $entity = 'payment')
    {
        $content = $this->getVerifyRequestContent($input, $entity);

        $content = $this->postAmaTransactionRequestAndGetContent($content, $input);

        if (isset($content['vpc_SecureHash']))
        {
            $this->verifySecureHash($content);
            unset($content['vpc_SecureHash']);
        }

        if (isset($content['vpc_Command']))
        {
            unset($content['vpc_Command']);
        }

        return $content;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $content = $this->sendVerifyRequest($input, 'payment');

        $verify->verifyResponse = $this->response;

        $verify->verifyResponseBody = $this->response->body;

        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;
        $content = $verify->verifyResponseContent;
        $input = $verify->input;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'payment_id' => $input['payment']['id'],
                'content' => $content
            ]);

        unset($content['vpc_Command']);

        if ((isset($content['vpc_DRExists']) === false) and
            ($content['vpc_TxnResponseCode'] === '7'))
        {
            // Most probably means AMA credentials are not correct.
            // However, not sure. Read the error message provided.
            throw new Exception\GatewayErrorException(
                Error\ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                $content['vpc_TxnResponseCode'],
                $content['vpc_Message']);
        }

        if ($content['vpc_DRExists'] !== 'Y')
        {
            $this->verifyPaymentNonExistentCase($verify);
        }
        else
        {
            assertTrue ($content['vpc_DRExists'] === 'Y');

            $this->verifyPaymentReconcileWithGatewayResponse($content, $verify);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $this->verifyPaymentBackfillDataIfRequired($content, $gatewayPayment);

        return $verify->status;
    }

    protected function verifyPaymentNonExistentCase($verify)
    {
        $verify->gatewaySuccess = false;

        $gatewayPayment = $verify->payment;

        $apiPayment = $verify->input['payment'];

        // Could be the case where the transaction didn't even hit migs
        if (($gatewayPayment['received'] === false) and
            (($gatewayPayment['vpc_TxnResponseCode'] === null) or
             ($gatewayPayment['vpc_TxnResponseCode'] !== '0')))
        {
            $verify->apiSuccess = false;
        }
        else
        {
            if (($apiPayment['status'] !== Payment\Status::CREATED) and
                ($apiPayment['status'] !== Payment\Status::FAILED))
            {
                $verify->apiSuccess = true;
                $verify->status = VerifyResult::STATUS_MISMATCH;
            }
            else
            {
                $verify->apiSuccess = false;
            }
        }
    }

    protected function verifyPaymentReconcileWithGatewayResponse($content, $verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        if ($content['vpc_TxnResponseCode'] === '0')
        {
            $verify->gatewaySuccess = true;

            if (($payment['vpc_TxnResponseCode'] !== '0') or
                ($input['payment']['status'] === 'failed') or
                ($input['payment']['status'] === 'created'))
            {
                $verify->apiSuccess = false;
                $verify->status = VerifyResult::STATUS_MISMATCH;
            }
            else
            {
                $verify->apiSuccess = true;
            }
        }
        else
        {
            $verify->apiSuccess = false;
            $verify->gatewaySuccess = false;

            //
            // If payment is not marked as success then it shouldn't be success
            // on migs end as well.
            //

            if (($payment['vpc_TxnResponseCode'] === '0') or
                (($input['payment']['status'] !== 'failed') and
                 ($input['payment']['status'] !== 'created')))
            {
                // It's marked as success, in this case, if it's totally refunded,
                // then that means billdesk refunded the payment on it's own end
                // and we don't need to worry.

                $verify->gatewaySuccess = true;
                $verify->status = VerifyResult::STATUS_MISMATCH;
            }
        }
    }

    protected function verifyPaymentBackfillDataIfRequired($content, $gatewayPayment)
    {
        if ($gatewayPayment['received'] === false)
        {
            unset($content['vpc_Command']);

            $gatewayPayment->fill($content);
            $gatewayPayment->saveOrFail();
        }
        else
        {
            // Fill only important fields that change during payment auth/capture/refund
            // lifecycle.
            $array = [
                'vpc_AuthorisedAmount',
                'vpc_CapturedAmount',
                'vpc_RefundedAmount',
                'vpc_ShopTransactionNo'
            ];

            foreach ($array as $key)
            {
                if (empty($content[$key]) === false)
                {
                    $gatewayPayment->setAttribute($key, $content[$key]);
                }
            }

            $gatewayPayment->saveOrFail();
        }
    }

    protected function postAmaTransactionRequestAndGetContent(array & $content, $input)
    {
        $response = $this->postAmaTransactionRequest($content, $input);

        $content = $this->getAmaTxnResponseContent($response);

        return $content;
    }

    protected function parseQueryResponse($response)
    {
        parse_str($response->body, $content);

        return $content;
    }

    protected function getPaymentAuthorizeRequestContent($input)
    {
        $attributes = [
            'vpc_Command'     => Command::PAY,
            'vpc_Amount'      => $input['payment']['amount'],
            'vpc_Currency'    => $input['payment']['currency'],
            'vpc_MerchTxnRef' => $input['payment']['id'],
        ];

        $network = $input['card']['network'];

        $content = [
            'vpc_Version'           => '1',
            'vpc_ReturnURL'         => $input['callbackUrl'],
            'vpc_Locale'            => 'en',
            'vpc_gateway'           => 'ssl',
            'vpc_Card'              => $this->getVpcCardValue($network),
            'vpc_CardNum'           => $input['card']['number'],
            'vpc_CardExp'           => $this->getFormattedCardExpiryDate($input),
            'vpc_CardSecurityCode'  => $input['card']['cvv'],
//            'vpc_OrderInfo'             => 'testinfo',
        ];

        $content = array_merge($attributes, $content);

        if (($this->mode === Mode::TEST) and
            ($this->mock === false))
        {
            $this->addTestCardDetailsInTestMode($content);
        }

        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        return $content;
    }

    protected function addSubMerchantDetails(array & $content, array $input)
    {
        // Dummy function
    }

    protected function getPaymentCaptureRequestContent($input, $payment)
    {
        $content = [
            'vpc_Command'       => Command::CAPTURE,
            'vpc_MerchTxnRef'   => $input['payment']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
            'vpc_Amount'        => $input['amount'],
            'vpc_Currency'      => $input['currency'],
        ];

        return $content;
    }

    protected function getVerifyRequestContent($input, $entity)
    {
        $content = [
            'vpc_Command'       => Command::QUERYDR,
            'vpc_MerchTxnRef'   => $input[$entity]['id'],
        ];

        return $content;
    }

    protected function getPaymentRefundRequestContent($input, $payment)
    {
        $content = [
            'vpc_Command'       => Command::REFUND,
            'vpc_Amount'        => $input['refund']['amount'],
            'vpc_Currency'      => $input['currency'],
            'vpc_MerchTxnRef'   => $input['refund']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
        ];

        return $content;
    }

    protected function getPaymentReversalRequestContent($input, $payment)
    {
        $content = [
            'vpc_Command'       => Command::REVERSAL,
            'vpc_Currency'      => $input['payment']['currency'],
            'vpc_MerchTxnRef'   => $input['refund']['id'],
            'vpc_TransNo'       => $payment['vpc_TransactionNo'],
        ];

        return $content;
    }

    protected function addAmaTransactionFields(array & $content, $input)
    {
        $content['vpc_Version'] = 1;

        $this->addMerchantIdAndAccessCode($content, $input['terminal']);

        $this->addAmaUserAndPassword($content, $input['terminal']);
    }

    protected function getAuthRequestArray($content)
    {
        $request = [
            'url'       => $this->getUrl(Command::PAY),
            'content'   => $content,
            'method'    => 'post'
        ];

        return $request;
    }

    protected function getAmaRequestArray($content)
    {
        $request = [
            'action'    => $this->action,
            'url'       => $this->getUrl('ama'),
            'content'   => $content,
            'method'    => 'post'
        ];

        return $request;
    }

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $payment = $this->getNewGatewayPaymentEntity();

        unset($attributes['vpc_Card']);

        $paymentId = $input['payment']['id'];
        $attributes['terminal_id'] = $input['terminal']['id'];

        $payment->setPaymentId($paymentId);
        $payment->setAction($this->action);

        $payment->fill($attributes);

        $payment->saveOrFail();

        $this->gatewayEntity = $payment;

        return $payment;
    }

    protected function postAmaTransactionRequest(array & $content, $input)
    {
        $traceContent = $content;

        unset($traceContent['vpc_CardNum']);
        unset($traceContent['vpc_CardExp']);
        unset($traceContent['vpc_CardSecurityCode']);

        $this->trace->info(
            TraceCode::GATEWAY_SUPPORT_REQUEST,
            [
                'gateway' => 'axis_migs',
                'action' => 'Support action request array',
                'content' => $traceContent
            ]);

        $this->addAmaTransactionFields($content, $input);

        $request = $this->getAmaRequestArray($content);
        // send the request and get response

        $this->paymentId = $input['payment']['id'];

        $response = $this->postRequest($request);

        return $response;
    }

    public function postRequest($request)
    {
        $options['timeout'] = 60;

        $request['options'] = $options;

        $this->response = $this->sendGatewayRequest($request);

        return $this->response;
    }

    protected function getAmaTxnResponseContent($response)
    {
        $this->trace->info(
            TraceCode::GATEWAY_SUPPORT_RESPONSE,
            ['action' => 'Support action response string',
            'content' => $response->body]);

        parse_str($response->body, $content);

        return $content;
    }

    protected function getStringToHash($content, $glue = '')
    {
        unset($content['vpc_SecureHashType']);

        $input = [];

        foreach ($content as $k => $v)
        {
            if (Str::startsWith($k, 'vpc_') === true)
            {
                $input[] = $k . '=' . $v;
            }
        }

        return parent::getStringToHash($input, '&');
    }

    protected function getHashOfString($str)
    {
        $secret = pack('H*', $this->getSecret());

        return strtoupper(hash_hmac(HashAlgo::SHA256, $str, $secret));
    }

    protected function getHashValueFromContent(array $input)
    {
        return strtoupper(parent::getHashValueFromContent($input));
    }

    protected function addMerchantIdAndAccessCode(array & $content, $terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['vpc_Merchant'] = $this->config['test_merchant_id'];
            $content['vpc_AccessCode'] = $this->config['test_access_code'];
        }
        else
        {
            $content['vpc_Merchant'] = $terminal['gateway_merchant_id'];
            $content['vpc_AccessCode'] = $terminal['gateway_access_code'];
        }
    }

    protected function addAmaUserAndPassword(array & $content, $terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            $content['vpc_User'] = $this->config['test_ama_user'];
            $content['vpc_Password'] = $this->config['test_ama_password'];
        }
        else
        {
            $content['vpc_User'] = $terminal['gateway_terminal_id'];
            $content['vpc_Password'] = $terminal['gateway_terminal_password'];
        }
    }

    protected function verifyPaymentCallbackResponse($gatewayPayment, array $input)
    {
        $txnResponseCode = $input['gateway']['vpc_TxnResponseCode'];

        $threeDSstatus = $input['gateway']['vpc_3DSstatus'] ?? null;

        $apiErrorCode = null;

        $message = $input['gateway']['vpc_Message'] ?? '';

        // check for success
        if ($txnResponseCode === '0')
        {
            //
            // Transaction has been successful
            // However, if 3dsecure failed and international is not enabled for the merchant,
            // then we need to block the transaction on the international card.
            //

            $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

            $authStatus = ThreeDSecureStatus::getThreeDSstatus($threeDSstatus);

            if (($authStatus === Payment\TwoFactorAuth::FAILED) or
                ($authStatus === Payment\TwoFactorAuth::UNKNOWN))
            {
                if ($this->shouldRaiseErrorForInternationalMerchant($input))
                {
                    $apiErrorCode = Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED;
                }
                else if ($input['merchant']['risk_rating'] > Notify::MIN_HIGH_RISK_RATING)
                {
                    $apiErrorCode = Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK;
                }
                else
                {
                    return $this->getCallbackResponseData(['threeDSstatus' => $threeDSstatus], $acquirerData); // payment succeeds
                }
            }
            else
            {
                // payment succeeds
                return $this->getCallbackResponseData(['threeDSstatus' => $threeDSstatus], $acquirerData);
            }
        }
        else
        {
            // Get appropriate error code
            $apiErrorCode = $this->getApiErrorCode($input);
        }

        $this->throwException($apiErrorCode, $txnResponseCode, $message, $threeDSstatus);
    }

    protected function getCallbackResponseData(array $input, $response = [])
    {
        $twoFactorAuth = ThreeDSecureStatus::getThreeDSstatus($input['threeDSstatus']);

        $response[Payment\Entity::TWO_FACTOR_AUTH] = $twoFactorAuth;

        return $response;
    }

    protected function throwException($code, $gatewayErrorCode, $gatewayErrorDesc, $threeDSstatus = null)
    {
        $e = new Exception\GatewayErrorException($code, $gatewayErrorCode, $gatewayErrorDesc);

        $twoFactorAuth = ThreeDSecureStatus::getThreeDSstatus($threeDSstatus);

        if ($twoFactorAuth === Payment\TwoFactorAuth::FAILED)
        {
            $e->markTwoFaError();
        }

        throw $e;
    }

    protected function getApiErrorCode($input)
    {
        if ($this->isSessionExpired($input))
        {
            return Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BECAUSE_SESSION_EXPIRED;
        }

        return $this->checkTransactionResponse($input['gateway'], $input);
    }

    protected function isSessionExpired($input)
    {
        $txnResponseCode = $input['gateway']['vpc_TxnResponseCode'];

        $message = $input['gateway']['vpc_Message'];

        if ((isset(AxisMigs\ErrorCodes\ErrorCodes::$txnErrorCodeMap[$txnResponseCode])) and
            ($txnResponseCode === 'Aborted') and
            ($message === 'Your Session has expired'))
        {
            return true;
        }

        return false;
    }

    protected function returnIfRefundAmountMatches($content, $input)
    {
        if (isset($content['vpc_RefundedAmount']) === false)
        {
            return false;
        }

        $amount = $input['payment']['amount_refunded'] + $input['refund']['amount'];

        $vpcAmount = (int) $content['vpc_RefundedAmount'];

        return ($amount === $vpcAmount);
    }

    protected function getVpcCardValue($network)
    {
        return $network;
    }

    protected function addTestCardDetailsInTestMode(array & $content)
    {
        assertTrue ($this->mode === Mode::TEST);

        if ($content['vpc_CardNum'] === '4111111111111111')
        {
            return;
        }

        $content['vpc_Card'] = 'MasterCard';
        $content['vpc_CardNum'] = '5123456789012346';
        $content['vpc_CardExp'] = '1705';
        $content['vpc_CardSecurityCode'] = '333';
    }

    protected function getFormattedCardExpiryDate($input)
    {
        $expiryMonth = $input['card']['expiry_month'];

        if ($expiryMonth < 10)
        {
            $expiryMonth = '0' . $expiryMonth;
        }

        $cardExp = substr($input['card']['expiry_year'], 2,2) . $expiryMonth;

        return $cardExp;
    }

    protected function shouldRaiseErrorForInternationalMerchant(array $input) : bool
    {
        return ($input['merchant']['international'] === false);
    }

    protected function checkTransactionResponse($content, $input)
    {
        $msg = $txnResponseCode = null;

        if (isset($content[AxisMigs\ErrorCodes\ErrorFields::VPC_TXNRESPONSECODE]) === true)
        {
            $txnResponseCode = $content[AxisMigs\ErrorCodes\ErrorFields::VPC_TXNRESPONSECODE];
        }

        if ($txnResponseCode === '0')
        {
            return;
        }

        $code = AxisMigs\ErrorCodes\ErrorCodes::getInternalErrorCode($content);

        $msg = AxisMigs\ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($content);

        if ($this->action === Base\Action::REFUND)
        {
            // Refund request failed. Just check if refund amount due to
            // previous requests matches the expected amount.
            // In that case, we will mark it as success.

            $ret = $this->returnIfRefundAmountMatches($content, $input);

            if ($ret === true)
            {
                return;
            }
            // The following checks are being made -
            // to avoid the case where an array is returned because $code should be a string
            // there are multiple levels of mapping - so if one of the index is missing in the message -
            // the $code and $msg will be an array instead of a string
            $code = (is_string($code) === true) ? $code : ErrorCode::BAD_REQUEST_REFUND_FAILED;
            $msg = (is_string($msg) === true) ? $msg : ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
        }

        // Payment fails, throw exception
        throw new Exception\GatewayErrorException(
            $code,
            $txnResponseCode,
            $msg,
            [
                Payment\Gateway::GATEWAY_RESPONSE  => json_encode($content),
                Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($content)
            ]);
    }

    protected function decideAuthenticationGateway($input)
    {
        if (empty($input['authenticate']['gateway']) === false)
        {
            $authenticationGateway = $input['authenticate']['gateway'];
        }
        else
        {
            $authenticationGateway = Payment\Gateway::AXIS_MIGS;
        }

        return $authenticationGateway;
    }

    protected function callAuthenticationGateway(array $input, $authenticationGateway)
    {
        return $this->app['gateway']->call(
            $authenticationGateway,
            $this->action,
            $input,
            $this->mode);
    }
}
