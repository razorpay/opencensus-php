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
    public function __construct()
    {
        parent::__construct();

        $this->request = \Request::getFacadeRoot();

        $this->mockHdfcServer = \Config::get('gateway.mockhdfc_server');
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

    protected function sendGatewayRequest($request)
    {
        if ($this->mockHdfcServer)
        {
            $request['url'] = $this->getMockRequestUrl($request['url']);

            return parent::sendGatewayRequest($request);
        }
        else
        {
            $serverResponse = $this->callGatewayRequestFunctionInternally($request);

            $response = new \Requests_Response();

            $response->headers['Content-Type']  = 'application/xml; charset=UTF-8';
            $response->headers['Cache-Control']  = 'no-cache';

            $response->body = $serverResponse->getContent();
            $response->status_code = 200;
            $response->success = true;
            // @todo: add url to response var

            return $response;
        }
    }

    protected function callGatewayRequestFunctionInternally($requestVar)
    {
        $server = new Server();
        $server->setInput($requestVar['xml']);

        $response = null;

        switch($requestVar['type'])
        {
            case 'enroll':
                $response = $server->enroll();
                break;

            case 'auth_enrolled':
                $response = $server->authEnrolled();
                break;

            case 'auth_not_enrolled':
            case 'capture':
            case 'refund':
                $response = $server->gatewayTransaction();
                break;

            default:
                throw new Exception\LogicException('Unrecognized request type: ' . $requestVar['type']);
        }

        return $response;
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

    public function getRequestFields($name)
    {
        $var = $name.'Request';

        $array = $this->$var;
        $fields = $array['fields'];

        return $fields;
    }
}