<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function login($input)
    {
        // Get the admin record
        $admin = $this->repo->admin->getByUsername($input['username']);

        // Valid password ?
        if (\Hash::check($input['password'], $admin->password))
        {
            // Create a token for the user
            $token = $this->createAuthToken($admin);

            $admin = $admin->toArrayPublic();
            $admin['token'] = $token->token;

            return $admin;
        }

        return null;
    }

    private function createAuthToken($admin)
    {
        $token = $this->repo->admin_token->createToken($admin);

        return $token;
    }
}
