<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive\Transformers;

use Carbon\Carbon;
use RZP\Exception\RuntimeException;
use RZP\Models\P2p\Complaint\Action;
use RZP\Models\P2p\Complaint\Entity;
use RZP\Gateway\P2p\Upi\AxisOlive\Fields;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\TurboAction;

/**
 * Class ComplaintTransformer
 * Complaint transformer which will transforms the bank callback to razorpay format
 * @package RZP\Gateway\P2p\Upi\AxisOlive\Transformers
 */
class ComplaintTransformer extends Transformer
{
    /**
     * Transform basic fields
     *
     * @return array
     */
    public function transform(): array
    {
        $output = [
            Entity::CRN                                                 => $this->input[Fields::CRN],
            Entity::UPDATED_AT                                          => Carbon::now()->getTimestamp(),
            Entity::GATEWAY_DATA =>[
                Fields::GATEWAY_TRANSACTION_ID                          => $this->input[Fields::ORG_TXN_ID],
                Fields::RRN                                             => $this->input[Fields::ORG_RRN],
                Fields::TRANSACTION_TIME_STAMP                          => $this->input[Fields::ORG_TXN_DATE],
                Fields::TRANSACTION_ORIGINATION_TIME                    => $this->input[Fields::REF_ADJ_TS],
                Fields::GATEWAY_COMPLAINT_REFERENCE_ID                  => $this->input[Fields::REF_ADJ_REF_ID],
            ],
        ];

        if(isset($this->input[Fields::INIT_MODE]) === true)
        {
            $output[Entity::GATEWAY_DATA][Fields::INITIATION_MODE]      = $this->input[Fields::INIT_MODE];
        }

        if(isset($this->input[Fields::TYPE]) === true)
        {
            $output[Entity::GATEWAY_DATA][Fields::TYPE]                 = $this->input[Fields::TYPE];
        }

        if(isset($this->input[Fields::SUBTYPE]) === true)
        {
            $output[Entity::GATEWAY_DATA][Fields::SUB_TYPE]             = $this->input[Fields::SUBTYPE];
        }

        // if reference is set the meta accordingly
        if(isset($this->input[Fields::REF_ADJ_CODE])=== true)
        {
            $output[Entity::META]  = [
            Fields::REFERENCE_ADJ_CODE                                  => $this->input[Fields::REF_ADJ_CODE],
            Fields::REFERENCE_ADJ_FLAG                                  => $this->input[Fields::REF_ADJ_FLAG],
            Fields::REFERENCE_ADJ_AMOUNT                                => $this->input[Fields::REF_ADJ_AMOUNT],
            Fields::REFERENCE_ADJ_REMARKS                               => $this->input[Fields::REF_ADJ_REMARKS],
            ];
        }
        else if(isset($this->input[Fields::REQ_ADJ_CODE])=== true)
        {
            $output[Entity::META]  = [
                Fields::REQUESTED_ADJ_CODE                              => $this->input[Fields::REQ_ADJ_CODE],
                Fields::REQUESTED_ADJ_FLAG                              => $this->input[Fields::REQ_ADJ_FLAG],
                Fields::REQUESTED_ADJ_AMOUNT                            => $this->input[Fields::REQ_ADJ_AMOUNT],
            ];
        }

        return  $output;
    }
}
