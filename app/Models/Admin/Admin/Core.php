<?php

namespace RZP\Models\Admin\Admin;

use Mail;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Action;
use RZP\Models\User\Core as UserCore;
use RZP\Mail\Admin\Account\Otp as OtpMail;
use RZP\Mail\Admin\Account\AccountLockedWrongAttemptMail as AccountLockedWrongAttemptMail;


class Core extends Base\Core
{
    public function create(Org\Entity $org, array $input)
    {
        $admin = (new Entity)->generateId();

        $admin->setAuditAction(Action::CREATE_ADMIN);

        $admin->org()->associate($org);

        (new Validator)->validatePasswordAuthType(
            $org->getAuthType(), $input);

        $admin->build($input);

        // Validate the admin email should be unique for the org
        $existingAdmin = $this->repo->admin->findByOrgIdAndEmail($org->getId(), $admin->getEmail());
        if ($existingAdmin !== null) {
            throw new Exception\BadRequestValidationFailureException(
                "admin email should be unique value");
        }

        // order of arg is important for diff to be stored in ES
        // This is done to apply eloquent casts to input
        $dirtyData = array_merge($input, $admin->toArray());

        $this->app['workflow']
            ->setEntityAndId($admin->getEntity(), $admin->getId())
            ->handle((new \StdClass()), $dirtyData);

        $this->repo->saveOrFail($admin);

        $this->associateRelevantEntitiesToAdmin($admin, $input);

        $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
            $admin->getId(), $org->getId(), [Entity::ROLES, Entity::GROUPS]);

