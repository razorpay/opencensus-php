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



    const DOCUMENT_UPLOAD_MUTEX_LOCK_TIMEOUT = '30';
    const DOCUMENT_UPLOAD_MUTEX_RETRY_COUNT  = '2';

    const KYC_PROOF       = 'kyc_proof';
    const TRADEMARK_LOGO = 'trademark_logo';

    const PURPOSE_TYPE = [
        self::KYC_PROOF,
        self::TRADEMARK_LOGO,
    ];
}