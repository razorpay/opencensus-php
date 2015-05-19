<?php

namespace Gateway\Hdfc\Mock;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Payment\Action;
use Gateway\Hdfc\Mock;
use Models\Card;

class Server
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
        '4012001037141112',
        );

    public function __construct()
    {
        $this->request = \Request::getFacadeRoot();

        $this->gateway = new Gateway;
    }

    public function threeDSecure($input)
    {
        return $input;
    }

    public function gatewayTransaction()
    {
        $action = Hdfc\Utility::getFieldFromXML($this->input, 'action');

        switch ($action)
        {
            case Action::AUTHORIZE:
                $xml = $this->authNotEnrolledOnGateway();
                break;

            case Action::CAPTURE:
                $xml = $this->capturePaymentOnGateway();
                break;

            case Action::REFUND:
                $xml = $this->refundPaymentOnGateway();
                break;

            case Action::INQUIRY:
                $xml = $this->inquirePaymentOnGateway();
                break;

            default:
                throw new Exception\LogicException(
                    'Hdfc\Mock: Action code not recognized. Action: ' . $this->data['action']);
        }

        return $this->makeResponse($xml);
    }

    public function enroll()
    {
        $this->processInput('enroll');

        $cardNumber = $this->data['card'];

        $res = [];
        if (($cardNumber === '4012001038488884') or
            ($cardNumber === '4012001036298889'))
        {
            $res['result'] = 'FSS0001-Authentication Not Available';
            $res['PAReq'] = 'abcd';
            $res['paymentid'] = $this->getNewPaymentId();
            $res['trackid'] = $this->data['trackid'];
        }
        else
        {
            $res = $this->getResponseParamsForEnroll();
        }

        $this->copyUdfValues($res);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    public function authEnrolled()
    {
        $this->processInput('authEnrolled');

        $txnId = $this->data['paymentid'];

        $gatewayTransaction = (new Hdfc\Repository)->findByGatewayTransactionIdOrFail($txnId);

        if ($gatewayTransaction === null)
        {
            throw new Exception\LogicException($txnId . ' not found');
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

//        $this->copyUdfValues($res);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    protected function authNotEnrolledOnGateway()
    {
        $this->processInput('authNotEnrolled');

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

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
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

        // @todo: move this to iin
        $iin = substr($cardNumber, 0, 6);
        $network = Card\Network::detectNetwork($cardNumber);
        $type = $this->getCardType($cardNumber, $iin);

        $iin = substr($cardNumber, 0, 6);

        $network = Card\Network::detectNetwork($cardNumber);
        $type = $this->getCardType($cardNumber, $iin);

        $res = array();
        if ($type === 'debit')
        {
            $res = $this->getResponseParamsForEnrollDebit();
        }
        else if (($type === 'credit') or
                 ($type === ''))
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

        $request = \Request::getFacadeRoot();
        $scheme = $request->getScheme().'://';
        $host = $request->getHost();

        $res['url'] = $scheme . $host . '/gateway/3dsecure';

        return $res;
    }

    protected function getCardType($cardNumber, $iin)
    {
        if (in_array($cardNumber, $this->debitCardNumbers))
        {
            return 'debit';
        }

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
            $eci = 6;

        if (($network === Card\Network::MC) or
            ($network === Card\Network::MAES))
            $eci = 1;

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

        $exists = $this->checkGatewayTxnIdAndStatusExist('authorized');

        if ($exists === false)
        {
            $res = $this->getTxnNotFoundError();
        }
        else
        {
            $res = $this->getDefaultPaymentSuccessArray();
            $res['result'] = 'CAPTURED';

            $res['udf2'] = (isset($this->data['udf2'])) ? $this->data['udf2'] : '';
            $res['udf5'] = (isset($this->data['udf5'])) ? $this->data['udf5'] : '';
        }

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function refundPaymentOnGateway()
    {
        $this->processInput('supportPayment');

        $exists = $this->checkGatewayTxnIdAndStatusExist('captured');

        if ($exists === false)
        {
            $res = $this->getTxnNotFoundError();
        }

        $res = $this->getDefaultPaymentSuccessArray();
        $res['result'] = 'CAPTURED';

        $res['udf2'] = (isset($this->data['udf2'])) ? $this->data['udf2'] : '';
        $res['udf5'] = (isset($this->data['udf5'])) ? $this->data['udf5'] : '';

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function inquirePaymentOnGateway()
    {
        $this->processInput('inquiry');

        $gatewayTxnId = $this->data['transid'];

        $txn = (new Hdfc\Repository)->findByGatewayTransactionIdAndStatus(
            $gatewayTxnId, 'authorized');

        if ($txn === null)
        {
            $res = $this->getTxnNotFoundError();
        }

        $res = array(
            'result'    => 'APPROVED',
            'auth'      => $txn['auth'],
            'ref'       => $txn['ref'],
            'avr'       => $txn['avr'],
            'postdate'  => $txn['postdate'],
            'tranid'    => $txn['gateway_transaction_id'],
            'trackid'   => $txn['payment_id'],
            'payid'     => '-1',
            'amt'       => $txn['amount'] / 100);

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function checkGatewayTxnIdAndStatusExist($status)
    {
        $gatewayTxnId = $this->data['transid'];

        $txn = (new Hdfc\Repository)->findByGatewayTransactionIdAndStatus($gatewayTxnId, $status);

        return ($txn !== null);
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    protected function processInput($name)
    {
        $input = $this->input;

        $fields = $this->gateway->getRequestFields($name);

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
        return (new Carbon('now', 'Asia/Kolkata'))->format('md');
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

    protected function isSpecialCardNumber($cardNumber)
    {
        return (in_array($cardNumber, $this->specialCardNumbers));
    }

    protected function handleSpecialCardNumber($cardNumber)
    {
        if (in_array($cardNumber, $this->specialCardNumbers) === false)
        {
            throw new \LogicException('Card number given here is not special. Number: ' . $cardNumber);
        }

        $error = array();
        $error['error_service_tag'] = null;

        switch ($cardNumber)
        {
            case '4012001036275556':
                sleep(Hdfc\Config::TIMEOUT);
                exit(1);
                break;

            case '4012001036853337':
                $code = Hdfc\ErrorCode::GV00007;
                break;

            case '4012001036983332':
                $code = Hdfc\ErrorCode::GV00008;
                break;

            case '4012001037461114':
                $code = Hdfc\ErrorCode::GV00004;
                break;

            case '4012001037484447':
            case '4012001037490006':
                $code = Hdfc\ErrorCode::FSS0001;
                break;

            default:
                throw new \LogicException('Card number given here is notn special. Number: ' . $cardNumber);
        }

        $error['error_code_tag'] = $code;
        $error['error_text'] = '!ERROR!-'.$code.'-'.Hdfc\ErrorCode::$errorMessages[$code];
        $error['error_service_tag'] = '';
        $error['result'] = $code.'-'.Hdfc\ErrorCode::$errorMessages[$code];
        // @todo: figure out exactly how and when to send 'result' field

        return $error;
    }
}