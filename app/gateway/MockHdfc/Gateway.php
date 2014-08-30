<?php

namespace Gateway\MockHdfc;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Action;
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

        return $xml;
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

        return $xml;
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

        return $xml;
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