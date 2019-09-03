<?php
namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    const ID                                = 'id';
    const FILE_STORE_ID                     = 'file_store_id';
    const DOCUMENT_TYPE                     = 'document_type';
    const MERCHANT_ID                       = 'merchant_id';
    const ENTITY_TYPE                       = 'entity_type';

    protected static $sign = 'doc';

    protected $entity = "merchant_document";
}
