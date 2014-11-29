<?php

namespace Gateway\MockAtom;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Atom;
use Gateway\MockAtom;
use Models\Payment;

class Server
{
    public function __construct()
    {
        $this->request = \Request::getFacadeRoot();
    }

    public function netBankingTransactionChooseBank($input)
    {
        $this->verifyTxn1stStageInput($input);

        $data['url'] = $this->getRzpBankPageUrl();
        $data['tempTxnId'] = $input['tempTxnId'];

        return $data;
    }

    public function initiateNetBankingTransaction($input)
    {
        $tempTxnId = random_integer(6);
        $token = $this->generateToken();
        $url = $this->getNetBankingAtomMockUrl();

        $xml = $this->formInitiateTransactionXml($tempTxnId, $token, $url);

        return $this->makeResponse($xml);
    }

    protected function makeResponse($xml)
    {
        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function getNetBankingAtomMockUrl()
    {
        $url = \URL::route('mockatom_choose_bank', array(), false);
        $url = str_replace('?', '', $url);

        $scheme = \Request::getScheme().'://';
        $host = \Request::getHost();
        $key = \BasicAuth::getPublicKey();

        $chooseBankUrl = $scheme . $key . '@' . $host . $url;

        return $chooseBankUrl;
    }

    protected function getRzpBankPageUrl()
    {
        $url = \URL::route('mockatom_rzp_bank', array(), false);

        $scheme = \Request::getScheme().'://';
        $host = \Request::getHost();
        $key = \BasicAuth::getPublicKey();

        $key = 'rzp_test';
        $secret = 'DASHBOARD_AUTH_PASS';

        $rzpBankPageUrl = $scheme . $key . ':' . $secret . '@' . $host . $url;

        return $rzpBankPageUrl;
    }

    protected function getRzpBankPageSubmitUrl()
    {
        $url = \URL::route('mockatom_rzp_bank_submit', array(), false);

        $scheme = \Request::getScheme().'://';
        $host = \Request::getHost();
        $key = \BasicAuth::getPublicKey();

        $key = 'rzp_test';
        $secret = 'DASHBOARD_AUTH_PASS';

        $rzpBankPageSubmitUrl = $scheme . $key . ':' . $secret . '@' . $host . $url;

        return $rzpBankPageSubmitUrl;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function capture(array $input)
    {
        $tempTxnId = random_integer(6);
        $token = $this->generateAtomToken();

        return $this->getXmlFormattedResponse($tempTxnId, $token);
    }

    public function atomRzpBankPage($input)
    {
        $bankTxnId = random_integer(6);

        $data = array(
            'tempTxnId' => $input['tempTxnId'],
            'ITC' => $bankTxnId,
            'BID' => $bankTxnId . '1',
            'amount' => '50.0000',
            'url' => $this->getRzpBankPageSubmitUrl(),
            'clientCode' => '007');

        return $data;
    }

    public function atomRzpBankSubmit($input)
    {

        ;
    }

    public function atomRzpBankPageSubmit($input)
    {
        $tempTxnId = $input['tempTxnId'];

        $success = $input['success'];
        $success = ($success === 'S') ? 'Ok' : $success;

        $atom = Atom\Entity::where('gateway_payment_id', '=', $input['tempTxnId'])
                           ->firstOrFail();

        $paymentId = $atom['id'];

        $payment = (new Payment\Repository)->findOrFail($paymentId);
        $keyId = 'rzp_test_'.$payment->merchant->keys[0]->getKey();

        $publicId = $payment->getPublicId();
        $merchantCallbackUrl = $this->formMerchantCallbackUrl($publicId, $keyId);

        $data = array(
            'mmp_txn'       => $tempTxnId,
            'mer_txn'       => $payment->getPublicId(),
            'amt'           => $payment->getAmount() / 100 . '00',
            'prod'          => 'NSE',
            'date'          => 'Sat Nov 29..',
            'bank_txn'      => $tempTxnId.'1',
            'f_code'        => $success,
            'clientcode'    => '123',
            'bank_name'     => 'Razorpay Bank',
            'udf9'          => '',
            'discriminator' => 'NB',
            'surcharge'     => '0.0',
            'CardNumber'    => '');

        $x = range(1,6);
        foreach ($x as $n)
        {
            $data['udf'.$n] = 'null';
        }

        return array($merchantCallbackUrl, $data);
    }

    protected function formMerchantCallbackUrl($paymentPublicId, $key)
    {
        $url = \URL::route('payment_callback', ['id' => $paymentPublicId], false);

        $scheme = \Request::getScheme().'://';
        $host = \Request::getHost();

        $callbackUrl = $scheme . $key . '@' . $host . $url;

        return $callbackUrl;
    }

    public function verifyTxn1stStageInput($input)
    {
        return [];
    }

    protected function generateAtomToken()
    {
        $token = bin2hex(openssl_random_pseudo_bytes(46/2));
        $token .= 'z'.'%3D';

        return $token;
    }

    protected function formInitiateTransactionXml($tempTxnId, $token, $url)
    {
        $str = ''.
        '<?xml version="1.0" encoding="UTF-8"?>
            <MMP><MERCHANT><RESPONSE>
                <url>'.$url.'</url>
                <param name="ttype">NBFundTransfer</param>
                <param name="tempTxnId">'.$tempTxnId.'</param>
                <param name="token">'.$token.'</param>
                <param name="txnStage">1</param>
            </RESPONSE></MERCHANT></MMP>';

        return $str;
    }

    protected function getBankPageUrl()
    {
        ;
    }

    public function netBankingPage()
    {
        ;
    }

    protected function generateToken()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ%';
        $length = 45;

        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Seems like all atom tokens end with this for some reason!
        $token .= '%3D';
        return $token;
    }
}
