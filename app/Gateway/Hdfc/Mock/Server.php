<?php

namespace RZP\Gateway\Hdfc\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Hdfc;
use RZP\Gateway\Hdfc\Mock;
use RZP\Gateway\Hdfc\Payment\Action;
use RZP\Models\Card;
use RZP\Models\Card\Network;
use RZP\Models\Payment\AuthType;

class Server extends Base\Mock\Server
{
    protected $request;

    protected $input;

    protected $data = array();

    protected $response = array();

    protected $specialCardNumbers = array(
        '4012001036275556',
        '4012001038488884',
        '4012001036298889',
        '4012001036853337',
        '4012001036983332',
        '4012001037461114',
        '4012001037484447',
        '4012001037490006',
    );

    protected $debitCardNumbers = array(
        '4012001037141112',
        '4005559876540',
        '4012001037167778',
        '4012001037490014',
        '6073849700004947',
        '4111111111111111',
        '4012001037411127',
        '5200000000000064',
        '6080757792005576',
    );

    protected $notEnrolledDebitCardNumbers = array(
        '4012001037141112',
        '4012001037411127',
    );

    protected $onlyPurchaseCardNetworks = array(
        Network::RUPAY,
        Network::MAES,
        Network::DICL);

    public function __construct()
    {
        parent::__construct();

        $this->gateway = new Gateway;
    }

    public function threeDSecure($input)
    {
        $gatewayTransaction = $this->getRepo()->findByGatewayTransactionIdOrFail($input['MD']);

        $card = $gatewayTransaction->payment->card;

        $networkCode = Network::getCode($card['network']);

        if ($networkCode === Network::RUPAY)
        {
            $this->data['paymentid'] = $input['MD'];
            $ret = $this->getAuthResponse($input['MD']);
            $ret['TermUrl'] = $input['TermUrl'];
            $ret['MD'] = $input['MD'];

            return $ret;
        }

        $input['PaRes'] = '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';

        $this->content($input, 'authenticate');

        return $input;
    }

    public function debitPin($input)
    {
        $gatewayTransaction = $this->getRepo()->findByGatewayPaymentIdOrFail($input['PaymentID']);

        $callbackUrl = $this->getCallbackUrl($gatewayTransaction->payment);

        $ret = $this->getDebitPinAuthorizationResponse($input['PaymentID'], $callbackUrl);

        $ret = http_build_query($ret);

        $ret = urldecode($ret);

        return $ret;
    }

    protected function getCallbackUrl($payment): string
    {
        $params = $this->getPaymentIdAndHashParams($payment);

        $callbackUrl = $this->route->getUrlWithPublicCallbackAuth($params);

        return $callbackUrl;
    }

    protected function getPaymentIdAndHashParams($payment): array
    {
        $publicId = $payment->getPublicId();

        $hash = $this->getHashOf($publicId);

        return ['x_entity_id' => $publicId, 'hash' => $hash];
    }

    protected function getHashOf(string $string): string
    {
        $secret = $this->app->config->get('app.key');

        return hash_hmac('sha1', $string, $secret);
    }

    public function gatewayTransaction($type = null)
    {
        $action = Hdfc\Utility::getFieldFromXML($this->input, 'action');

        switch ($action)
        {
            case Action::PURCHASE:
                $this->action = 'purchase';
                $xml = $this->authNotEnrolledOnGateway();
                break;
            case Action::AUTHORIZE:
                $this->action = 'authorize';
                $xml = $this->authNotEnrolledOnGateway();
                break;

            case Action::CAPTURE:
                $this->action = 'capture';
                $xml = $this->capturePaymentOnGateway();
                break;

            case Action::REFUND:
                $this->action = 'refund';
                $xml = $this->refundPaymentOnGateway();
                break;

            case Action::INQUIRY:
                $this->action = 'verify';
                $xml = $this->inquirePaymentOnGateway();
                break;

            default:
                throw new Exception\LogicException(
                    'Hdfc\Mock: Action code not recognized.',
                    null,
                    [
                        'payment_id' => $this->data['trackid'],
                        'action'     => $this->data['action'],
                    ]);
        }

        switch ($type)
        {
            case 'auth_second_recurring':
                $this->action = 'authorize';
                $xml = $this->authSecondRecurringOnGateway();
                break;
        }

        return $this->makeResponse($xml);
    }

