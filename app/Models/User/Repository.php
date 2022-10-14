<?php

namespace RZP\Models\User;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive { saveOrFail as saveOrFailTestAndLive; }

    protected $entity = 'user';

    protected $appFetchParamRules = [
        Entity::EMAIL               => 'sometimes|email|max:255'
    ];

    /**
     * Finds user by email.
     * Fetches the first user found for an email, or throws an exception.
     * @param string $email
     * @return Entity User entity
     * @throws BadRequestException
     */
    public function findByEmail(string $email)
    {
        $liveMode = $this->auth->getLiveConnection();

        return $this->newQueryWithConnection($liveMode)
                    ->where(Entity::EMAIL, '=', strtolower($email))
                    ->firstOrFailPublic();
    }

    /**
     * Finds user by contact number.
     * Fetches the first user found for a contact number, or throws an exception.
     * @param string $mobile
     * @return
     */
    public function findByMobile(string $mobile)
    {
        // for legacy reasons, a contact number can belong to multiple users
        // Return back all the users and let the calling function
        // choose what to do with it
        $liveMode = $this->auth->getLiveConnection();

        return $this->newQueryWithConnection($liveMode)
                    ->where(Entity::CONTACT_MOBILE, '=', $mobile)
                    ->get();
    }

    /**
     * @param string $mobile
     * @return Entity User entity
     * @throws BadRequestException
     */
    public function getUserFromMobileOrFail(string $mobile): Entity
    {
        $users = $this->findByMobile($mobile);

        if($users->count() === 1)
        {
            return $users->firstOrFail();
        }
        else if($users->count() === 0)
        {
            $this->trace->count(Metric::NO_ACCOUNTS_ASSOCIATED);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED,
                null,
                [
                    'internal_error_code' => ErrorCode::BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED,
                ]
            );
        }
        else
        {
            $this->trace->count(Metric::MULTIPLE_ACCOUNTS_ASSOCIATED);
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
                null,
                [
                    'internal_error_code' => ErrorCode::BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED,
                ]
            );
        }

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

    public function getUserFromId($user_id)
    {
        return $this->newQuery()
            ->where(Entity::ID, $user_id)
            ->firstOrFailPublic();
    }


    public function findUserWithContactNumbersExcludingUser(string $userIdToBeExcluded, array $numbers)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->whereIn(Entity::CONTACT_MOBILE, $numbers)
                    ->where(Entity::ID, '!=', $userIdToBeExcluded)
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

    public function getUserFromEmailCaseInsensitive(string $email)
    {
        return $this->newQuery()
                    ->whereRaw('lower('.Entity::EMAIL.') = (?)', [mb_strtolower($email)])
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
