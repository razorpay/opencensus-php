<?php

namespace RZP\Http\Response;

use RZP\Models\User\Role;

interface UserRoleBasedResponse
{
    public function getFieldRoleMapping();

}
