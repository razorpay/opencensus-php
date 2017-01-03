<?php

namespace RZP\Tests\Functional\Helpers\Heimdall;

use Carbon\Carbon;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;

trait HeimdallTrait
{
    use EntityActionTrait;
    use RequestResponseFlowTrait
    {
        sendRequest as makeRequestParent;
    }

    protected function deleteAdmin($orgId, $adminId, $token = null)
    {
        $request = [
            'url'    => '/orgs/' . $orgId . '/admins/' . $adminId,
            'method' => 'DELETE'
        ];

        $this->ba->adminAuth('test', $token);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function getAdmin($orgId, $adminId, $token = null)
    {
        $request = [
            'url'    => '/orgs/' . $orgId . '/admins/' . $adminId,
            'method' => 'GET'
        ];

        $this->ba->adminAuth('test', $token);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function getAuthTokenForOrg($org, $role = 'admin')
    {
        $now = Carbon::now();

        $admin = $this->fixtures->create('admin', [
            'org_id'   => $org->getId(),
            'username' => 'auth admin',
            'password' => 'Heimdall!234',
        ]);

        if ($role === 'admin')
        {
            $superAdminRole = $this->fixtures->create('role:admin_role', [
                'org_id' => $org->getId(),
            ]);

            $admin->roles()->attach($superAdminRole);
        }

        $adminToken = $this->fixtures->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => str_random(40),
            'created_at' => $now->timestamp,
            'expires_at' => $now->addDays(2)->timestamp,
        ]);

        return $adminToken->getToken();
    }
}