    public function enroll()
    {
        $this->processInput('enroll');
        $this->setAction('enroll');

        $cardNumber = $this->data['card'];

        $res = [];
        if (($cardNumber === '4012001038488884') or
            ($cardNumber === '4012001036298889') or
            ($cardNumber === '6073840000000008'))
        {
            $res['result'] = 'FSS0001-Authentication Not Available';
            $res['PAReq'] = 'abcd';
            $res['paymentid'] = $this->getNewPaymentId();
            $res['trackid'] = $this->data['trackid'];

            if ($cardNumber === '6073840000000008')
            {
                $res['result'] = 'AUTH ERROR';
            }
        }
        else if ($cardNumber === '4000000000000002')
        {
            // mock timeout exception for enroll
            throw new \WpOrg\Requests\Exception('operation timed out', 'operation timed out');
        }
        else
        {
            $res = $this->getResponseParamsForEnroll();
        }

        $this->copyUdfValues($res);

        $this->content($res, $this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    public function authEnrolled()
    {
        $this->processInput('authEnrolled');
        $this->setAction('authorize');

        $res = $this->getAuthResponse($this->data['paymentid']);

        $this->content($res, $this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    public function preAuthorization()
    {
        $this->processInput('preAuthorize');
        $this->setAction('authorize');

        $res = $this->getPreAuthResponse();

        $this->content($res, $this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    protected function getAuthResponse($txnId, $cardNumber = null)
    {
        $gatewayTransaction = $this->getRepo()->findByGatewayTransactionIdOrFail($txnId);

        $card = $gatewayTransaction->payment->card;

        if ($gatewayTransaction === null)
        {
            throw new Exception\LogicException(
                'Transaction not found',
                null,
                [
                    'transaction_id' => $txnId,
                ]);
        }

        $res = array(
            'result'    => 'APPROVED',
            'auth'      => '999999',
            'ref'       => random_integer(12),
            'avr'       => 'N',
            'postdate'  => $this->getPostDateForToday(),
            'paymentid' => $txnId,
            'tranid'    => $txnId,
            'trackid'   => $gatewayTransaction['payment_id'],
            'amt'       => $gatewayTransaction['amount']);

        $networkCode = Network::getCode($card['network']);

        if (in_array($networkCode, $this->onlyPurchaseCardNetworks))
        {
            $res['result'] = 'CAPTURED';
        }

        $this->content($res, 'auth_response');

        // $this->copyUdfValues($res);

        return $res;
    }

    protected function getPreAuthResponse()
    {
        $txnId = $this->getNewPaymentId();

        $res = [
            'result'    => 'APPROVED',
            'auth'      => '999999',
            'ref'       => random_integer(12),
            'avr'       => 'N',
            'postdate'  => $this->getPostDateForToday(),
            'paymentid' => $txnId,
            'tranid'    => $txnId,
            'trackid'   => $this->data['trackid'],
            'amt'       => $this->data['amt'] / 100
        ];

        $this->content($res, 'preauth_response');

        return $res;
    }

    public function preAuth()
    {
        $this->processInput('debitPinAuthentication');

        $this->setAction('authorize');

        $res = $this->getDebitPinAuthResponse();

        $this->content($res,$this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    protected function getDebitPinAuthResponse()
    {
        $res = [
            'paymentId'    => random_integer(12),
            'paymenturl'   => $this->route->getUrl('mock_hdfc_3dsecure'),
            'result'       => 'INITIALIZED',
        ];

        $this->content($res, 'debit_pin_auth_response');

        return $res;
    }

    protected function getDebitPinAuthorizationResponse($txnid, $callbackUrl)
    {
        $gatewayTransaction = $this->getRepo()->findByGatewayPaymentIdOrFail($txnid);

        $res = [
            Hdfc\Fields::PAYMENT_ID       => $txnid,
            Hdfc\Fields::RESULT           => 'CAPTURED',
            Hdfc\Fields::AUTH             => '999999',
            Hdfc\Fields::AMOUNT           => $gatewayTransaction['amount'],
            Hdfc\Fields::REF              => random_integer(12),
            Hdfc\Fields::POSTDATE         => $this->getPostDateForToday(),
            Hdfc\Fields::TRACKID          => $gatewayTransaction['payment_id'],
            Hdfc\Fields::TRANID           => $txnid,
            Hdfc\Fields::UDF1             => $callbackUrl,
            Hdfc\Fields::UDF2             => $callbackUrl,
            Hdfc\Fields::UDF3             => 'test',
            Hdfc\Fields::UDF4             => 'test',
            Hdfc\Fields::UDF5             => '',
            Hdfc\Fields::AVR              => 'N',
            Hdfc\Fields::AUTH_RESP_CODE   => '00',
        ];

        $this->content($res,'debit_pin_authorization_response');

        return $res;
    }

    protected function authNotEnrolledOnGateway()
    {
        $type = $this->getRequestType(__FUNCTION__);

        return $this->getXMLForRequest($type);
    }

    protected function getXMLForRequest($type = null)
    {
        $this->processInput($type);

        $cardNumber = $this->data['card'];

        $network = $this->getCardNetwork($cardNumber);

        if ($this->isSpecialCardNumber($cardNumber))
        {
            $res = $this->handleSpecialCardNumber($cardNumber);
        }
        else
        {
            $res = $this->getDefaultPaymentSuccessArray();
            $res['result'] = 'APPROVED';

            // 4628481036290001 - credit card
            // 4012001037141112 - debit card
            if (($cardNumber === '4628481036290001') or
                ($cardNumber === '4012001037141112'))
            {
                $res['result'] = 'NOT APPROVED';
            }

            if ($network === 'MAES')
                $res['result'] = 'CAPTURED';

            $this->copyUdfValues($res);
        }

        $this->content($res, $this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function authSecondRecurringOnGateway()
    {
        $type = $this->getRequestType(__FUNCTION__);

        return $this->getXMLForRequest($type);
    }

    protected function getRequestType($function)
    {
        return explode('OnGateway', $function)[0];
    }

    protected function authOnGateway()
    {
        $cardNumber = $this->data['card'];

        if ($this->isSpecialCardNumber($cardNumber))
        {
            $res = $this->handleSpecialCardNumber($cardNumber);
        }
        else
        {
            $res = $this->getDefaultPaymentSuccessArray();
            $res['result'] = 'APPROVED';

            $this->copyUdfValues($res);
        }

        return $res;
    }

    protected function getResponseParamsForEnroll()
    {
        $cardNumber = $this->data['card'];

        $network = $this->getCardNetwork($cardNumber);
        $type = $this->getCardType($cardNumber, $network);

        $res = array();

        // Debit and prepaid cards are "ENROLLED" cards and we're returning the enrolled response.
        if (($type === 'debit') or
            ($type === 'prepaid'))
        {
            $res = $this->getResponseParamsForEnrollDebit();
        }

        if (($type === 'credit') or
            ($type === '') or
            (in_array($cardNumber, $this->notEnrolledDebitCardNumbers, true) === true))
        {
            $res['result'] = 'NOT ENROLLED';
            $res['eci'] = $this->getEci($network);
        }

        $resCommon = array(
            'paymentid' => $this->getNewPaymentId(),
            'trackid'   => $this->data['trackid'],
            'PAReq'     => 'abcsafsf');

        $res = array_merge($res, $resCommon);

        return $res;
    }

    protected function getResponseParamsForEnrollDebit()
    {
        $res['result'] = 'ENROLLED';

        $res['url'] = $this->route->getUrl('mock_hdfc_3dsecure');

        return $res;
    }

    protected function getCardType($cardNumber, $network)
    {
        if (in_array($cardNumber, $this->debitCardNumbers))
        {
            return 'debit';
        }
        else if ($network === Card\Network::MAES)
        {
            return 'debit';
        }

        $iin = substr($cardNumber, 0, 6);
        $cardDetails = (new Card\Repository)->retrieveIinDetails($iin);

        if ($cardDetails === null)
            return '';

        return $cardDetails->getType();
    }

    protected function getEci($network)
    {
        $eci = null;

        if (($network === Card\Network::VISA) or
            ($network === Card\Network::DICL))
        {
            $eci = 6;
        }

        if (($network === Card\Network::MC) or
            ($network === Card\Network::MAES))
        {
            $eci = 1;
        }

        return $eci;
    }

    protected function makeResponse($xml)
    {
        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function capturePaymentOnGateway()
    {
        $this->processInput('supportPayment');

        $payment = $this->getByGatewayTxnIdAndStatusExist('authorized');

        $res = $this->getDefaultPaymentSuccessArray();
        $res['trackid'] = $payment['payment_id'];
        $res['result'] = 'CAPTURED';

        $res['udf2'] = (isset($this->data['udf2'])) ? $this->data['udf2'] : '';
        $res['udf5'] = (isset($this->data['udf5'])) ? $this->data['udf5'] : '';

        $this->content($res, $this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function refundPaymentOnGateway()
    {
        $this->processInput('supportPayment');

        // assert ($this->data['udf5'] === 'PaymentID');

        $this->request($this->data, $this->action);

        $payment = $this->getByGatewayTxnIdAndStatusExist('captured');

        if ($payment === false)
        {
            $res = $this->getTxnNotFoundError();
        }

        $res = $this->getDefaultPaymentSuccessArray();

        $res['trackid'] = $this->data['trackid'];
        $res['result'] = 'CAPTURED';

        $res['udf2'] = (isset($this->data['udf2'])) ? $this->data['udf2'] : '';
        $res['udf5'] = (isset($this->data['udf5'])) ? $this->data['udf5'] : '';

        $this->content($res, $this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function inquirePaymentOnGateway()
    {
        $this->processInput('inquiry');

        $gatewayTxnId = $this->data['transid'];

        $txn = $this->getRepo()->findByGatewayTransactionIdAndStatus(
              $gatewayTxnId, 'authorized');

        if ($txn === null)
        {
            $txn = $this->getRepo()->findByPaymentIdAndStatus(
                $gatewayTxnId, 'captured')->first();

        }

        $network = null;

        if (isset($this->data['card']))
        {
            $network = $this->getCardNetwork($this->data['card']);
        }
        //TODO:: not throwing txn not found error(correct this code)
        if ($txn === null)
        {
            $res = $this->getTxnNotFoundError();
        }

        $res = array(
            'result'    => 'SUCCESS',
            'auth'      => $txn['auth'],
            'ref'       => $txn['ref'],
            'avr'       => $txn['avr'],
            'postdate'  => $txn['postdate'],
            'tranid'    => $txn['gateway_transaction_id'],
            'trackid'   => $this->data['transid'],
            'payid'     => '-1',
            'amt'       => $this->data['amt'] );

        if ($network === Card\Network::RUPAY)
        {
            $res['result'] = 'SUCCESS';
        }

        $this->content($res, $this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function getByGatewayTxnIdAndStatusExist($status)
    {
        $gatewayTxnId = $this->data['transid'];

        $txn = $this->getRepo()->findByGatewayTransactionIdAndStatus(
                                        $gatewayTxnId, $status);

        if (($txn === null) and
            ($status === 'captured'))
        {
            $txn = $this->getRepo()->findByGatewayTransactionIdAndErrorCode(
                                            $gatewayTxnId, Hdfc\ErrorCodes\ErrorCodes::GW00176);
        }

        return $txn;
    }

    protected function processInput($name)
    {
        $input = $this->input;

        $this->gateway = new Gateway;
        $fields = $this->gateway->getRequestFields($name);

        $this->data = [];

        Hdfc\Utility::getFieldsFromXML(
            $input,
            $fields,
            $this->data);
    }

    protected function copyUdfValues(array & $res)
    {
        $r = range(1, 5);

        foreach ($r as $i)
        {
            $res['udf'.$i] = $this->data['udf'.$i];
        }
    }

    protected function getPostDateForToday()
    {
        return (new Carbon('now', Timezone::IST))->format('md');
    }

    protected function getNewPaymentId()
    {
        return random_integer(16);
    }

    protected function getDefaultPaymentSuccessArray()
    {
        $res = array(
            'auth'      => '999999',
            'ref'       => random_integer(12),
            'avr'       => 'N',
            'postdate'  => $this->getPostDateForToday(),
            'tranid'    => random_integer(15),
            'trackid'   => $this->data['trackid'],
            'payid'     => -1,
            'amt'       => $this->data['amt']);

        return $res;
    }

    protected function getTxnNotFoundError()
    {
        $res['error_code_tag'] = 'GW00201';
        $res['result'] = '!ERROR!-GW00201-Transaction not found';
        $res['error_service_tag'] = '';

        return $res;
    }

    protected function isSpecialCardNumber($cardNumber)  // nosemgrep : razorpay:card_pii_data_parameters
    {
        return (in_array($cardNumber, $this->specialCardNumbers));
    }

    protected function handleSpecialCardNumber($cardNumber) // nosemgrep : razorpay:card_pii_data_parameters
    {
        $error = array();
        $error['error_service_tag'] = null;

        switch ($cardNumber)
        {
            case '4012001036853337':
                $code = Hdfc\ErrorCodes\ErrorCodes::GV00007;
                break;

            case '4012001036983332':
                $code = Hdfc\ErrorCodes\ErrorCodes::GV00008;
                break;

            case '4012001037461114':
                $code = Hdfc\ErrorCodes\ErrorCodes::GV00004;
                break;

            case '4012001037484447':
            case '4012001037490006':
                $code = Hdfc\ErrorCodes\ErrorCodes::FSS0001;
                break;

            default:
                throw new \LogicException('Card number given here is not special');
        }

        $errorText = Hdfc\ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription([ 'code' => $code]);

        $error['error_code_tag'] = $code;
        $error['error_text'] = '!ERROR!-'.$code . '-' . $errorText;
        $error['error_service_tag'] = '';

        // @todo: figure out exactly how and when to send 'result' field
        $error['result'] = $code . '-' . $errorText;

        return $error;
    }

    public function debitPinAuth()
    {
        $this->processInput('debitPinAuthentication');

        $this->setAction('authorize');

        $res = $this->getDebitPinAuthResponse();

        $this->content($res,$this->action);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    protected function getCardNetwork($number)
    {
        $iin = substr($number, 0, 6);

        return Card\Network::detectNetwork($iin);
    }
}
