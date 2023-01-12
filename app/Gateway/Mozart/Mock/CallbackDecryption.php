<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;

class CallbackDecryption extends Base\Mock\Server
{

    public function upi_icici($entities)
    {
        $responseData = $entities;

        if(($responseData !== null) and ($responseData['gateway'] !== null) and
            ($responseData['gateway']['payload'] !== null))
        {
            $responseData = $entities['gateway']['payload'];

            if($responseData['encryptedData'] !== null)
            {
                $responseData = json_decode(base64_decode($responseData['encryptedData']),true);
            }
        }

        $response = [
            'data' => [
                "subMerchantId"         => "400660",
                "ResponseCode"          => "00",
                "PayerMobile"           => "9876543210",
                "TxnCompletionDate"     => "20200715211843",
                "terminalId"            => "5094",
                "PayerName"             => "payer",
                "PayerAmount"           => "5",
                "PayerVA"               => "test@icici",
                "BankRRN"               => "019721040510",
                "merchantId"            => "400660",
                "RespCodeDescription"   => "Debit Success   |ZM|Valid MPIN",
                "UMN"                   => $responseData['UMN'],
                "TxnInitDate"           => "20200715211840",
                "TxnStatus"             => $responseData['TxnStatus'],
                "merchantTranId"        => $responseData['merchantTranId']
            ],
            'error'             => null,
            'success'           => true,
            'mozart_id'         => '',
            'external_trace_id' => '',
        ];

        return $response;
    }
}
