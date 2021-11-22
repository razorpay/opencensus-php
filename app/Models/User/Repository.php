<?php

namespace RZP\Models\User;

use RZP\Models\Base;
use RZP\Constants\Mode;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive { saveOrFail as saveOrFailTestAndLive; }

    protected $entity = 'user';

    protected $appFetchParamRules = [
        Entity::EMAIL       => 'sometimes|email|max:255',
    ];

    public function findByEmail(string $email)
    {
        $liveMode = $this->auth->getLiveConnection();

        return $this->newQueryWithConnection($liveMode)
                    ->where(Entity::EMAIL, '=', strtolower($email))
                    ->firstOrFailPublic();
    }

    public function findByMobile(string $mobile)
    {
        $liveMode = $this->auth->getLiveConnection();

        return $this->newQueryWithConnection($liveMode)
                    ->where(Entity::CONTACT_MOBILE, '=', $mobile)
                    ->get();
    }

    public function getUserFromEmailOrFail(string $email)
    {
        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->firstOrFailPublic();
    }

    public function findByToken(string $token)
    {
        return $this->newQuery()
                    ->where(Entity::CONFIRM_TOKEN, '=', $token)
                    ->firstOrFailPublic();
    }


    public function findUniqueNumberExcludingCurrentUser(string $id, string $number)
    {
        return $this->newQuery()
                    ->where(Entity::CONTACT_MOBILE, '=', $number)
                    ->where(Entity::ID, '!=', $id)
                    ->first();
    }

    public function filterEmailNotVerifiedUserIds(int $from, int $to): array
    {
        return $this->newQuery()
            ->select(Entity::ID)
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->WhereNotNull(Entity::CONTACT_MOBILE)
            ->WhereNotNull(Entity::CONFIRM_TOKEN)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
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

        return $this->saveOrFailTestAndLive($entity, $options);
    }


    /**
     * select `email` from `users`
     *         where `id` in (?, ?)
     *         order by `id` asc
     *
     * @param     $userIds
     *
     * @return array
     */
    public function fetchUserEmails($userIds): array
    {
        $userEmailIds = $this->newQuery()
                             ->select(Entity::EMAIL)
                             ->whereIn(Entity::ID, $userIds)
                             ->orderBy(Entity::ID)
                             ->get()
                             ->getStringAttributesByKey(Entity::EMAIL);

        return array_keys($userEmailIds);
    }

}
