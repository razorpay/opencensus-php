<?php

namespace Gateway\Netbanking\Hdfc\Mock;

use Carbon\Carbon;
use Gateway\Paytm;
use Gateway\Base;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $this->validateAuthorizeInput($input);

        $content = array(
            'MerchRefNo'    => $input['MerchantRefNo'],
            'TxnAmount'     => $input['TxnAmount'],
            'TxnCurrency'   => 'INR',
            'ClientCode'    => $input['ClientCode'],
            'TxnScAmount'   => $input['TxnScAmount'],
            'CheckSum'      => '',
            'BankRefNo'     => random_integer(6),
            'MerchantCode'  => $input['MerchantCode'],
            'Date'          => $input['Date'],
            'StFailFlg'     => 'N',
            'StSucFlg'      => 'N',
            'Message'       => '',
            'fldSessionNbr' => '5',
        );

        $content['CheckSum'] = $this->getCallbackChecksum($content);

        $url = $input['DynamicUrl'];
        $url .= '?' . http_build_query($content);

        return $url;
    }

    protected function getCallbackChecksum($input)
    {
        $paramsOrder = array(
            'ClientCode',
            'MerchantCode',
            'TxnCurrency',
            'TxnAmount',
            'TxnScAmount',
            'MerchRefNo',
            'StSucFlg',
            'StFailFlg',
            'Date',
            'Ref1',
            'Ref2',
            'Ref3',
            'Ref4',
            'Ref5',
            'Ref6',
            'Ref7',
            'Ref8',
            'Ref9',
            'Ref10',
            'Ref11',
            'Date1',
            'Date2',
            'BankRefNo',
            'Message',
        );

        $str = '';

        $data = [];

        foreach ($paramsOrder as $param)
        {
            if (isset($input[$param]))
            {
                $data[$param] = $input[$param];
            }
        }

        return $this->generateHash($data);
    }
}
