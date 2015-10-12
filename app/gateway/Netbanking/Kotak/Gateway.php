<?php

namespace Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use Constants\Mode;
use EE\Error\ErrorCode;
use EE\Exception;
use Gateway\Base\Action;
use Gateway\Base\Verify;
use Gateway\Base\VerifyResult;
use Gateway\Netbanking\Base;
use Symfony\Component\DomCrawler\Crawler;
use Trace\Trace;
use Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use ResponseFieldsTrait;

    protected $gateway = 'netbanking_kotak';

    protected $bank = 'kotak';

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

        $payment = $this->createGatewayPaymentEntity($content);

        $request = $this->getRequestArray($content);

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

        $this->validateCallbackChecksum($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        // Unset date because format of date returned
        // is different than what we sent
        unset($content['DateTimeInGMT']);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attrs['received'] = true;
        $attrs['status'] = $content['AuthorizationStatus'];
        $attrs['bank_payment_id'] = $content['BankReference'];

        $payment->fill($attrs);

        $payment->saveOrFail();

        if (($attrs['status'] === '') or
            ($attrs['bank_payment_id'] === ''))
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $attrs['status'],
                '');
        }
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getPaymentToVerify($input, $verify)
    {
        $payment = $this->getRepo()->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $payment;

        return $payment;
    }

    protected function validateCallbackChecksum($content)
    {
        $expectedHash = $content['Checksum'];

        unset($content['Checksum']);

        $generatedHash = $this->getHashOfArray($content);

        if ($expectedHash !== $generatedHash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getDataFromResponse($data)
    {
        $content = explode('|', $data);

        $fields = $this->getFieldsForAction($this->action);

        $content = array_combine($fields, $content);

        return $content;
    }

    protected function getPaymentRequestData($input)
    {
        // Kotak asks for date in GMT
        $date = Carbon::now('GMT')->format('dmYhis');

        $data = array(
            'MessageCode'            => MessageCodes::AUTHORIZE,
            'DateTimeInGMT'          => $date,
            'MerchantId'             => $input['terminal']['gateway_merchant_id'],
            'TraceNumber'            => time() . random_integer(5),
            'Amount'                 => $input['payment']['amount'] / 100,
            'TransactionDescription' => $input['payment']['contact'],
        );

        if ($this->mode === Mode::TEST)
        {
            $data['MerchantId'] = $this->getTestMerchantId();
        }

        return $data;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $payment = $verify->payment;
        $input = $verify->input;

        $content = array(
            'MessageCode'   => MessageCodes::VERIFY,
            'DateTimeInGMT' => $payment['date'],
            'MerchantId'    => $payment['merchant_code'],
            'TraceNumber'   => $payment['int_payment_id'],
            'Future1'       => '',
            'Future2'       => '',
        );

        $request = $this->getRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $request);

        $response = $this->sendGatewayRequest($request);
        $content = $this->processContentFromPaymentVerifyResponse($response, $request);

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

    public function getMessageStringWithHash($content)
    {
        $str = $this->getStringToHash($content, '|');

        return $str . '|' . $this->getHashOfString($str);
    }

    protected function getTestMerchantId()
    {
        return 'OSTEST';
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
}