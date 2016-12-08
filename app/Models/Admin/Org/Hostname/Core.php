<?php

namespace RZP\Models\Admin\Org\Hostname;

use RZP\Models\Base;
use RZP\Models\Admin\Action;
use RZP\Models\Admin\Org;

class Core extends Base\Core
{
    public function create(Org\Entity $org, string $hostname)
    {
        $orgHost = new Entity;

        $orgHost->setAuditAction(Action::CREATE_ORG_HOSTNAME);

        $orgHost->org()->associate($org);

        $orgHost->setHostname($hostname);

        $this->repo->org_hostname->saveOrFail($orgHost);

        return $orgHost;
    }

    public function firstOrCreate(Org\Entity $org, string $hostname)
    {
        $orgHost = $this->repo->org_hostname->firstOrCreate(['hostname' => $hostname]);

        $orgHost->org()->associate($org);

        $this->repo->org_hostname->saveOrFail($orgHost);

        return $orgHost;
    }

    public function fetch(string $hostname)
    {
        return $this->repo->org_hostname->findByHostname($hostname);
    }

    public function delete(string $hostname)
    {
        $orgHostname = $this->repo->org_hostname->findByHostname($hostname);

        $orgHostname->setAuditAction(Action::DELETE_ORG_HOSTNAME);

        $this->repo->deleteOrFail($orgHostname);
    }
}