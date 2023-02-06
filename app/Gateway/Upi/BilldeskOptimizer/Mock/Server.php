<?php

namespace RZP\Gateway\Upi\BilldeskOptimizer\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\BilldeskOptimizer\Fields;

class Server extends Base\Mock\Server
{
    public function getCallback(array $upiEntity, array $payment)
    {
        $this->action = Action::CALLBACK;
        $content = $this->getCallbackData($upiEntity, $payment);
        $this->content($content, "callback");

        return $this->getCallbackRequest($content);
    }

    public function getCallbackRequest($data)
    {
        $url = "/callback/billdesk_optimizer";
        $method = "post";

        $server = [
            "CONTENT_TYPE" => "application/jose",
        ];

        $raw = json_encode($data);

        return [
            "url" => $url,
            "method" => $method,
            "raw" => $raw,
            "server" => $server,
        ];
    }

    protected function getCallbackData(array $upiEntity, array $payment)
    {
        $status = "SUCCESS";
        $amount = $payment["amount"];
        $responseMassage = "Transaction Successful";

        switch ($payment["description"]) {
            case "callback_failed_v2":
                $status = "FAILED";
                $responseMassage = "Transaction Failed";
                break;

            case "callback_amount_mismatch_v2":
                $amount = $payment["amount"] + 100;
                break;
        }

        $content = [
            Fields::AUTH_STATUS => "0300",
            Fields::TRANSACTION_ERROR_DESC => $responseMassage,
            Fields::SURCHARGE => "0.00",
            Fields::PAYMENT_METHOD_TYPE => "upi",
            Fields::BANK_REF_NO => "272836618724",
            Fields::BANKID => "HD5",
            Fields::TRANSACTION_ERROR_CODE => "TRS0000",
            Fields::TRANSACTION_ERROR_TYPE => $status,
            Fields::TRANSACTIONID => "XHD50934246984",
            Fields::TXN_PROCESS_TYPE => "collect",
            Fields::CURRENCY => "356",
            Fields::TRANSACTION_DATE => "2022-12-28T15:32:41+05:30",
            Fields::ORDERID => $payment["id"],
            Fields::ITEMCODE => "DIRECT",
            Fields::CHARGE_AMOUNT => $amount,
            Fields::MERCID => "BDUAT2",
            Fields::AMOUNT => $amount,
            Fields::DISCOUNT => "0.00",
            Fields::ADDITIONAL_INFO =>
                '{\"additional_info7\": \"NA\",\"additional_info6\": \"NA\",\"additional_info8\": \"NA\",\"additional_info3\": \"NA\",\"additional_info2\": \"NA\",\"additional_info5\": \"NA\",\"additional_info4\": \"NA\",\"additional_info9\": \"NA\",\"additional_info10\": \"NA\",\"additional_info1\": \"NA\"}',
            Fields::OBJECTID => "transaction",
        ];

        return $content;
    }
}
