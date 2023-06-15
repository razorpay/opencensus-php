<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

use RZP\Models\Dispute\Phase;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Dispute\Reason\Entity as DisputeReasonEntity;

class ReasonCode
{
    // reason_code => ticket_subcategory => [network_code, phase]
    const REASON_CODE_MAP = [
        Subcategory::DISPUTE_A_PAYMENT => [
            'goods_service_not_provided' => [
                DisputeReasonEntity::GATEWAY_CODE => 'RZP01',
                DisputeEntity::PHASE => Phase::CHARGEBACK
            ],
            'unauthorized_transaction' => [
                DisputeReasonEntity::GATEWAY_CODE => 'RZP02',
                DisputeEntity::PHASE => Phase::CHARGEBACK
            ],
            'potential_fraud' => [
                DisputeReasonEntity::GATEWAY_CODE => 'RZP03',
                DisputeEntity::PHASE => Phase::CHARGEBACK
            ],
            'refund_not_processed' => [
                DisputeReasonEntity::GATEWAY_CODE => 'RZP04',
                DisputeEntity::PHASE => Phase::CHARGEBACK
            ],
            'account_debited_but_confirmation_not_received' => [
                DisputeReasonEntity::GATEWAY_CODE => 'RZP05',
                DisputeEntity::PHASE => Phase::CHARGEBACK
            ],
            'not_available' => [
                DisputeReasonEntity::GATEWAY_CODE => 'RZP00',
                DisputeEntity::PHASE => Phase::CHARGEBACK
            ],
            // typo exist in FE and DB: adding responding with typo, as it is not shown to merchant directly
            'merchant_business_not_reponding' => [
                DisputeReasonEntity::GATEWAY_CODE => 'RZP06',
                DisputeEntity::PHASE => Phase::CHARGEBACK
            ]
        ],
        Subcategory::REPORT_FRAUD => [
            'potential_fraud' => [
                DisputeReasonEntity::GATEWAY_CODE => 'RZP03',
                DisputeEntity::PHASE => Phase::CHARGEBACK
            ],
        ],
    ];

    public static function isValidReasonCode(string $subcategory, string $reasonCode): bool
    {
        return isset(self::REASON_CODE_MAP[$subcategory][$reasonCode]) === true;
    }
}
