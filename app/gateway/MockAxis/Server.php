<?php

namespace Gateway\MockAxis;

use Models\Card;
use Carbon\Carbon;
use EE\Exception;
use Gateway\Axis;
use Gateway\MockAxis;
use Http\Route;
use Models\Payment;

class Server
{
    protected $validator = null;

    public function __construct()
    {
        $this->request = \Request::getFacadeRoot();

        $this->repo = new Axis\Repository;
    }

    public function authorize($input)
    {
        $validator = $this->getValidator();

        $validator->validateInput('auth', $input);

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
            'vpc_Merchant'          => $input['vpc_Merchant'],
            'vpc_Message'           => 'Approved',
            'vpc_ReceiptNo'         => '511415585968',
            'vpc_RiskOverallResult' => 'ACC',
            'vpc_TransactionNo'     => '11000' . random_integer(5),
            'vpc_TxnResponseCode'   => '0',
            'vpc_VerSecurityLevel'  => '06',
            'vpc_VerStatus'         => 'M',
            'vpc_VerToken'          => 'huMdTSBYZwAbYwAAAHhpApYAAAA=',
            'vpc_VerType'           => '3DS',
            'vpc_Version'           => '1',
        );

        $this->addMessageAndResponseCode($content, $input);

        $content['vpc_SecureHash'] = (new Axis\Gateway)->generateHash($content);

        $url = $input['vpc_ReturnURL'];
        $url .= '&' . http_build_query($content);

        return $url;
    }

    public function capture(array $input)
    {
        ;
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

    protected function getBankPageUrl()
    {
        ;
    }

    protected function generateToken()
    {
        $token = \Models\Base\UniqueIdEntity::generateUniqueId();

        return $token;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function getValidator()
    {
        if ($this->validator === null)
        {
            $this->validator = new Validator;
        }

        return $this->validator;
    }
}
