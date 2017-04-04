<?php

namespace RZP\Models\User;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    /**
     * Creates a user and saves in database
     *
     * @param  array            $input
     * @return User\Entity
     */
    public function create(array $input)
    {
        $user = (new Core)->create($input);

        return $user->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->edit($user, $input);

        return $user->toArrayPublic();
    }

    public function attach($id, array $input)
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $currentTimestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $this->repo->sync($user,
                        'merchants',
                        [$input['merchant_id'] => [
                                                'role' => $input['role'],
                                                'created_at' => $currentTimestamp,
                                                'updated_at' => $currentTimestamp
                                                ]
                        ]);

        return $user->toArrayPublic();
    }
}
