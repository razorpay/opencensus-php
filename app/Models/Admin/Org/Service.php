<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Admin;
use Config;

class Service extends Base\Service
{
    public function create(array $input)
    {
        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::PERMISSIONS]);
        }

        $org = $this->repo->transactionOnLiveAndTest(function() use ($input)
        {
            $org = $this->core()->create($input);

            if (isset($input['hostname']) === true)
            {
                $hostnames = explode(',', $input['hostname']);

                foreach ($hostnames as $hostname)
                {
                    (new Hostname\Core)->create($org, $hostname);
                }
            }

            // create default role
            $role = $this->createDefaultRole($org, $input);

            // create admin
            $input['admin']['roles'] = (array) $role->getId();

            $input['admin']['email'] = $input['email'];

            (new Admin\Core)->create($org, $input['admin']);

            return $org;
        });

        return $org->toArrayPublic();
    }

    /*
        Create SuperAdmin default role for this org
    */
    protected function createDefaultRole(Entity $org, array $input)
    {
        if (empty($input['permissions']))
        {
            return;
        }

        $input = [
            'name' => config('heimdall.default_role_name'),
            'description' => 'This role has all permissions possible',
            'permissions' => $input['permissions'],
        ];

        return (new Role\Core)->create($org, $input);
    }

    protected function editDefaultRole(Entity $org, array $input)
    {
        // EDIT of default role is only allowed on permissions
        if (empty($input['permissions']))
        {
            return;
        }

        // Find default role
        $roleName = config('heimdall.default_role_name');

        $role = (new Role\Core)->findRoleByOrgAndName($org, $roleName);

        $input = [
            'permissions' => $input['permissions'],
        ];

        return (new Role\Core)->edit($role, $input);
    }

    public function fetch(string $id)
    {
        $org = $this->core()->fetch($id);

        $hostnames = $this->getArrayOfHostnames($org);

        $org = $org->toArrayPublic();

        $org['hostname'] = implode($hostnames, ', ');

        return $org;
    }

    public function fetchByHostname(string $hostname)
    {
        $org = $this->repo->org->findOrFailByHostname($hostname);

        $org = $org->toArrayPublic();

        // find a way to fix this
        $org['hostname'] = $hostname;

        return $org;
    }

    public function delete(string $id)
    {
        return $this->core()->delete($id);
    }

    public function edit(string $id, array $input)
    {
        $org = $this->repo->transactionOnLiveAndTest(function() use ($id, $input)
        {
            $org = $this->core()->edit($id, $input);

            if (isset($input['hostname']) === true)
            {
                $newHostnames = explode(',', $input['hostname']);
                $newHostnames = array_map('trim', $newHostnames);

                $existingHostnames = $this->getArrayOfHostnames($org);

                $hostnamesToCreate = array_diff($newHostnames, $existingHostnames);

                $hostnamesToDelete = array_diff($existingHostnames, $newHostnames);

                foreach ($hostnamesToDelete as $hostname)
                {
                    (new Hostname\Core)->delete($org, $hostname);
                }

                foreach ($hostnamesToCreate as $hostname)
                {
                    (new Hostname\Core)->create($org, $hostname);
                }
            }

            // create default role
            $role = $this->editDefaultRole($org, $input);

            return $org;
        });

        return $org->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }

    protected function getArrayOfHostnames(Entity $org)
    {
        $hostnames = $org->hostnames->pluck(Hostname\Entity::HOSTNAME);

        return $hostnames->toArray();
    }
}
