<?php

namespace Gateway\MockHdfc;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Action;
use Gateway\MockHdfc;
use Models\Card;
use ReflectionClass;
use Requests;

class Gateway extends Hdfc\Gateway
{
    protected $request;

    protected $input;

    protected $data = array();

    protected $response = array();

    public function __construct()
    {
        parent::__construct();

        $this->request = \Request::getFacadeRoot();
    }

    public function generateMpr()
    {
        $mpr = (new MockHdfc\Repository)->getUnreportedTransactions();

        $mprHeadings = array_keys($mpr->first()->toArrayForMprReport());

        foreach($mprHeadings as &$heading)
        {
            $heading = strtoupper($heading);
            $heading = str_replace('_', ' ', $heading);
        }

        $mprArray = array();
        array_push($mprArray, $mprHeadings);

        foreach($mpr->all() as $row)
        {
            array_push($mprArray, array_values($row->toArrayForMprReport()));
        }

        $fp = fopen('hdfc_mpr.xlsx', 'w');

        foreach ($mprArray as $row)
        {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return 'hdfc_mpr.xlsx';
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $serviceTaxPercent = 12;
        $educationCessPercent = 0.36;

        $request = $this->supportTxnRequest;
        $data = $request['data'];
        $amount = $data['amt'];
        $msf = $amount * 2 / 100;
        $serviceTax = $amount * 12 / 100;
        $educationCess = $amount * 0.36 / 100;
        $netAmount = $amount - $msf;

        $attributes = array(
            'merchant_code'     => $this->terminal['gateway_merchant_id'],
            'terminal_number'   => $this->terminal['gateway_terminal_id'],
            'rfc_fmt'           => 'BAT',
            'bat_nbr'           => 1,
            'card_type'         => $this->input['txn']['card']['network'] . ' ' . 'LOCAL',
            'card_number'       => $this->input['txn']['card']['iin'] . 'xxxxxx' . $this->input['txn']['card']['last4'],
            'trans_date'        => (new Carbon('now'))->format('d-M-y'),
            'settle_date'       => (new Carbon('now'))->format('d-M-y'),
            'approv_code'       => '000000',
            'intl_amt'          => 0,
            'domestic_amt'      => $amount,
            'tran_id'           => $this->supportTxnResponse['data']['tranid'],
            'upvalue'           => '`',
            'merchant_trackid'  => $this->input['txn']['id'],
            'msf'               => $msf,
            'service_tax'       => $serviceTax,
            'edu_cess'          => $educationCess,
            'net_amount'        => $netAmount,
            'debitcredit_type'  => 'CC',
            'udf1'              => '',
            'udf2'              => '',
            'udf3'              => '',
            'udf4'              => '',
            'udf5'              => '',
            'sequence_number'   => $this->supportTxnResponse['data']['ref'],
            'mpr_generated'     => 0,
        );

        $mprGenerator = new MprGenerator($attributes);

        (new MockHdfc\Repository)->saveOrFail($mprGenerator);
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
                $xml = $this->captureTransactionOnGateway();
                break;

            case Action::REFUND:
                $xml = $this->refundTransactionOnGateway();
                break;

            default:
                throw new Exception\LogicException(
                    'MockHdfc: Action code not recognized. Action: ' . $this->data['action']);
        }

        return $this->makeResponse($xml);
    }

    public function enroll()
    {
        $this->processInput('enroll');

        $iin = substr($this->data['card'], 0, 6);

        $network = Card\Network::detectNetwork($iin);

        $eci = null;
        if (($network === Card\Network::VISA) or
            ($network === Card\Network::DICL))
            $eci = 6;

        if (($network === Card\Network::MC) or
            ($network === Card\Network::MAES))
            $eci = 1;

        $paymentId = random_integer(16);

        $res = array(
            'result'    => 'NOT ENROLLED',
            'eci'       => $eci,
            'paymentid' => $paymentId,
            'trackid'   => $this->data['trackid'],
            'PAReq'     => 'abcsafsf');

        $this->copyUdfValues($res);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    public function authEnrolled()
    {
        $this->processInput();
    }

    protected function authNotEnrolledOnGateway()
    {
        $this->processInput('authNotEnrolled');

        $res = array(
            'result'    => 'APPROVED',
            'auth'      => '999999',
            'ref'       => random_integer(12),
            'avr'       => 'N',
            'postdate'  => $this->getPostDateForToday(),
            'tranid'    => random_integer(15),
            'trackid'   => $this->data['trackid'],
            'payid'     => -1,
            'amt'       => $this->data['amt']);

        $this->copyUdfValues($res);

        $xml = Hdfc\Utility::createXml($res);

        return $this->makeResponse($xml);
    }

    protected function makeResponse($xml)
    {
        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }

    protected function captureTransactionOnGateway()
    {
        $this->processInput('supportTxn');

        $res = array(
            'result'    => 'CAPTURED',
            'auth'      => '999999',
            'ref'       => random_integer(12),
            'avr'       => 'N',
            'postdate'  => $this->getPostDateForToday(),
            'tranid'    => random_integer(15),
            'trackid'   => $this->data['trackid'],
            'payid'     => -1,
            'amt'       => $this->data['amt']);

        $res['udf2'] = (isset($this->data['udf2'])) ? $this->data['udf2'] : '';
        $res['udf5'] = (isset($this->data['udf5'])) ? $this->data['udf5'] : '';

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function refundTransactionOnGateway()
    {
        $this->processInput('supportTxn');

        $res = array(
            'result'    => 'CAPTURED',
            'auth'      => '999999',
            'ref'       => random_integer(12),
            'avr'       => 'N',
            'postdate'  => $this->getPostDateForToday(),
            'tranid'    => random_integer(15),
            'trackid'   => $this->data['trackid'],
            'payid'     => -1,
            'amt'       => $this->data['amt']);

        $res['udf2'] = (isset($this->data['udf2'])) ? $this->data['udf2'] : '';
        $res['udf5'] = (isset($this->data['udf5'])) ? $this->data['udf5'] : '';

        $xml = Hdfc\Utility::createXml($res);

        return $xml;
    }

    protected function sendGatewayRequest($request)
    {
        $request['url'] = $this->getMockRequestUrl($request['url']);

        return Requests::post(
                    $request['url'],
                    $request['header'],
                    $request['xml'],
                    $request['options']);
    }

    protected function getMockRequestUrl($url)
    {
        $rc = new ReflectionClass('Gateway\Hdfc\Urls');
        $urls = $rc->getConstants();

        foreach ($urls as $name => $hdfcUrl)
        {
            if ($url === $hdfcUrl)
            {
                return $this->makeMockRequestUrl($name);
            }
        }
    }

    protected function makeMockRequestUrl($name)
    {
        $url = constant('Gateway\MockHdfc\Urls::'.$name);

        $scheme = $this->request->getScheme().'://';
        $host = $this->request->getHost();
        $key = 'rzp_test';
        $secret = 'DASHBOARD_AUTH_PASS';

        if ($host === 'localhost')
            $host = 'rzp';

        $url = $scheme . $key . ':' . $secret. '@' . $host . '/v1/' . $url;

        return $url;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    protected function processInput($name)
    {
        $input = $this->input;

        $var = $name.'Request';

        $array = $this->$var;
        $fields = $array['fields'];

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
}