<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Admin;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Config;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $org = $this->repo->transactionOnLiveAndTest(function() use ($input)
        {
            $org = $this->core()->create($input);

            // create hostname
            $hostnames = explode(',', $input['hostname']);

            foreach ($hostnames as $hostname) {
                (new Hostname\Core)->create($org, $hostname);
            }

            // create default role
            $role = $this->createDefaultRole($org);

            // create admin
            $input['admin']['roles'] = (array) $role->getPublicId();

            $input['admin']['email'] = $input['email'];

            (new Admin\Core)->create($org, $input['admin']);

            return $org;
        });

        return $org->toArrayPublic();
    }

    protected function createDefaultRole(Entity $org)
    {
        $permissions = Config::get('heimdall.permissions') ?: [];

        $permissions = (new Permission\Core)->getMultiplePermissionIdsByNames($permissions);

        $input = [
            'name' => 'superadmin',
            'description' => 'This role has all permissions possible',
            'permissions' => $permissions,
        ];

        return (new Role\Core)->create($org, $input);
    }

    public function fetch(string $id)
    {
        $org = $this->core()->fetch($id);

        $hostnames = array_map(create_function('$o', 'return $o->getHostname();'), $org->hostnames->all());

        $this->trace->info(TraceCode::ERROR_EXCEPTION, ['hostnames' => $hostnames]);

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
                // create hostname
                $hostnames = explode(',', $input['hostname']);

                foreach ($hostnames as $hostname) {
                    (new Hostname\Core)->firstOrCreate($org, $hostname);
                }
            }

            return $org;
        });

        return $org->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }
}
