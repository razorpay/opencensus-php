<?php

namespace RZP\Constants;

use RZP\Base\Fetch;
use RZP\Models\Dispute;

class Disputes
{
    public static function getDisputeFilters()
    {
        return [
            Dispute\Entity::MERCHANT_ID => Fetch::FIELD_MERCHANT_ID,
            Dispute\Entity::PAYMENT_ID => Fetch::FIELD_PAYMENT_ID,
            Dispute\Entity::STATUS => [
                Fetch::LABEL => 'Status',
                Fetch::TYPE => Fetch::TYPE_ARRAY,
                Fetch::VALUES => [
                    Dispute\Status::OPEN,
                    Dispute\Status::UNDER_REVIEW,
                    Dispute\Status::WON,
                    Dispute\Status::LOST,
                    Dispute\Status::CLOSED,
                ],
            ],
            Dispute\Entity::INTERNAL_STATUS => [
                Fetch::LABEL => 'Internal Status',
                Fetch::TYPE => Fetch::TYPE_ARRAY,
                Fetch::VALUES => Dispute\InternalStatus::getInternalStatuses(),
            ],
            Dispute\Entity::PHASE => [
                Fetch::LABEL => 'Phase',
                Fetch::TYPE => Fetch::TYPE_ARRAY,
                Fetch::VALUES => [
                    Dispute\Phase::CHARGEBACK,
                    Dispute\Phase::PRE_ARBITRATION,
                    Dispute\Phase::ARBITRATION,
                    Dispute\Phase::RETRIEVAL,
                    Dispute\Phase::FRAUD,
                ],
            ],
            Dispute\Entity::AMOUNT => [
                Fetch::LABEL => 'Amount',
                Fetch::TYPE => Fetch::TYPE_STRING,
            ],
            Dispute\Entity::INTERNAL_RESPOND_BY_TO => [
                Fetch::LABEL => 'Internal Respond By To',
                Fetch::TYPE => Fetch::TYPE_STRING,
            ],
            Dispute\Entity::INTERNAL_RESPOND_BY_FROM => [
                Fetch::LABEL => 'Internal Respond By From',
                Fetch::TYPE => Fetch::TYPE_STRING,
            ],
            Dispute\Entity::ORDER_BY_INTERNAL_RESPOND => [
                Fetch::LABEL => 'Prioritize on Internal Respond By Disputes',
                Fetch::TYPE => Fetch::TYPE_BOOLEAN,
            ],
            Dispute\Entity::GATEWAY_DISPUTE_SOURCE => [
                Fetch::LABEL => 'Gateway Dispute Source',
                Fetch::TYPE => Fetch::TYPE_ARRAY,
                Fetch::VALUES => [
                    Dispute\Constants::GATEWAY_DISPUTE_SOURCE_CUSTOMER,
                    Dispute\Constants::GATEWAY_DISPUTE_SOURCE_NETWORK,
                ],
            ],
        ];
    }

    public static function getDisputeReasonFilters(){
        return [
            'network' => [
                Fetch::LABEL  => 'Network',
                Fetch::TYPE   => Fetch::TYPE_ARRAY,
                Fetch::VALUES => Dispute\Reason\Network::list(),
            ],
            'code' => [
                Fetch::LABEL  => 'Code',
                Fetch::TYPE   => Fetch::TYPE_STRING,
            ],
            'description' => [
                Fetch::LABEL  => 'Description',
                Fetch::TYPE   => Fetch::TYPE_STRING,
            ],
            'gateway_code' => [
                Fetch::LABEL  => 'Gateway Code',
                Fetch::TYPE   => Fetch::TYPE_STRING,
            ],
            'gateway_description' => [
                Fetch::LABEL  => 'Gateway Description',
                Fetch::TYPE   => Fetch::TYPE_STRING,
            ]
        ];
    }

    public static function getDisputeEvidenceFilters(){
        return [
            Dispute\Evidence\Document\Entity::DISPUTE_ID =>  [
                Fetch::LABEL        => 'Dispute ID',
                Fetch::TYPE         => Fetch::TYPE_STRING,
            ],
        ];
    }

    public static function getDisputeEvidenceDocumentFilters(){
        return [
            Dispute\Evidence\Document\Entity::DISPUTE_ID =>  [
                Fetch::LABEL        => 'Dispute ID',
                Fetch::TYPE         => Fetch::TYPE_STRING,
            ],
        ];
    }
}
