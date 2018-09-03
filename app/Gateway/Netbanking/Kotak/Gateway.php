<?php

namespace RZP\Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Entity;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Gateway\Netbanking\Base\Entity as E;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;
    use AuthorizeFailed;

    protected $gateway = 'netbanking_kotak';

    protected $bank = 'kotak';

    protected $tpv;

    protected $sortRequestContent = false;

    protected $fields = array(
        'MessageCode',
        'DateTimeInGMT',
        'MerchantId',
        'TraceNumber',
        'Amount',
        'TransactionDescription',
        'Checksum',
    );

    protected $map = array(
        'MessageCode'            => 'reference1',
        'DateTimeInGMT'          => 'date',
        'MerchantId'             => 'merchant_code',
        'TraceNumber'            => 'int_payment_id',
        'Amount'                 => 'amount',
        'TransactionDescription' => 'client_code',
        'AuthorizationStatus'    => 'status',
        'BankReference'          => 'bank_payment_id',
    );

    /**
     * @param  array $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($content, $input);

        $request = $this->getRequestArray($content);

        if ($this->mock === true)
        {
            $request['content']['msg'] = $request['content']['msg'] . '|' . $input['callbackUrl'];
        }

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }
//
//    public function capture(array $input = array())
//    {
//        return parent::capture($input);
//    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->getDataFromResponse($input['gateway']['msg']);

        $this->validateCallbackChecksum($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        // Unset date because format of date returned
        // is different than what we sent
        unset($content['DateTimeInGMT']);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->assertPaymentId((string) $gatewayPayment->getIntPaymentId(), $content['TraceNumber']);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format($content['Amount'], 2, '.', '');
        $this->assertAmount($expectedAmount, $actualAmount);

        $attrs['received'] = true;
        $attrs['status'] = $content['AuthorizationStatus'];
        $attrs['bank_payment_id'] = $content['BankReference'];

        $gatewayPayment->fill($attrs);

        $gatewayPayment->saveOrFail();

        if ($attrs[Fields::STATUS] === Status::SUCCESS)
        {
            $response = $this->verifyCallback($input, $gatewayPayment);
        }
        if (($attrs[Fields::STATUS] === Status::FAIL) or ($response[Fields::AUTHORIZATION_STATUS] !== Status::SUCCESS))
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]
            );

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function verifyPayment($verify)
    {
        $content = $verify->verifyResponseContent;

        $status = VerifyResult::STATUS_MATCH;

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        if ($content['AuthorizationStatus'] === 'Y')
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        // From verified content put the bank payment id and
        // status
        $this->fillStatusAndBankPaymentId($input, $content);

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        return $status;
    }

    /**
     * For verify, Set bank payment and status from
     * verified response.
     */
    protected function fillStatusAndBankPaymentId($input, $content)
    {
        $gatewayPayment = $this->repo->retrieveByPaymentIdOrFail(
            $input['payment']['id']);

        $attrs['received'] = true;
        $attrs['status'] = $content['AuthorizationStatus'];
        $attrs['bank_payment_id'] = $content['BankReference'];

        $gatewayPayment->fill($attrs);

        $gatewayPayment->saveOrFail();
    }

    protected function validateCallbackChecksum($content)
    {
        $inputHash = $content['Checksum'];

        unset($content['Checksum']);

        $expectedHash = $this->getHashOfArray($content);

        if (hash_equals($expectedHash, $inputHash) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getDataFromResponse($data)
    {
        $content = explode('|', $data);

        $fields = $this->getFieldsForAction($this->action);

        /**
         * If Gateway returns data in invalid format,
         * then field count does not matches expected output format column count
         * throw Gateway unknown error exception
         */
        if (count($fields) !== count($content))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE);
        }

        $content = array_combine($fields, $content);

        return $content;
    }

    protected function getPaymentRequestData($input)
    {
        // Kotak asks for date in IST
        $date = Carbon::now(Timezone::IST)->format('dmYHis');

        $data = array(
            'MessageCode'            => MessageCodes::AUTHORIZE,
            'DateTimeInGMT'          => $date,
            'MerchantId'             => $input['terminal']['gateway_merchant_id'],
            'TraceNumber'            => time() . random_integer(5),
            'Amount'                 => $input['payment']['amount'] / 100,
            'TransactionDescription' => $this->getDynamicMerchantName($input['merchant'], 50),
        );

        if ($this->mode === Mode::TEST)
        {
            $data['MerchantId'] = $this->getTestMerchantId();
        }

        // Change Content for Merchants with TPV Required
        if ($input['merchant']->isTPVRequired())
        {
            $data['TransactionDescription'] = $input['order']['account_number'];

            if ($this->mode === Mode::TEST)
            {
                $data['MerchantId'] = $this->getTestTpvMerchantId();
            }
        }

        return $data;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $gatewayPayment = $verify->payment;

        $input = $verify->input;

        $date = Carbon::now(Timezone::IST)->format('dmYHis');

        $content = [
            'MessageCode'   => MessageCodes::VERIFY,
            'DateTimeInGMT' => $date,
            'MerchantId'    => $gatewayPayment['merchant_code'],
            'TraceNumber'   => $gatewayPayment['int_payment_id'],
            'Future1'       => '',
            'Future2'       => '',
        ];

        $request = $this->getRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $request);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            ['response' => $response]);

        $content = $response->body;

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            ['responseBody' => $content]);

        $content = $this->getDataFromResponse($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            ['responseContent' => $content]);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        return $content;
    }

    protected function getRequestArray($content)
    {
        $msg = $this->getMessageStringWithHash($content);

        $request = array(
            'url' => $this->getUrl($this->action),
            'method' => 'post',
            'content' => ['msg' => $msg],
        );

        return $request;
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        if ($this->action === Action::AUTHORIZE)
        {
            $type = $this->mode.'_'.$type;

            $type = strtoupper($type);
        }

        return constant($ns.'\Url::'.$type);
    }

    public function getMessageStringWithHash($content)
    {
        $str = $this->getStringToHash($content, '|');

        return $str . '|' . $this->getHashOfString($str);
    }

    protected function getTestMerchantId()
    {
        return 'OSTEST';
    }

    protected function getTestTpvMerchantId()
    {
        return 'OTTEST';
    }

    protected function getLiveSecret()
    {
        assert ($this->mode === Mode::LIVE);

        if ($this->tpv === true)
        {
            return $this->config['live_hash_secret_tpv'];
        }
        else if (isset($this->input['merchant']))
        {
            if ($this->input['merchant']->isTPVRequired())
            {
                return $this->config['live_hash_secret_tpv'];
            }
        }

        return $this->config['live_hash_secret'];
    }

    protected function getHashOfString($str)
    {
        $str = $str . '|' . $this->getSecret();

        return str_pad((crc32($str)), 8, '0', STR_PAD_LEFT);
    }

    protected function getHashOfArray($content)
    {
        $str = $this->getStringToHash($content, '|');

        return $this->getHashOfString($str);
    }

    protected function verifyCallback(array $input, $gatewayPayment)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $gatewayPayment;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //here the payment will be in created state and the callback has also returned a Success status so
        //marking apiSuccess as true
        $verify->apiSuccess = true;

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                [
                    'gateway'         => $this->gateway,
                    'verify_response' => $verify->verifyResponseContent,
                ]);
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');

        $actualAmount = number_format($verify->verifyResponseContent['Amount'], 2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->assertPaymentId($verify->verifyResponseContent[Fields::BANK_REFERENCE_NO],
            $gatewayPayment[E::BANK_PAYMENT_ID]);

        return $verify->verifyResponseContent;
    }

    protected function checkGatewaySuccess($verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[Fields::AUTHORIZATION_STATUS]) === true) and
            ($content[Fields::AUTHORIZATION_STATUS] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }
}
