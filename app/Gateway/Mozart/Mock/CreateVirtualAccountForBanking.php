<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Mozart\BTRblBanking\ErrorCode;

class CreateVirtualAccountForBanking extends Base\Mock\Server
{
    public function bt_rbl()
    {
        $errorCode = $this->app['config']->get('rbl_create_virtual_account.error_code');

        $response = [
            'data' =>
                [
                    'Message' => 'NewAccount',
                    'Status'  => 'Success',
                    'TranID'  => 'Jc8tq8LxmzuEZi',
                    '_raw'    => "{\"create_VA\":{\"Header\":{\"TranID\":\"Jc8tq8LxmzuEZi\",\"Status\":\"Success\"},\"Details\":{\"VA_Number\":\"330037167600\",\"Short_Name\":\"ABC01\",\"CIF\":\"1495210\",\"VA_BENEFICIARY\":\"TOURS TRAVEL PVT LTD\",\"Status_Reason\":\"NewAccount\",\"Full_VA_Number\":\"VAABC01HADOOPA\",\"Account_Number\":\"2223330037167600\"}}}"
                ],
            'error'             => null,
            'mozart_id'         => '',
            'next'              => '',
            'success'           => true,
            'external_trace_id' => '',
        ];

        switch ($errorCode)
        {
            case ErrorCode::ER002:

                $response['data']['Status'] = 'FAILED';
                $response['data']['_raw']   = "{\"create_VA\":{\"Header\":{\"TranID\":\"Jc8tq8LxmzuEZi\",\"Status\":\"FAILED\",\"Corp_ID\":\"RZPAYP\",\"Error_Cde\":\"ER002\",\"Error_Desc\":\"ESB Service didn’t respond because of a  technical roadblock.\"}}}";

                unset($response['data']['Message']);
                break;
        }

        return $response;
    }
}
