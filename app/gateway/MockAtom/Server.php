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

        $this->repo = new Atom\Repository;
    }

    public function netBankingTransactionChooseBank($input)
    {
        $this->verifyTxn1stStageInput($input);

        $atom = $this->repo->findByToken($input['token']);

        $merchant = \BasicAuth::getMerchant();

        $paymentId = $atom->getKey();

        $paymentRepo = new \Models\Payment\Repository;
        $payment = $paymentRepo->findByIdAndMerchantId($paymentId, $merchant->getId());

        $data['url'] = $this->getRzpBankPageUrl();
        $data['tempTxnId'] = $input['tempTxnId'];

        return $data;
    }

    public function initiateNetBankingTransaction($input)
    {
        $tempTxnId = random_integer(9);
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
        $key = \BasicAuth::getPublicKey();

        $query = [];
        $url = \Http\Route::getUrl('mockatom_choose_bank', $query, $key);

        return $url;
    }

    protected function getRzpBankPageUrl()
    {
        $key = \BasicAuth::getPublicKey();

        $url = \Http\Route::getUrl('mockatom_rzp_bank', array(), $key);

        return $url;
    }

    protected function getRzpBankPageSubmitUrl()
    {
        $key = \BasicAuth::getPublicKey();

        $url = \Http\Route::getUrl('mockatom_rzp_bank_submit', array(), $key);

        return $url;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function capture(array $input)
    {
        ;
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

        $time = Carbon::now('Asia/Kolkata')->format('D M d H:i:s \G\M\T+05:30 Y');

        $data = array(
            'mmp_txn'       => $tempTxnId,
            'mer_txn'       => $payment->getPublicId(),
            'amt'           => $payment->getAmount() / 100 . '00',
            'prod'          => 'NSE',
            'date'          => $time,
            'bank_txn'      => $tempTxnId.'1',
            'f_code'        => $success,
            'clientcode'    => '123',
            'bank_name'     => 'Razorpay Bank',
            'udf9'          => '',
            'discriminator' => 'NB',
            'desc'          => 'abcdef',
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
        $token = \Models\Base\UniqueIdEntity::generateUniqueId();

        return $token;
    }
}
