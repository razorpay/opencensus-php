<?php

namespace RZP\Gateway\Mozart\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Mozart\BTRblBanking\ErrorCode;

class DeactivateVirtualAccountCollectX extends Base\Mock\Server
{
    public function bt_rbl()
    {
        $errorCode = $this->app['config']->get('rbl_close_virtual_account.error_code');

        $response = [
            'data' =>
                [
                    'Message' => 'NewAccount',
                    'TranID'  => 'Jc8tq8LxmzuEZi',
                    '_raw'    => "{\"deactivate_VA\":{\"Header\":{\"TranID\":\"Jc8tq8LxmzuEZi\",\"Corp_ID\": \"RAZORPAYVA\"},\"Body\":{\"Account_No\": \"409000007396\",\"Full_VA_Number\":\"2224cbdde3c736\",
                    \"Status\":\"Success\"}}}"
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
                $response['data']['_raw']   = "{\"deactivate_VA\":{\"Header\":{\"TranID\":\"Jc8tq8LxmzuEZi\",\"Status\":\"FAILED\",\"Corp_ID\":\"RZPAYP\",\"Error_Cde\":\"ER002\",\"Error_Desc\":\"ESB Service didn’t respond because of a  technical roadblock.\"}}}";

                unset($response['data']['Message']);
                break;
        }

        return $response;
    }
}
