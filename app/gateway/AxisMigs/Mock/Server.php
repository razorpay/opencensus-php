<?php

namespace Gateway\AxisMigs\Mock;

use Carbon\Carbon;
use Constants\Mode;
use EE\Exception;
use Gateway\AxisMigs;
use Gateway\Base;
use Models\Card;
use Models\Payment;

class Server extends Base\Mock\Server
{
    protected $validator = null;

    public function __construct()
    {
        $this->request = \Request::getFacadeRoot();

        $this->repo = new AxisMigs\Repository;
    }

    public function authorize($input)
    {
        $this->validateAuthorizeInput($input);

        // Format - YYYYMMDD
        $date = Carbon::today('Asia/Kolkata')->format('Ymd');

        $content = array(
            'vpc_3DSECI'            => '01',
            'vpc_3DSXID'            => '6NQZ/DZVL/LgcawFYz7cMP0vpMo=',
            'vpc_3DSenrolled'       => 'Y',
            'vpc_3DSstatus'         => 'A',
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
        );

        $this->addVpcMerchant($content, $input);

        $this->addMessageAndResponseCode($content, $input);

        $content['vpc_SecureHash'] = $this->generateHash($content);

        $url = $input['vpc_ReturnURL'];
        $url .= '&' . http_build_query($content);

        return $url;
    }

    public function capture(array $input)
    {
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

        return $this->prepareResponse($content);
    }

    public function refund($input)
    {
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
            'vpc_Merchant'          => $input['vpc_Merchant'],
            'vpc_Message'           => 'Approved',
            'vpc_ReceiptNo'         => $payment['vpc_ReceiptNo'],
            'vpc_RefundedAmount'    => '0',
            'vpc_ShopTransactionNo' => $input['vpc_TransNo'],
            'vpc_TransactionNo'     => $this->generateTransactionNo(),
            'vpc_TxnResponseCode'   => '0',
            'vpc_Version'           => '1',
        );

        return $this->prepareResponse($content);
    }

    protected function checkReferer()
    {
        $request = $this->request;

        $referer = $request->headers->get('referer');

        $schema = $request->getScheme().'://';
        $host = $request->getHost();
        $host = $schema.$host;

        $pos = strpos($referer, $host);

        if ($pos !== 0)
        {
            throw new Exception\LogicException(
                'Unexpected referer value. Referer: ' . $referer);
        }
    }

    protected function getGatewayPaymentEntity($input)
    {
        return (new AxisMigs\Repository)->findByMerchantTxnRef($input['vpc_MerchTxnRef']);
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

    protected function validateAuthorizeInput($input)
    {
        $validator = $this->getValidator();

        $validator->validateInput('auth', $input);
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    protected function getValidator()
    {
        if ($this->validator === null)
        {
            $this->validator = new Validator;
        }

        return $this->validator;
    }

    protected function generateTransactionNo()
    {
        return '11000' . random_integer(5);
    }
}