        return $admin;
    }

    public function createAuthToken(Entity $admin, array $input)
    {
        $token = new Token\Entity;

        $token->generateId();

        $token->build($input);

        $token->admin()->associate($admin);

        $this->repo->saveOrFail($token);

        return $token;
    }

    public function delete(Entity $admin)
    {
        $this->repo->deleteOrFail($admin);

        return $admin->toArrayDeleted();
    }

    public function edit(Entity $admin, array $input)
    {
        $admin->setAuditAction(Action::EDIT_ADMIN);

        $admin->edit($input);

        $this->repo->saveOrFail($admin);

        $this->associateRelevantEntitiesToAdmin($admin, $input);

        $admin = $this->repo->admin->findByIdAndOrgIdWithRelations(
            $admin->getId(), $admin['org_id'], ['roles', 'groups']);

        return $admin;
    }

    private function associateRelevantEntitiesToAdmin(Entity $admin, array $input)
    {
        if (isset($input[Entity::ROLES]) === true)
        {
            $this->repo->role->validateExists($input[Entity::ROLES]);

            $this->repo->sync($admin, Entity::ROLES, $input[Entity::ROLES]);
        }

        if (isset($input[Entity::GROUPS]) === true)
        {
            $this->repo->group->validateExists($input[Entity::GROUPS]);

            $this->repo->sync($admin, Entity::GROUPS, $input[Entity::GROUPS]);
        }
    }

    public function updatePassword(
        Entity $admin,
        array $input,
        bool $forgotPassword = true,
        $updateType = 'reset')
    {
        $validator = new Validator($admin);

        $validator->validateInput($updateType, $input);

        $admin->setAuditAction(Action::RESET_PASSWORD);

        // In case of forgotten passwords, oldPassword is not present.
        // In case of voluntary change of password, we would require
        // oldPassword
        if ($forgotPassword === false)
        {
            $oldPassword = $input['old_password'];

            $admin->setAuditAction(
                Action::RESET_PASSWORD_INVALID_OLD_PASSWORD);

            if ($admin->matchPassword($oldPassword) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old Password is incorrect');
            }
        }

        $admin->setPassword($input['password']);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $adminIds
     *
     * @return array
     */
    public function assignMerchantToAdmins(Merchant\Entity $merchant, array $adminIds)
    {
        $this->trace->info(TraceCode::MERCHANT_ADMIN_ATTACH_REQUEST,
                           [
                               'action'   => 'attach_in_merchant_map',
                               'merchantId' => $merchant->getId(),
                               'adminIds' => $adminIds,
                           ]
        );

        $this->repo->sync($merchant, 'admins', $adminIds);

        return $merchant->toArrayPublic();
    }

    /**
     * @param Group\Entity $group
     * @param array        $adminIds
     *
     * @return array
     */
    public function assigningGroupToAdmins(Group\Entity $group, array $adminIds)
    {
        $this->trace->info(TraceCode::GROUP_ADMIN_ATTACH_REQUEST,
                           [
                               'action'   => 'attach_in_group_map',
                               'groupId'    => $group->getId(),
                               'adminIds' => $adminIds,
                           ]
        );

        $this->repo->sync($group, 'admins', $adminIds, false);

        return $group->toArrayPublic();
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $adminIds
     *
     * @return array
     */
    public function removeMerchantFromAdmins(Merchant\Entity $merchant, array $adminIds)
    {
        $this->trace->info(TraceCode::MERCHANT_ADMIN_DETACH_REQUEST,
                           [
                               'action'   => 'detach_in_merchant_map',
                               'merchantId' => $merchant->getId(),
                               'adminIds' => $adminIds,
                           ]
        );

        $this->repo->detach($merchant, 'admins', $adminIds);

        return $merchant->toArrayPublic();
    }

    /**
     * @param Group\Entity $group
     * @param array        $adminIds
     *
     * @return array
     */
    public function removeGroupsFromAdmins(Group\Entity $group, array $adminIds)
    {
        $this->trace->info(TraceCode::GROUP_ADMIN_DETACH_REQUEST,
                           [
                               'action'   => 'detach_in_group_map',
                               'groupId'    => $group->getId(),
                               'adminIds' => $adminIds,
                           ]
        );

        $this->repo->detach($group, 'admins', $adminIds);

        return $group->toArrayPublic();
    }

    public function checkSecondFactorAuthAndSendOtp($admin){

        $this->checkAdminAccountNotLockedOrThrowException($admin);

        if ($admin->isOrgEnforcedSecondFactorAuth() === false)
        {
            return;
        }
        $this->trace->info(TraceCode::ADMIN_LOGIN_2FA_ENABLED, ['admin_id' => $admin->getId()]);

        $this->sendOtpForSecondFactorAuthOnLogin($admin);

    }

    public function verifyAdminSecondFactorAuth(Entity $admin, array $input)
    {
        $validator = new Validator($admin);

        $validator->validateInput('verify_admin_second_factor', $input);

        $this->checkAdminAccountNotLockedOrThrowException($admin);

        return $this->verifyOtpForSecondFactorAuthOnLogin($admin, $input);

    }

    private function verifyOtpForSecondFactorAuthOnLogin(Entity $admin, array $input)
    {
        if ($this->isCorrectOtpForSecondFactorAuthOnLogin($admin, $input) === true)
        {
            $this->trace->info(TraceCode::LOGIN_2FA_CORRECT_OTP);

            $this->resetAdminWrong2faAttempts($admin);

            return $admin;
        }
        else
        {
            //if the otp is incorrect, increment the number of wrong 2fa attempts.
            $this->incrementWrong2faAttempts($admin);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ADMIN_2FA_LOGIN_INCORRECT_OTP,
                null,
                [
                    'internal_error_code'    => ErrorCode::BAD_REQUEST_ADMIN_2FA_LOGIN_INCORRECT_OTP,
                    'admin_details'           => [
                        'admin_id' => $admin->getId(),
                        'account_locked' => $admin->isLocked()
                    ],
                ]);
        }
    }

    private function isCorrectOtpForSecondFactorAuthOnLogin($admin, $input)
    {
        $data = [
            Constant::MEDIUM => Entity::EMAIL,
            Constant::ACTION => 'verify_2fa',
            Constant::OTP    => $input['otp'],
            Constant::TOKEN   => $admin->getId()
        ];

        try
        {
            $response = $this->verifyOtp($data, $admin);

            $this->trace->info(TraceCode::RESPONSE, ['response'=> $response]);

            if((isset($response['success']) === false) or
                ($response['success'] !== true))
            {
                $success = false;
            }
            else
            {
                $success = true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::VERIFY_2FA_OTP_EMAIL_FOR_ACTION_FAILED, [
                'exception' => $e->getMessage(),
                'action'    => 'verify_2fa',
            ]);
            $success = false;
        }

        return $success;
    }

    public function verifyOtp(array $input, Entity $admin, bool $mock = false)
    {
        $otp = $input['otp'];

        unset($input['otp']);

        //Unset OTP for logging
        $this->trace->info(TraceCode::ADMINS_VERIFY_OTP_FOR_ACTION, compact('input'));

        $input['otp'] = $otp;

        $payload = $this->getTokenAndRavenOtpReqParams($input, $admin);

        $payload = array_only($payload, ['context', 'receiver', 'source']) + array_only($input, 'otp');

        return $this->app->raven->verifyOtp($payload, $mock);
    }

    private function resetAdminWrong2faAttempts(Entity $admin)
    {
        if (($admin->getWrong2faAttempts() !== 0))
        {
            $admin->setWrong2faAttempts(0);

            $this->repo->saveOrFail($admin);
        }
    }

    private function incrementWrong2faAttempts(Entity $admin)
    {
        $wrongTries = $admin->getWrong2faAttempts() + 1;

        $admin->setWrong2faAttempts($wrongTries);

        $this->trace->info(TraceCode::ADMIN_LOGIN_2FA_WRONG_OTP, ['admin_id' => $admin->getId()]);

        $this->lockAcountCheck($wrongTries,$admin);

    }

    private function lockAcountCheck($wrongTries,Entity $admin)
    {
        $maxWrongTries = $this->config->get('applications.admin_2fa.max_incorrect_tries');

        $orgId = $this->app['basicauth']->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId);

        $maxWrongTries = min($maxWrongTries, $org->getAdminMaxWrong2FaAttempts());

        if ($wrongTries >= $maxWrongTries)
        {
            $admin->lock(true);

            $this->trace->info(TraceCode::ADMIN_LOGIN_2FA_ACCOUNT_LOCKED, ['user_id' => $admin->getId()]);
        }

        $this->repo->saveOrFail($admin);

        if ($admin->isLocked() === true) {
            $this->notifyUserAboutAccountLocked($admin);
        }
    }

    private function notifyUserAboutAccountLocked(Entity $admin)
    {
        $data = [
            'admin'  => [
                Entity::ID              => $admin->getId(),
                Entity::EMAIL           => $admin->getEmail(),
                Entity::NAME            => $admin->getName(),
            ],
        ];

        $email = new AccountLockedWrongAttemptMail($data);

        Mail::queue($email);

    }

    private function checkAdminAccountNotLockedOrThrowException(Entity $admin)
    {
        if ($admin->isLocked() === false) {
            return;
        }
        $this->trace->info(TraceCode::ADMIN_2FA_LOCKED, ['admin_id' => $admin->getId()]);

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_LOCKED_ADMIN_LOGIN,
            null,
            [
                'internal_error_code' => ErrorCode::BAD_REQUEST_LOCKED_ADMIN_LOGIN,
                'admin_details' => [
                    'account_locked' => true,
                    'admin_id' => $admin->getId()
                ],
            ]);
    }

    private function sendOtpForSecondFactorAuthOnLogin(Entity $admin)
    {

        $this->send2faOtp($admin);

        $this->trace->info(TraceCode::ADMIN_LOGIN_2FA_OTP_SENT, ['admin_id' => $admin->getId()]);

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ADMIN_2FA_LOGIN_OTP_REQUIRED,
            null,
            [
                'internal_error_code' => ErrorCode::BAD_REQUEST_ADMIN_2FA_LOGIN_OTP_REQUIRED,
                'admin_details'        => [
                    'admin_id'        => $admin->getId(),
                    'account_locked' => $admin->isLocked(),
                ],
            ]);
    }
    public function send2faOtp(Entity $admin)
    {
        $input = [
            Constant::MEDIUM => Entity::EMAIL,
            Constant::ACTION => 'verify_2fa',
            Constant::TOKEN   => $admin->getId()
        ];

        $this->sendOtp($input, $admin);

        $this->trace->info(TraceCode::ADMIN_2FA_OTP_SENT, ['admin_id' => $admin->getId()]);

    }

    public function sendOtp(array $input,Entity $admin): array
    {
        $this->trace->info(TraceCode::ADMIN_SEND_OTP_FOR_ACTION, compact('input'));

        $func = 'sendOtpVia' . studly_case($input[Constant::MEDIUM] ?? 'email');

        return $this->$func($input, $admin);
    }

    public function sendOtpViaEmail(array $input, Entity $admin): array
    {
        $org = $this->getOrg();
        $orgBusinessName = $org->getBusinessName();

        $otp = $this->generateOtpFromRaven($input, $admin);

        $mailable = new OtpMail($input, $admin, $orgBusinessName, $otp, $org);
        Mail::queue($mailable);

        return array_only($otp, 'token');
    }


    private function getOrg()
    {
        $orgId = $this->app['basicauth']->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId);

        return $org;
    }

    protected function generateOtpFromRaven(array $input, Entity $admin): array
    {
        $payload = $this->getTokenAndRavenOtpReqParams($input, $admin);

        $token = array_pull($payload, 'token');

        $otp = $this->app->raven->generateOtp($payload);

        return $otp + compact('token');
    }

    protected function getTokenAndRavenOtpReqParams(array $input,Entity $admin): array
    {
        $token = $input['token'] ?? Entity::generateUniqueId();

        $context = sprintf('%s:%s:%s', $admin->getId(), $input[Constant::ACTION], $token);  // use constant 1

        $source = 'api.2fa.otp.auth';

        if (((isset($input['medium']) === true) and ($input['medium'] === 'email')))
        {
            $expires_at = 20;
            $receiver = $admin->getEmail();

            $response = compact(
                'token',
                'receiver',
                'context',
                'source',
                'expires_at');
        }


        return $response;
    }
    /*
     * Exception is thrown if 2FA settings are enforced by org so if 2FA is
     * enforced on admin's org , admin cannot change the settings
     */

    public function change2faSetting(Entity $admin, array $input): array
    {
        if ($admin->isOrgEnforcedSecondFactorAuth() === true) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ORG_2FA_ENFORCED);
        }

        $org = $this->getOrg();

        $action = $input[Constant::SECOND_FACTOR_AUTH];

        $org->setAdmin2FaEnabled($action);

        $this->repo->org->saveOrFail($org);

        return [
            Org\Entity::ADMIN_SECOND_FACTOR_AUTH => $org->isAdmin2FaEnabled(),
        ];
    }


    public function accountLockUnlock(Entity $admin, string $action): array
    {
        $traceInfo = [
            Constant::ADMIN_ID => $admin->getId(),
            Constant::ACTION  => $action,
        ];

        $this->trace->info(TraceCode::ADMIN_ACCOUNT_LOCK_UNLOCK_ACTION, $traceInfo);

        switch ($action)
        {
            case Constant::LOCK:

                $admin->lock(true);

                break;

            case Constant::UNLOCK:
                $admin->setWrong2faAttempts(0);

                $admin->unlock();

                break;

        }
        $this->repo->saveOrFail($admin);

        return [
            Entity::LOCKED => $admin->isLocked(),
            Constant::ID => 'admin_'.$admin->getPublicId(),
        ];
    }

    public function resendOtp(Entity $admin)
    {
        $input = [
            Constant::MEDIUM => Entity::EMAIL,
            Constant::ACTION => 'verify_2fa',
            Constant::TOKEN   => $admin->getId()

        ];

        $this->sendOtp($input, $admin);

        $this->trace->info(TraceCode::ADMIN_2FA_OTP_RESENT, ['admin_id' => $admin->getId()]);

        return [
            'otp_send' => true,
        ];
    }


    public function addRoles(Entity $admin, array $roleIDs) : Entity
    {
        $this->trace->info(TraceCode::ADMIN_ADD_ROLES_REQUEST,
            [
                'action'      => 'add_roles_to_admin',
                'admin_email' => $admin->getEmail(),
                'role_ids'    => $roleIDs,
            ]
        );

        $this->repo->sync($admin, Entity::ROLES, $roleIDs, false);
        return $admin;
    }

}
