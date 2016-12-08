<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Admin\Action;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $org = new Entity;

        $org->generateId();

        $org->setAuditAction(Action::CREATE_ORG);

        $org->build($input);

        $this->repo->saveOrFail($org);

        return $org;
    }

    public function fetch(string $orgId)
    {
        $orgId = Entity::verifyIdAndStripSign($orgId);

        return $this->repo->org->findOrFailWithHostname($orgId);

        // return $this->repo->org->findOrFailPublic($orgId);
    }

    public function edit(string $orgId, array $input)
    {
        $orgId = Entity::verifyIdAndStripSign($orgId);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $org->setAuditAction(Action::EDIT_ORG);

        $org->edit($input);

        $this->repo->saveOrFail($org);

        // $this->associateHostnameToGroup($input, $org);

        return $org;
    }

    public function delete($id)
    {
        $id = Entity::verifyIdAndStripSign($id);

        $org = $this->repo->org->findOrFail($id);

        $org->setAuditAction(Action::DELETE_ORG);

        $this->repo->deleteOrFail($org);

        return ['success' => true];
    }

    protected function associateHostnameToGroup($input, $org)
    {
        if (isset($input['hostname']) === true)
        {
            // create hostname
            $newHostnames = explode(',', $input['hostname']);

            foreach ($hostnames as $hostname) {
                $this->repo->org_hostname->firstOrCreate($org, $hostname);
            }
        }
    }
}
