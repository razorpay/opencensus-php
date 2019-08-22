<?php

namespace RZP\Modules\SecondFactorAuth;

interface BaseAuth
{
    /**
     * All implementing methods must return success
     * or failure
     *
     * @return bool
     */
    public function is2faCredentialValid(array $input): bool;
}
