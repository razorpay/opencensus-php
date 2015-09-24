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
    protected $fields             = array(
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
        'TraceNumber'            => 'trace_id',
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
        $content = $this->getDataWithChecksum($content);
        if ($this->mode == Mode::TEST)
        {
            $content = $content . '|' . $input['callbackUrl'];
        }
        $request = array(
            'url'     => $this->getUrl('pay'),
            'method'  => 'post',
            'content' => ['msg' => $content]);

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
        $this->validateCallbackChecksum($input);

        $response_data = $this->getDataFromResponse($input['gateway']['msg']);
        // Unset date because format of date returned is different than what we sent

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $input['gateway']);

        $payment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $attrs['received'] = true;
        $attrs['status'] = $response_data['AuthorizationStatus'];
        $attrs['bank_payment_id'] = $response_data['BankReference'];

        $payment->fill($attrs);
        $payment->saveOrFail();

        if (($attrs['status'] === '') or
            ($attrs['bank_payment_id'] === '')
        )
        {
            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,
                '',
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

    protected function validateCallbackChecksum($input)
    {
        $gateway_response = $input['gateway']['msg'];
        $gateway_response = $this->getDataFromResponse($gateway_response);
        $checksum_received = $gateway_response['Checksum'];

        unset($gateway_response['Checksum']);
        $response_expected = $this->getDataFromResponse($this->getDataWithChecksum($gateway_response));
        $checksum_expected = $response_expected['Checksum'];
        if ($checksum_received !== $checksum_expected)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }
    }

    protected function getDataFromResponse($data)
    {
        $gateway_response_array = explode('|', $data);
        $gateway_response_array = array_combine($this->getFieldsForAction($this->action), $gateway_response_array);

        return $gateway_response_array;
    }

    protected function getPaymentRequestData($input)
    {
        $date = Carbon::now('Asia/Kolkata')->format('dmYhis');
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
            'TraceNumber'   => $payment['client_code'],
            'Future1'       => '',
            'Future2'       => '',
        );
        $content = $this->getDataWithChecksum($content);

        $request = array(
            'url'     => $this->getUrl(),
            'method'  => 'post',
            'content' => ['msg' => $content]);


        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $request);

        $response = $this->sendGatewayRequest($request);
        sd($response);
        $content = $this->processContentFromPaymentVerifyResponse($response, $request);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        return $content;
    }

//    protected function verifyPayment($verify)
//    {
//        $payment = $verify->payment;
//        $content = $verify->verifyResponseContent;
//        $input = $verify->input;
//
//        $days = (time() - $input['payment']['created_at']) / (24 * 60 * 60);
//
//        // In HDFC netbnaking, the bank only stores the payment data for
//        // 45 days!
//        if ($days > 45)
//        {
//            throw new Exception\BadRequestValidationFailureException(
//                'For hdfc netbanking, the bank only stores payment data for 45 days. ' .
//                'The given payment for verification is ' . $days . ' days old');
//
//            // $verify->match = true;
//            // $verify->status = VerifyResult::STATUS_MATCH;
//            // return;
//        }
//
//        $status = VerifyResult::STATUS_MATCH;
//
//        $verify->apiSuccess = (($input['payment']['status'] === 'authorized') or
//            ($input['payment']['status'] === 'captured'));
//
//        $verify->gatewaySuccess = ($content['flgSuccess'] === 'S');
//
//        if (($verify->apiSuccess === false) and
//            ($verify->gatewaySuccess === true)
//        )
//        {
//            $status = VerifyResult::STATUS_MISMATCH;
//        }
//
//        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;
//
//        if ($payment['received'] === false)
//        {
//            $attrs = $this->getMappedAttributes($content);
//            $payment->fill($attrs);
//            $payment->saveOrFail();
//        }
//
//        return $status;
//    }

//    public function authorizeFailed(array $input)
//    {
//        $e = null;
//
//        try
//        {
//            $this->verify($input);
//        } catch (Exception\PaymentVerificationException $e)
//        {
//            ;
//        }
//
//        if ($e === null)
//        {
//            throw new Exception\LogicException(
//                'When converting failed payment to authorized, payment verification ' .
//                'should have failed but instead it did not. ' .
//                'Are you sure you want to convert this payment to authorized?');
//        }
//
//        $verify = $e->getVerifyObject();
//
//        if (($verify->apiSuccess === false) and
//            ($verify->gatewaySuccess === true)
//        )
//        {
//            $payment = $verify->payment;
//            $payment->fill($verify->verifyResponseContent);
//            $payment->saveOrFail();
//        }
//        else
//        {
//            throw new Exception\LogicException(
//                'Should not have reached here');
//        }
//
//        return true;
//    }

//    protected function processContentFromPaymentVerifyResponse($response, $request)
//    {
//        $this->trace->info(
//            TraceCode::GATEWAY_PAYMENT_VERIFY,
//            [$response->body]);
//
//        try
//        {
//            $crawler = new Crawler($response->body, $request['url']);
//            $form = $crawler->filter('form')->form();
//            $values = $form->getValues();
//            $url = $values['REDIRECTURL'];
//        } catch (\InvalidArgumentException $e)
//        {
//            $msg = $e->getMessage();
//
//            if ($msg === 'The current node list is empty')
//            {
//                // This happens because hdfc nb gateway is down.
//                // We will need to verify the request later.
//                throw new Exception\GatewayTimeoutException(
//                    'Payment verify request to Hdfc nb gateway timed out');
//            }
//        }
//
//        $content = [];
//        $parts = parse_url($url);
//        parse_str($parts['query'], $content);
//
//        return $content;
//    }

//    protected function getCallbackChecksum($input)
//    {
//        $paramsOrder = array(
//            'ClientCode',
//            'MerchantCode',
//            'TxnCurrency',
//            'TxnAmount',
//            'TxnScAmount',
//            'MerchRefNo',
//            'StSucFlg',
//            'StFailFlg',
//            'Date',
//            'Ref1',
//            'Ref2',
//            'Ref3',
//            'Ref4',
//            'Ref5',
//            'Ref6',
//            'Ref7',
//            'Ref8',
//            'Ref9',
//            'Ref10',
//            'Ref11',
//            'Date1',
//            'Date2',
//            'BankRefNo',
//            'Message',
//        );
//
//        $str = '';
//
//        $data = [];
//
//        foreach ($paramsOrder as $param)
//        {
//            if (isset($input[$param]))
//            {
//                $data[$param] = $input[$param];
//                $str .= $input[$param];
//            }
//        }
//
//        return $this->getHashOfString($str);
//    }

//    protected function getHashOfString($str)
//    {
//        $secret = $this->getSecret();
//
//        return (string)crc32($str . $secret);
//    }


//    protected function getLiveSecret()
//    {
//        assert($this->mode === Mode::LIVE);
//
//        return $this->config['live_hash_secret'];
//    }


    protected function getTestMerchantId()
    {
        return 'OSTEST';
    }

    protected function getHashSecret()
    {
        if ($this->mode === Mode::LIVE)
        {
            return $this->config['live_hash_secret'];
        }
        else
        {
            return $this->config['test_hash_secret'];
        }
    }

    protected function getDataWithChecksum($data)
    {
        $dataStr = implode("|", $data);
        $dataStrWithSecret = $dataStr . "|" . $this->getHashSecret();

        return (string)$dataStr . '|' . $this->getChecksum($dataStrWithSecret);
    }

    protected function getChecksum($dataStrWithSecret)
    {
        return str_pad((crc32($dataStrWithSecret)), 8, '0', STR_PAD_LEFT);
    }
}
