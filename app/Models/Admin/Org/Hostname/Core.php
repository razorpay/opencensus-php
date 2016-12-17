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

        $orgHost->build(['hostname' => $hostname, 'org_id' => $org->getId()]);

        $this->repo->org_hostname->saveOrFail($orgHost);

        return $orgHost;
    }

    public function fetch(string $hostname)
    {
        return $this->repo->org_hostname->findByHostname($hostname);
    }

    public function delete(string $hostname)
    {
        $orgHost = $this->repo->org_hostname->findByHostname($hostname);

        $orgHost->setAuditAction(Action::DELETE_ORG_HOSTNAME);

        $this->repo->org_hostname->deleteOrFail($orgHost);
    }

    public function deleteHostnamesOfOrg(string $orgId)
    {
        $allHosts = $this->repo->org_hostname->getHostsByOrgId($orgId);

        foreach ($allHosts as $host) {
            $this->repo->org_hostname->deleteOrFail($host);
        }
    }
}