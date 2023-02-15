<?php

namespace RZP\Models\GenericDocument;

class Constants
{
    const PURPOSE    = 'purpose';
    const FILE       = 'file';
    const DURATION   = 'duration';
    const EXPIRY     = 'expiry';
    const CONTENT    = 'content';
    const SIGNED_URL = 'signed_url';
    const URL        = 'url';
    const MIME       = 'mime';
    const MIME_TYPE  = 'mime_type';
    const CREATED_AT = 'created_at';
    const TYPE       = 'type';
    const ID         = 'id';
    const IDS        = 'ids';
    const SIZE       = 'size';
    const ENTITY_TYPE = 'entity_type';
    const ENTITY_ID   = 'entity_id';
    const ENTITY      = 'entity';
    const DOCUMENT_ENTITY  = 'document';
    const DOCUMENT_ID      = 'document_id';
    const DOCUMENT_ID_SIGN = 'doc_';
    const FILE_ID_SIGN     = 'file_';
    const DISPLAY_NAME     = 'display_name';


    const DOCUMENT_UPLOAD_MUTEX_LOCK_TIMEOUT = '30';
    const DOCUMENT_UPLOAD_MUTEX_RETRY_COUNT  = '2';

    const KYC_PROOF                         = 'kyc_proof';
    const TRADEMARK_LOGO                    = 'trademark_logo';
    const DISPUTE_EVIDENCE                  = 'dispute_evidence';
    const INTERNATIONAL_ENABLEMENT          = 'international_enablement';
    const MERCHANT_WORKFLOW_CLARIFICATION   = 'merchant_workflow_clarification';
    const B2B_EXPORT_INVOICE                = 'b2b_export_invoice';
    const APM_ONBOARDING                    = 'apm_onboarding';
    const OPGSP_INVOICE                     = 'opgsp_invoice';

    const PURPOSE_TYPE = [
        self::KYC_PROOF,
        self::TRADEMARK_LOGO,
        self::DISPUTE_EVIDENCE,
        self::INTERNATIONAL_ENABLEMENT,
        self::MERCHANT_WORKFLOW_CLARIFICATION,
        self::B2B_EXPORT_INVOICE,
        self::APM_ONBOARDING,
        self::OPGSP_INVOICE
    ];
}
