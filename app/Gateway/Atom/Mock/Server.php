<?php

namespace RZP\Gateway\Atom\Mock;

use RZP\Models\Card;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Atom;
use RZP\Gateway\Atom\Mock;
use RZP\Gateway\Base;
use RZP\Models\Payment;

class Server extends Base\Mock\Server
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Atom\Repository;
    }

    public function atomPaymentChooseOrg($input)
    {
        $this->checkReferer();

        $this->verifyTxn1stStageInput($input);

        $atom = $this->repo->findByToken($input['token']);

        $merchant = \BasicAuth::getMerchant();

        $paymentId = $atom->getKey();

        $payment = (new Payment\Repository)->findByIdAndMerchantId(
                                            $paymentId, $merchant->getId());

        $data['url'] = $this->getRzpPaymentPageUrl();

        $data['tempTxnId'] = $input['tempTxnId'];
        $data['method'] = $payment['method'];

        return $data;
    }

    public function authorize($input)
    {
        $tempTxnId = random_integer(9);
        $token = $this->generateToken();
        $ttype = $input['ttype'];
        $url = $this->getSecondRequestUrl($ttype);

        $xml = $this->formInitiatePaymentXml($tempTxnId, $token, $ttype, $url);

        return $this->makeResponse($xml);
    }

    protected function makeResponse($xml)
    {
        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    protected function getSecondRequestUrl($ttype)
    {
        return $this->route->getUrlWithPublicAuth('mock_atom_choose_org');
    }

    protected function getRzpPaymentPageUrl()
    {
        $url = $this->route->getUrlWithPublicAuth('mock_atom_rzp_payment');

        return $url;
    }

    protected function getRzpPaymentPageSubmitUrl()
    {
        $url = $this->route->getUrlWithPublicAuth('mock_atom_rzp_payment_submit');

        return $url;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function capture($input)
    {
        parent::capture($input);
    }

    public function atomRzpPayment($input)
    {
        $this->checkReferer();

        // For net-banking, show the bank choice auto-submit page
        // For card show random stuff

        $atom = $this->getAtomPaymentByTempTxnId($input['tempTxnId']);

        $payment = (new \RZP\Models\Payment\Repository)->findOrFail($atom['id']);

        $bankTxnId = random_integer(6);

        $amount = $payment['amount'] / 100;
        if (is_int($amount))
            $amount .= '.00';

        $data = array(
            'tempTxnId' => $input['tempTxnId'],
            'ITC' => $bankTxnId,
            'BID' => $bankTxnId . '1',
            'amount' => $amount,
            'url' => $this->getRzpPaymentPageSubmitUrl(),
            'clientCode' => '007');

        return $data;
    }

    public function atomRzpPaymentPageSubmit($input)
    {
        $this->checkReferer();

        $tempTxnId = $input['tempTxnId'];

        $success = $input['success'];
        $success = ($success === 'S') ? 'Ok' : $success;

        $atom = $this->getAtomPaymentByTempTxnId($input['tempTxnId']);

        $paymentId = $atom['id'];

        $payment = (new Payment\Repository)->findOrFail($paymentId);
        $method = $payment['method'];
        $card = null;

        $publicId = $payment->getPublicId();
        $merchantCallbackUrl = $this->formMerchantCallbackUrl($publicId);

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
            'desc'          => 'abcdef',
            'surcharge'     => '0.0',
            'CardNumber'    => '');

        if ($method === 'netbanking')
        {
            $data['discriminator'] = 'NB';
            $data['CardNumber'] = '';
        }
        else if ($method === 'card')
        {
            $data['discriminator'] = 'DC';

            $card = $payment->card;

            if ($card->getType() === Card\Type::CREDIT)
                $data['discriminator'] = 'CC';

            $xx = str_repeat('X', $card['length'] - 10);
            $data['CardNumber'] = $card['iin'] . $xx . $card['last4'];
        }

        $x = range(1,6);
        foreach ($x as $n)
        {
            $data['udf'.$n] = 'null';
        }

        return array($merchantCallbackUrl, $data);
    }

    public function verify($input)
    {
        $id = $input['merchanttxnid'];
        $merchantId = $input['merchantid'];
        $amt = $input['amt'];

        $publicId = $id;

        $id = Atom\Entity::verifyIdAndStripSign($id);
        $payment = (new Atom\Repository)->find($id);

        $status = (bool) $payment['success'];
        $verified = ($status) ? 'SUCCESS' : 'FAILED';

        $bid = null;
        if ($status)
        {
            $bid = $payment['bank_payment_id'];
        }

        $xml = '
        <?xml version="1.0" encoding="UTF-8" ?>
            <VerifyOutput
                MerchantID="'.$merchantId.'"
                MerchantTxnID="'.$publicId.'"
                AMT="'.$amt.'"
                VERIFIED="'.$verified.'"
                BID="'.$bid.'"
                bankname="'.$payment['bank_name'].'"
                atomtxnId="'.$payment['gateway_payment_id'].'"
            />';

        return $this->makeResponse($xml);
    }

    protected function formMerchantCallbackUrl($paymentPublicId)
    {
        $callbackUrl = $this->route->getPublicCallbackUrlWithHash($paymentPublicId);

        return $callbackUrl;
    }

    public function verifyTxn1stStageInput($input)
    {
        return [];
    }

    protected function getAtomPaymentByTempTxnId($tempTxnId)
    {
        return (new Atom\Repository)->findByGatewayPaymentId($tempTxnId);
    }

    protected function generateAtomToken()
    {
        $token = bin2hex(openssl_random_pseudo_bytes(46/2));
        $token .= 'z'.'%3D';

        return $token;
    }

    protected function formInitiatePaymentXml($tempTxnId, $token, $ttype, $url)
    {
        $str = ''.
        '<?xml version="1.0" encoding="UTF-8"?>
            <MMP><MERCHANT><RESPONSE>
                <url>'.$url.'</url>
                <param name="ttype">'.$ttype.'</param>
                <param name="tempTxnId">'.$tempTxnId.'</param>
                <param name="token">'.$token.'</param>
                <param name="txnStage">1</param>
            </RESPONSE></MERCHANT></MMP>';

        return $str;
    }

    protected function generateToken()
    {
        $token = \RZP\Models\Base\UniqueIdEntity::generateUniqueId();

        return $token;
    }
}
