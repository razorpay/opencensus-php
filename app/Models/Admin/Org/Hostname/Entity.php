<?php

namespace RZP\Models\Admin\Org\Hostname;

use RZP\Models\Admin\Base;
use RZP\Models\Base\Traits\HardDeletes;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\Entity
{
    use HardDeletes;
    use RevisionableTrait;

    const ORG_ID        = 'org_id';
    const HOSTNAME      = 'hostname';

    protected $entity = 'org_hostname';

    protected $generateIdOnCreate = false;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::HOSTNAME,
    ];

    protected $visible = [
        self::ORG_ID,
        self::HOSTNAME,
    ];

    protected $public = [
        self::ORG_ID,
        self::HOSTNAME,
    ];

    protected $publicSetters = [
        self::ID,
        self::ORG_ID,
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

    public function setHostnameAttribute($hostname)
    {
        $this->attributes[self::HOSTNAME] = trim($hostname);
    }
}
