<?php

namespace RZP\Gateway\Upi\Yesbank\Mock;

use App;
use Carbon\Carbon;
use RZP\Gateway\Upi\Yesbank;
use phpseclib\Crypt\RSA;
use RZP\Gateway\Base;
use RZP\Gateway\Utility;
use RZP\Gateway\Upi\Yesbank\Fields;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Models\Payment\Entity as Payment;

class Server extends Base\Mock\Server
{
    const EXPECTED_COUNT = [
        'payout'    => 35,
    ];

    public function payout(array $input)
    {
        $requestArray = $this->parseInput($input);

        $responseArray = [
          Fields::YBLREFNO              => time() . 'YBL',
          Fields::ORDERNO               => $requestArray[1],
          Fields::AMOUNT                => $requestArray[3],
          Fields::DATE                  => '',
          Fields::STATUSCODE            => Yesbank\Status::SUCCESS,
          Fields::STATUSDESC            => 'SUCCESS',
          Fields::RESPCODE              => '00',
          Fields::APPROVALNUM           => random_integer(5),
          Fields::PAYER_VPA             => 'test@vpa',
          Fields::NPCI_TXN_ID           => 'YESB38A1AF0B2B2B601CE05500000000000',
          Fields::CUST_REF_ID           => '769876897654',
          Fields::PAYER_ACC_NO          => '',
          Fields::PAYER_IFSC_NO         => '',
          Fields::PAYER_ACC_NAME        => '',
          Fields::ERROR_CODE            => 'M00', // not mentioned in the document, will replace with
                                            // correct value post UAT
          Fields::RESPONSE_ERROR_CODE   => '',
          Fields::TRANSFER_TYPE         => 'UPI',
          Fields::PAYEE_VPA             => $requestArray[12],
          Fields::PAYEE_IFSC            => '',
          Fields::PAYEE_ACC_NO          => '',
          Fields::PAYEE_AADHAR          => '',
          Fields::PAYEE_ACC_NAME        => '',
          Fields::ADD1                  => '',
          Fields::ADD2                  => '',
          Fields::ADD3                  => '',
          Fields::ADD4                  => '',
          Fields::ADD5                  => '',
          Fields::ADD6                  => '',
          Fields::ADD7                  => '',
          Fields::ADD8                  => '',
          Fields::ADD9                  => 'NA',
          Fields::ADD10                 => 'NA',
        ];

        $this->content($responseArray, 'payout');

        $msg = implode('|' , $responseArray);

        $response = $this->getGatewayInstance()->encrypt($msg);

        return $this->makeResponse($response);
    }

    protected function parseInput($input, $action = Yesbank\Action::PAYOUT)
    {s($input);
       // $input = json_decode($input, true);

        $encryptedInput = $input['requestMsg'];

        $res = $this->getGatewayInstance()->decrypt($encryptedInput);

        $arr = explode('|', $res);s($arr);

        $actualFieldLength = count($arr);

        $expectedFieldLength = self::EXPECTED_COUNT[$action];s($expectedFieldLength);s($actualFieldLength);

        $message = $actualFieldLength . ' is not equal to expected ' . $expectedFieldLength;

        assertTrue($actualFieldLength === $expectedFieldLength, $message);

        return $arr;
    }
}
