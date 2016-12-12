<?php

namespace RZP\Models\Admin\Admin\Token;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function deleteTokensForAdmin(Admin\Entity $admin)
    {
        $tokens = $this->repo->admin_token->fetchByAdminIdOrFail(
            $admin->getId());

        foreach ($tokens as $token)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($token)
            {
                $now = Carbon::now()->timestamp;

                $token->setExpiresAt($now);

                $this->repo->saveOrFail($token);

                $this->repo->deleteOrFail($token);
            });
        }
    }
}
