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
    public function create(array $input): array
    {
        $user = (new Core)->create($input);

        return $user->toArrayPublic();
    }

    public function edit(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->edit($user, $input);

        return $user->toArrayPublic();
    }

    public function mappingAction(string $id, array $input)
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user->getValidator()->validateInput('action', $input);

        $function = $input['action'];

        return $this->$function($user, $input);
    }

    public function attach(Entity $user, array $input): array
    {
        $currentTimestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $mappingParams = [
             'role' => $input['role'],
             'created_at' => $currentTimestamp,
             'updated_at' => $currentTimestamp
        ];

        $merchantId = $input['merchant_id'];

        $this->repo->attach($user, 'merchants', [$merchantId => $mappingParams]);

        return $user->toArrayPublic();
    }

    public function detach(Entity $user, array $input): array
    {
        $this->repo->detach($user, 'merchants', $input['merchant_id']);

        return $user->toArrayPublic();
    }

    public function update(Entity $user, array $input): array
    {
        $currentTimestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $mappingParams = [
            'role' => $input['role'],
            'updated_at' => $currentTimestamp
        ];

        $merchantId = $input['merchant_id'];

        $this->repo->sync($user, 'merchants', [$merchantId => $mappingParams], false);

        return $user->toArrayPublic();
    }

    public function confirm(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->confirm($user);

        return $user->toArrayPublic();
    }

    public function changePassword(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->changePassword($user, $input['password']);

        return $user->toArrayPublic();
    }
}
