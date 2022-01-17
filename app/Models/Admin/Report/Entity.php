<?php

namespace RZP\Models\Admin\Report;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Feature;
use RZP\Constants\Table;
use RZP\Models\Admin\Base;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\Entity
{
    protected $fillable = [
    ];

    protected $visible = [
    ];

    protected $public = [
    ];

    protected $guarded = [
    ];

    protected $casts = [
    ];

    protected $defaults = [
    ];

    protected $publicSetters = [
    ];

    protected static function boot()
    {
        parent::boot();
    }
}
