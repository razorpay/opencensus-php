<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive\Mock;

use Carbon\Carbon;
use phpseclib\Crypt\RSA;
use RZP\Gateway\P2p\Upi\AxisOlive\Fields;
use RZP\Gateway\P2p\Upi\AxisOlive\Gateway;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\TurboAction;

/**
 * Class Callback
 * This a callback mock class which will hold all callback information
 * @package RZP\Gateway\P2p\Upi\AxisOlive\Mock
 */
class Callback
{
    protected $action;
    protected $input;
    protected $errors = [];
    protected $callbacks = [];

    public function initiateCallback()
    {
        $content = json_encode(array_pop($this->callbacks));

        return [
            'content' => $content,
        ];
    }

    public function setCallback(string $type, array $input)
    {
        switch ($type)
        {
            case TurboAction::REQUEST_COMPLAINT_CALLBACK:
                $callback = [
                    Fields::ORG_TXN_ID          => $input[Fields::ORG_TXN_ID],
                    Fields::ORG_RRN             => $input[Fields::ORG_RRN],
                    Fields::ORG_TXN_DATE        => $input[Fields::ORG_TXN_DATE],
                    Fields::REF_ADJ_FLAG        => $input[Fields::REF_ADJ_FLAG],
                    Fields::REF_ADJ_CODE        => $input[Fields::REF_ADJ_CODE],
                    Fields::REF_ADJ_AMOUNT      => $input[Fields::REF_ADJ_AMOUNT],
                    Fields::REF_ADJ_REMARKS     => $input[Fields::REF_ADJ_REMARKS],
                    Fields::CRN                 => $input[Fields::CRN],
                    Fields::REF_ADJ_TS          => $input[Fields::REF_ADJ_TS],
                    Fields::REF_ADJ_REF_ID      => $input[Fields::REF_ADJ_REF_ID],
                ];
                break;

            case TurboAction::NOTIFICATION_COMPLAINT_CALLBACK:
                $callback = [
                    Fields::INIT_MODE           => $input[Fields::INIT_MODE],
                    Fields::TYPE                => $input[Fields::TYPE],
                    Fields::SUBTYPE             => $input[Fields::SUBTYPE],
                    Fields::ORG_TXN_ID          => $input[Fields::ORG_TXN_ID],
                    Fields::ORG_RRN             => $input[Fields::ORG_RRN],
                    Fields::ORG_TXN_DATE        => $input[Fields::ORG_TXN_DATE],
                    Fields::REQ_ADJ_FLAG        => $input[Fields::REQ_ADJ_FLAG],
                    Fields::REQ_ADJ_CODE        => $input[Fields::REQ_ADJ_CODE],
                    Fields::REQ_ADJ_AMOUNT      => $input[Fields::REQ_ADJ_AMOUNT],
                    Fields::CRN                 => $input[Fields::CRN],
                    Fields::REF_ADJ_TS          => $input[Fields::REF_ADJ_TS],
                    Fields::REF_ADJ_REF_ID      => $input[Fields::REF_ADJ_REF_ID],
                ];
                break;

            default:
                $callback = $input;
        }

        $this->callbacks[] = $callback;
    }
}
