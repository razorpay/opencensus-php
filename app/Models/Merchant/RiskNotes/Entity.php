<?php

namespace RZP\Models\Merchant\RiskNotes;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                   = 'id';
    const MERCHANT_ID          = 'merchant_id';
    const ADMIN_ID             = 'admin_id';
    const ADMIN                = 'admin';
    const NOTE                 = 'note';
    const DELETED_BY           = 'deleted_by';
    const UPDATED_AT           = null;

    protected $entity                   = Constants\Entity::MERCHANT_RISK_NOTE;
    protected $generateIdOnCreate       = true;

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::CREATED_AT,
        self::NOTE,
        self::DELETED_AT,
        self::DELETED_BY,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::ADMIN_ID,
        self::CREATED_AT,
        self::NOTE,
        self::DELETED_AT,
        self::DELETED_BY,
    ];

    protected $expanded = [self::ADMIN];

    public $timestamps = [self::CREATED_AT];

    protected $guarded = [];

    //--------------Setters---------------
    public function setDeletedBy($adminId)
    {
        $this->setAttribute(self::DELETED_BY, $adminId);
    }

    public function setDeletedAt()
    {
        $this->setAttribute(self::DELETED_AT, Carbon::now()->getTimestamp());
    }

    // ------------ Relations ------------
    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }
}
