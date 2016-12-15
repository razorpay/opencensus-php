<?php

namespace RZP\Tests\Functional\Helpers\Heimdall;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;

use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\Role;

trait HeimdallTrait
{
    use EntityActionTrait;

    use RequestResponseFlowTrait
    {
        sendRequest as makeRequestParent;
    }

    protected function deleteAdmin($orgId, $adminId, $token=null)
    {
        $request = array(
            'url' => '/orgs/' . $orgId . '/admins/' . $adminId,
            'method' => 'DELETE');

        $this->ba->adminAuth('test', $token);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function getAuthTokenForOrg($org, $role='admin')
    {
        $admin = $this->fixtures->create('admin',
            [
                'org_id'   => $org->getId(),
                'username' => 'auth admin',
                'password' => 'Heimdall!234',
            ]);

        if ($role === 'admin')
        {
            $superAdminRole = $this->fixtures->create(
                'role:admin_role',
                [
                    'org_id' => $org->getId(),
                ]);

            $admin->roles()->attach($superAdminRole);
        }

        $adminToken = $this->fixtures->create(
            'admin_token',
            [
                'admin_id'   => $admin->getId(),
                'token'      => str_random(40),
                'created_at' => time(),
                'expires_at' => time() + 86400,
            ]);

        return $adminToken->getToken();
    }
}
