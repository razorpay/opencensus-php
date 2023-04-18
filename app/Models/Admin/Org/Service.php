<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Admin;
use RZP\Models\BankAccount;
use RZP\Exception\BadRequestException;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Models\Admin\Org;
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

        if (empty($input[Entity::WORKFLOW_PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::WORKFLOW_PERMISSIONS]);
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

    public function createOrgBankAccount($input, $sessionOrgId)
    {
        if (isset($input[Entity::TYPE]) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BANK_ACCOUNT_TYPE_NOT_FOUND);
        }

        if ($input[Entity::TYPE] != BankAccount\Type::ORG)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BANK_ACCOUNT_TYPE_MISMATCH);
        }

        if (isset($input[BankAccount\Entity::ENTITY_ID]) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BANK_ACCOUNT_ENTITY_ID_NOT_PRESENT);
        }

        $orgId = $input[BankAccount\Entity::ENTITY_ID];
        if (($sessionOrgId !== $input[BankAccount\Entity::ENTITY_ID]) and ($sessionOrgId !== Constants::RZP))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $org = (new Org\Repository())->findOrFailPublic($orgId);

        if ($org->isFeatureEnabled(Feature\Constants::ENABLE_ORG_ACCOUNT) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $orgId = $input[BankAccount\Entity::ENTITY_ID];

        $type = $input[Entity::TYPE];

        $oldBankAccount = $this->repo->bank_account->getOrgBankAccount($orgId,$type);

        if ($oldBankAccount !== null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ORG_BANK_ACCOUNT_ALREADY_EXISTS);
        }

        $ba = (new BankAccount\Core)->createOrgBankAccount($input);

        return $ba;
    }

    public function updateOrgBankAccount($id, $input, $sessionOrgId)
    {
        if (($sessionOrgId !== $id) and ($sessionOrgId !== Constants::RZP))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $org = (new Org\Repository())->findOrFailPublic($id);

        if ($org->isFeatureEnabled(Feature\Constants::ENABLE_ORG_ACCOUNT) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $oldBankAccount = $this->repo->bank_account->getOrgBankAccount($id);

        if ($oldBankAccount === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ORG_NO_BANK_ACCOUNT_FOUND);
        }

        return (new BankAccount\Core)->editOrgBankAccount($oldBankAccount, $input)->toArray();
    }

    public function getOrgBankAccount($entity_id, $sessionOrgId)
    {
        if (($sessionOrgId !== $entity_id) and ($sessionOrgId !== Constants::RZP))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $org = (new Org\Repository())->findOrFailPublic($entity_id);

        if ($org->isFeatureEnabled(Feature\Constants::ENABLE_ORG_ACCOUNT) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $ba = $this->repo->bank_account->getOrgBankAccount($entity_id);

        if ($ba === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ORG_NO_BANK_ACCOUNT_FOUND);
        }

        return $ba->toArray();
    }

    /*
        Create SuperAdmin default role for this org
    */
    protected function createDefaultRole(Entity $org, array $input)
    {
        $input = [
            'name' => config('heimdall.default_role_name'),
            'description' => 'This role has all permissions possible',
            'permissions' => $input[Entity::PERMISSIONS],
        ];

        return (new Role\Core)->create($org, $input);
    }

    protected function editDefaultRole(Entity $org, array $input)
    {
        // EDIT of default role is only allowed on permissions
        if (empty($input[Entity::PERMISSIONS]))
        {
            return;
        }

        // Find default role
        $roleName = config('heimdall.default_role_name');

        $role = (new Role\Core)->findRoleByOrgAndName($org, $roleName);

        $input = [
            'permissions' => $input[Entity::PERMISSIONS],
        ];

        return (new Role\Core)->edit($role, $input);
    }

    public function fetch(string $id)
    {
        $org = $this->core()->fetch($id);

        $hostnames = $this->getArrayOfHostnames($org);

        $enabledFeatures = $org->getEnabledFeatures();

        $org = $org->toArrayPublic();

        $org['features'] = $enabledFeatures;

        $org['hostname'] = implode(', ', $hostnames);

        return $org;
    }

    public function fetchByHostname(string $hostname)
    {
        $org = $this->repo->org->findOrFailByHostname($hostname);

        $enabledFeatures = $org->getEnabledFeatures();

        $org = $org->toArrayPublic();

        $org['features'] = $enabledFeatures;

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
        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::PERMISSIONS]);
        }

        if (empty($input[Entity::WORKFLOW_PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::WORKFLOW_PERMISSIONS]);
        }

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

    public function validateEntityOrgId(array $entity):bool // validating the org id
    {
        $orgId = $this->app['basicauth']->getOrgId();

        if(empty($orgId) === false)
        {
            $orgId = Entity::verifyIdAndSilentlyStripSign($orgId);
        }

        if((empty($orgId)) or ($orgId === Entity::RAZORPAY_ORG_ID))
        {
            return true;

        }
        else if($entity['org_id'] === $orgId )
        {
            return true;
        }

        return false;
    }

    public function validateOrgIdWithFeatureFlag(string $orgId, string $featureFlag)
    {
        $features = (new \RZP\Models\Feature\Service)->getFeatures('org',$orgId);

        $assignedFeatures =  $features['assigned_features']->pluck('name')->toArray();

        return (in_array($featureFlag, $assignedFeatures) === true);
    }
}
