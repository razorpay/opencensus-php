<?php


namespace RZP\Models\Dispute\Evidence;


class Constants
{
    const ACTION       = 'action';
    const DOCUMENT_IDS = 'document_ids';

    const RISK_OPS_REVIEW_REASON_BEHAVIOR_NOT_SPECIFIED               = 'behavior not specified for payment+gateway combination';
    const RISK_OPS_REVIEW_REASON_ADJUSTMENT_OR_REFUND_CREATION_FAILED = 'adjustment or refund creation failed';

    const IGNORE_FIELDS_FOR_UPDATE_REQUEST = [
        Entity::SUBMITTED_AT,
    ];

    const SEGMENT_EVENT_DISPUTE_EVIDENCE_DOCUMENT_UPLOAD = 'dispute_evidence_document_upload';

    const ADMIN = "admin";
    const MERCHANT = "merchant";
}
