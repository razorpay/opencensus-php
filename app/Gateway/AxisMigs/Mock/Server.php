<?php

namespace RZP\Gateway\AxisMigs\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Gateway\AxisMigs;
use RZP\Gateway\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Gateway\AxisMigs\Action;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        parent::authorize($input);

        $this->validateAuthorizeInput($input);

        $date = Carbon::today(Timezone::IST)->format('Ymd');

        $content = array(
            'vpc_AVSRequestCode'    => 'Z',
            'vpc_AVSResultCode'     => 'Unsupported',
            'vpc_AcqAVSRespCode'    => 'Unsupported',
            'vpc_AcqCSCRespCode'    => 'Unsupported',
            'vpc_AcqResponseCode'   => '00',
            'vpc_Amount'            => $input['vpc_Amount'],
            'vpc_AuthorizeId'       => rand(111111, 999999),
            'vpc_BatchNo'           => $date,
            'vpc_CSCResultCode'     => 'Unsupported',
            'vpc_Card'              => 'MC',
            'vpc_Command'           => 'pay',
            'vpc_Currency'          => $input['vpc_Currency'],
            'vpc_Locale'            => $input['vpc_Locale'],
            'vpc_MerchTxnRef'       => $input['vpc_MerchTxnRef'],
            'vpc_Message'           => 'Approved',
            'vpc_ReceiptNo'         => '713116320780',
            'vpc_RiskOverallResult' => 'ACC',
            'vpc_TransactionNo'     => $this->generateTransactionNo(),
            'vpc_TxnResponseCode'   => '0',
            'vpc_Version'           => $input['vpc_Version'],
        );

        $this->addVpcMerchant($content, $input);

        $this->addMessageAndResponseCode($content, $input);

        $this->addAuthenticationDataIfApplicable($content, $input);

        $this->content($content);

        $content['vpc_SecureHash'] = $this->generateHash($content);

        return $this->prepareResponse($content);
    }

    protected function addAuthenticationDataIfApplicable(&$content, $input)
    {
        if ((isset($input['vpc_VerType']) === true) and
            ($input['vpc_VerType'] === '3DS'))
        {
            $content['vpc_3DSECI']      = $input['vpc_3DSECI'] ?? '01';
            $content['vpc_3DSXID']      = $input['vpc_3DSXID'] ?? '6NQZ/DZVL/LgcawFYz7cMP0vpMo=';
            $content['vpc_3DSenrolled'] = $input['vpc_3DSenrolled'] ?? 'Y';
            $content['vpc_VerToken']    = $input['vpc_VerToken'] ?? 'huMdTSBYZwAbYwAAAHhpApYAAAA=';
            $content['vpc_VerType']     = $input['vpc_VerType'] ?? '3DS';
        }
    }

    public function acs($input)
    {
        $this->validateAuthenticateInput($input);

        // Format - YYYYMMDD
        $date = Carbon::today(Timezone::IST)->format('Ymd');

        $content = array(
            'vpc_3DSECI'            => '01',
            'vpc_3DSXID'            => '6NQZ/DZVL/LgcawFYz7cMP0vpMo=',
            'vpc_3DSenrolled'       => 'Y',
            'vpc_AVSRequestCode'    => 'Z',
            'vpc_AVSResultCode'     => 'Unsupported',
            'vpc_AcqAVSRespCode'    => 'Unsupported',
            'vpc_AcqCSCRespCode'    => 'Unsupported',
            'vpc_AcqResponseCode'   => '14',
            'vpc_Amount'            => $input['vpc_Amount'],
            'vpc_BatchNo'           => $date,
            'vpc_CSCResultCode'     => 'Unsupported',
            'vpc_Card'              => 'MC',
            'vpc_Command'           => 'pay',
            'vpc_Currency'          => $input['vpc_Currency'],
            'vpc_Locale'            => $input['vpc_Locale'],
            'vpc_MerchTxnRef'       => $input['vpc_MerchTxnRef'],
            'vpc_Message'           => 'Approved',
            'vpc_ReceiptNo'         => '511415585968',
            'vpc_RiskOverallResult' => 'ACC',
            'vpc_TransactionNo'     => $this->generateTransactionNo(),
            'vpc_TxnResponseCode'   => '0',
            'vpc_VerSecurityLevel'  => '06',
            'vpc_VerStatus'         => 'M',
            'vpc_VerToken'          => 'huMdTSBYZwAbYwAAAHhpApYAAAA=',
            'vpc_VerType'           => '3DS',
            'vpc_Version'           => '1',
            'vpc_SecureHashType'    => 'SHA256',
        );

        $this->addVpcCard($content, $input);

        $this->addVpcMerchant($content, $input);

        $this->addMessageAndResponseCode($content, $input);

        $this->content($content, 'acs');
        $content['vpc_SecureHash'] = $this->generateHash($content);

        $url = $input['vpc_ReturnURL'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    public function callback($input)
    {
        return $this->authorize($input);
    }

    public function capture($input)
    {
        parent::capture($input);

        $this->validateActionInput($input);

        $payment = $this->getGatewayPaymentEntity($input);

        $content = array(
            'vpc_AcqResponseCode'   => '00',
            'vpc_Amount'            => $input['vpc_Amount'],
            'vpc_AuthorisedAmount'  => $input['vpc_Amount'],
            'vpc_BatchNo'           => '20150503',
            'vpc_CapturedAmount'    => $input['vpc_Amount'],
            'vpc_Card'              => 'MC',
            'vpc_Command'           => 'capture',
            'vpc_Locale'            => 'en_US',
            'vpc_MerchTxnRef'       => $input['vpc_MerchTxnRef'],
            'vpc_Message'           => 'Approved',
            'vpc_Merchant'          => $input['vpc_Merchant'],
            'vpc_ReceiptNo'         => $payment['vpc_ReceiptNo'],
            'vpc_RefundedAmount'    => '0',
            'vpc_ShopTransactionNo' => $payment['vpc_TransactionNo'],
            'vpc_TransactionNo'     => $this->generateTransactionNo(),
            'vpc_TxnResponseCode'   => '0',
            'vpc_Version'           => '1',
        );

        $this->content($content, 'capture');

        return $this->prepareResponse($content);
    }

    public function refund($input)
    {
        parent::refund($input);

        $this->validateActionInput($input);

        $payment = $this->getGatewayPaymentEntity($input);

        $content = array(
            'vpc_AcqResponseCode'   => '00',
            'vpc_Amount'            => $input['vpc_Amount'],
            'vpc_AuthorisedAmount'  => $input['vpc_Amount'],
            'vpc_BatchNo'           => '20150503',
            'vpc_CapturedAmount'    => $input['vpc_Amount'],
            'vpc_Card'              => 'MC',
            'vpc_Command'           => 'refund',
            'vpc_Locale'            => 'en_US',
            'vpc_MerchTxnRef'       => $input['vpc_MerchTxnRef'],
            'vpc_Merchant'          => $input['vpc_Merchant'],
            'vpc_Message'           => 'Approved',
            'vpc_ReceiptNo'         => $payment['vpc_ReceiptNo'],
            'vpc_RefundedAmount'    => '0',
            'vpc_ShopTransactionNo' => $input['vpc_TransNo'],
            'vpc_TransactionNo'     => $this->generateTransactionNo(),
            'vpc_TxnResponseCode'   => '0',
            'vpc_Version'           => '1',
        );

        $this->content($content, 'refund');

        return $this->prepareResponse($content);
    }

    public function reverse($input)
    {
        parent::reverse($input);

        $this->validateActionInput($input);

        $payment = $this->getGatewayPaymentEntity($input);

        $content = array(
            'vpc_AcqResponseCode'   => '00',
            'vpc_Amount'            => $payment['vpc_Amount'],
            'vpc_AuthorisedAmount'  => 0,
            'vpc_BatchNo'           => '20150503',
            'vpc_CapturedAmount'    => 0,
            'vpc_Card'              => 'MC',
            'vpc_Command'           => 'voidAuthorisation',
            'vpc_Currency'          => 'INR',
            'vpc_Locale'            => 'en_US',
            'vpc_MerchTxnRef'       => $input['vpc_MerchTxnRef'],
            'vpc_Merchant'          => $input['vpc_Merchant'],
            'vpc_Message'           => 'Approved',
            'vpc_ReceiptNo'         => $payment['vpc_ReceiptNo'],
            'vpc_RefundedAmount'    => '0',
            'vpc_ShopTransactionNo' => $input['vpc_TransNo'],
            'vpc_TransactionNo'     => $this->generateTransactionNo(),
            'vpc_TxnResponseCode'   => '0',
            'vpc_Version'           => '1',
        );

        $this->content($content, 'reverse');

        return $this->prepareResponse($content);
    }

    public function verify($input)
    {
        parent::verify($input);

        $this->validateActionInput($input);

        $refund = $this->getRefundEntity($input);

        if ($refund != null)
        {
            $payment = $this->getGatewayPaymentEntity(['vpc_MerchTxnRef' => $refund['payment_id']]);
        }
        else
        {
            $payment = $this->getGatewayPaymentEntity($input);
        }

        $content = array(
            'vpc_AcqResponseCode'   => '00',
            'vpc_Amount'            => $payment['vpc_Amount'],
            'vpc_BatchNo'           => $payment['vpc_BatchNo'],
            'vpc_Card'              => 'MC',
            'vpc_Command'           => 'queryDR',
            'vpc_Locale'            => 'en_US',
            'vpc_MerchTxnRef'       => $input['vpc_MerchTxnRef'],
            'vpc_Merchant'          => $input['vpc_Merchant'],
            'vpc_Message'           => 'Approved',
            'vpc_ReceiptNo'         => $payment['vpc_ReceiptNo'],
            'vpc_TransactionNo'     => $payment['vpc_TransactionNo'],
            'vpc_TxnResponseCode'   => '0',
            'vpc_Version'           => '1',
            'vpc_DRExists'          => 'Y',
            'vpc_FoundMultipleDRs'  => 'N',
        );

        if ($refund != null)
        {
            $content['vpc_RefundedAmount'] = $refund['amount'];
            $content['vpc_DRExists']       = 'N';
        }

        $this->content($content, 'verify');

        return $this->prepareResponse($content);
    }

    protected function getGatewayPaymentEntity($input)
    {
        return $this->getRepo()->findByMerchantTxnRef($input['vpc_MerchTxnRef']);
    }

    protected function getRefundEntity($input)
    {
        try
        {
            return $this->getRefundRepo()->findByMerchantTxnRef($input['vpc_MerchTxnRef']);
        }

        catch (\Exception $e)
        {
            return null;
        }
    }

    protected function addMessageAndResponseCode(array & $content, array $input)
    {
        $content['vpc_Message'] = 'Accepted';
        $content['vpc_TxnResponseCode'] = '0';

        if ($input['vpc_CardNum'] === '4111111111111111')
        {
            $content['vpc_Message'] = 'Declined';
            $content['vpc_TxnResponseCode'] = '2';
        }
    }

    protected function prepareResponse($content)
    {
        $this->content($content);
        $body = http_build_query($content);
        $response = \Response::make($body);

        $response->headers->set('Content-Type', 'text/plain;charset=iso-8859-1');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    protected function addVpcMerchant(array & $content, $input)
    {
        $content['vpc_Merchant'] = $input['vpc_Merchant'];
    }

    protected function addVpcCard(array & $content, $input)
    {
        switch ($input['vpc_CardNum'])
        {
            case '55553555655655':
                $content['vpc_3DSstatus'] = 'N';
                break;
            default:
                $content['vpc_3DSstatus'] = 'Y';
        }
    }

    protected function generateTransactionNo()
    {
        return '11000' . random_integer(5);
    }

    protected function getRepo()
    {
        return new AxisMigs\Repository;
    }

    protected function getRefundRepo()
    {
        return new AxisMigs\Mock\Repository;
    }
}
