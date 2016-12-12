<?php

namespace RZP\Models\Admin\Org\Hostname;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use RevisionableTrait;

    const ORG_ID        = 'org_id';
    const HOSTNAME      = 'hostname';
    const DELETED_AT    = 'deleted_at';

    protected $entity = 'org_hostname';

    public $incrementing = true;

    protected $fillable = [
        self::HOSTNAME
    ];

    protected $visible = [
        self::ORG_ID,
        self::HOSTNAME
    ];

    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
    }

    public function getHostname()
    {
        return $this->getAttribute(self::HOSTNAME);
    }

    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function setHostname($hostname)
    {
        $this->setAttribute(self::HOSTNAME, $hostname);
    }

    public function setOrgId($orgId)
    {
        $this->setAttribute(self::ORG_ID, $orgId);
    }
}
