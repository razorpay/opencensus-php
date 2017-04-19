<?php

namespace RZP\Models\User;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'user';

    protected $appFetchParamRules = [
        Entity::EMAIL       => 'sometimes|email|max:255',
    ];

    /**
     * Get all of the merchants that the user belongs to.
     */
    public function getMerchantCount($suspendedAlso = false)
    {
        $query = $this->belongsToMany(Merchant\Entity::class, 'merchant_users', 'user_id', 'merchant_id')
                      ->withPivot(['role']);

        if ($suspendedAlso === false)
        {
            $query = $query->whereNull('suspended_at');
        }

        return $query->orderBy('name', 'asc');
    }

    public function getByEmail(string $email)
    {
        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->firstOrFailPublic();
    }

    public function getUserForConfirmation(string $token)
    {
        return $this->newQuery()
                    ->where(Entity::CONFIRM_TOKEN, '=', $token)
                    ->firstOrFailPublic();
    }

    /**
     * Confirm a user account
     * @return self
     */
    public function confirm(Entity $user)
    {
        $user->setConfirmTokenNull();

        $this->repo->saveOrFail($user);

        return $user;
    }
}