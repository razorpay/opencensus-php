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
            return $admin;
        }

        return null;
    }
}
