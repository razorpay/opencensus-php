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

        $orgHost->generateId();

        $orgHost->setAuditAction(Action::CREATE_ORG_HOSTNAME);

        $orgHost->org()->associate($org);

        $orgHost->build(['hostname' => $hostname]);

        $this->repo->saveOrFail($orgHost);

        return $orgHost;
    }

    public function delete(Org\Entity $org, string $hostname)
    {
        $orgHost = $this->repo->org_hostname->findByOrgIdAndHostname($org->getId(), $hostname);

        $orgHost->setAuditAction(Action::DELETE_ORG_HOSTNAME);

        $this->repo->org_hostname->deleteOrFail($orgHost);
    }

    public function deleteHostnamesOfOrg(string $orgId)
    {
        $hosts = $this->repo->org_hostname->getHostsByOrgId($orgId);

        foreach ($hosts as $host)
        {
            $this->repo->org_hostname->deleteOrFail($host);
        }
    }
}
