<?php

namespace RZP\Models\User;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'user';

    protected $appFetchParamRules = [
        Entity::EMAIL       => 'sometimes|email|max:255',
    ];

    public function findByEmail(string $email)
    {
        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', strtolower($email))
                    ->firstOrFailPublic();
    }

    public function findByToken(string $token)
    {
        return $this->newQuery()
                    ->where(Entity::CONFIRM_TOKEN, '=', $token)
                    ->firstOrFailPublic();
    }

    public function getUserFromEmail(string $email)
    {
        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function saveOrFail($entity, array $options = [])
    {
        // If contact has been modified mark verified as false
        if ($entity->isDirty(Entity::CONTACT_MOBILE) === true)
        {
            $entity->setContactMobileVerified(false);
        }

        return parent::saveOrFail($entity, $options);
    }
}
