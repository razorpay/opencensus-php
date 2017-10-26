<?php

namespace RZP\Models\User;

use Mail;
use Hash;
use Config;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Invitation;
use RZP\Models\Admin\AdminLead;
use RZP\Mail\User as UserMail;
use RZP\Models\User;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    public function register(array $input): array
    {
        $data = [];

        $referrer = $input['ref'] ?? '';

        $invitationToken = $input['invitation'] ?? null;

        $businessName = $input['business_name'] ?? '';

        $invitation = null;

        $user = null;

        /*
         * If we have an invitation token, the user may have created an account
         * in the meantime. $user will be equal to the user with the same email
         * as the invited user
         */
        if (empty($invitationToken) === false)
        {
            $invitation = (new Invitation\Service)->fetchByToken($invitationToken);

            $user = (new Core)->getUserFromEmail($invitation);

            // Since input would be lacking an email in case of registration via the invitation
            $input['email'] = $invitation['email'];

            unset($input['invitation']);
        }

        $heimdallInvitationToken = $input['merchant_invitation'] ?? null;

        $adminId = null;

        if (empty($heimdallInvitationToken) === false)
        {
            // Check if this token is valid or not
            $tokenData = (new AdminLead\Service)->verify($heimdallInvitationToken);

            if (isset($tokenData['id']) === true)
            {
                $tokenSignUpInput = [
                    'signed_up' => 1
                ];

                (new AdminLead\Service)->editInvitation(
                    $tokenData['org_id'],
                    $tokenData['id'],
                    $tokenSignUpInput);
            }

            unset($input['merchant_invitation']);
        }

        /**
         * $user would not be null in a very rare edge case here
         * which happens when two subsequent invitations without either being
         * accepted. Once the second one is accepted, this block
         * is ignored and the $user found above will be used
         */
        if (empty($user) === true)
        {
            $input['password_confirmation'] = $input['password'];

            $input['name'] = $input['name'] ?? '';

            unset($input['ref']);

            unset($input['business_name']);

            $user = $this->create($input);
        }

        /**
         * These two conditions are exclusive
         * One cannot accept an invite and create a merchant account at the same time
         */
        if (empty($invitationToken) === false)
        {
            $invitationAcceptInput = [
                'user_id' => $user['id'],
                'action' => 'accept'
            ];

            (new Invitation\Service)->action($invitation['id'], $invitationAcceptInput);

            $this->confirm($user['id']);

            (new Core)->subscribeToMailingList($user);

             $data['login'] = true;
        }
        else
        {
            $merchantInputData = [
                'email' => $user['email'],
                'name'  => $businessName,
            ];

            $data = $this->createMerchantFromUser($merchantInputData, $user, $referrer);
        }

        return $data;
    }

    /**
     * Creates Merchant for user.
     * @param array  $merchantInputData
     * @param array  $userData
     * @param string $referrer
     *
     * @return array
     */
    protected function createMerchantFromUser(array $merchantInputData, array $userData, string $referrer = '')
    {
        $merchantData = (new Merchant\Service)->create($merchantInputData);

        if (empty($referrer) === false)
        {
            $tagInputData = [
                'tags' => ['ref-'.$referrer],
            ];

            (new Merchant\Service)->addTags($merchantData['id'], $tagInputData);
        }

        $userMerchantMappingInputData = [
            'action' => 'attach',
            'role' => 'owner',
            'merchant_id' => $merchantData['id']
        ];

        $this->updateUserMerchantMapping($userData['id'], $userMerchantMappingInputData);

        $this->sendConfirmationMail($userData['id']);

        $data = $this->postSortingHat($userData, $merchantData, $referrer);

        return $data;
    }

    /**
     * Posts data to sorting Hat.
     * @param array  $user
     * @param array  $merchantData
     * @param string $referrer
     *
     * @return array
     */
    private function postSortingHat(array $user, array $merchantData, string $referrer)
    {
        $sortingData = $this->getSortingHatData($user, $merchantData, $referrer);

        if (Config::get('slack.enable') === true)
        {
            (new Core)->postSortingHatData($sortingData);
        }

        return [
            'id'    => $merchantData['id'],
            'name'  => $merchantData['name'],
            'email' => $user['email'],
        ];
    }

    private function getSortingHatData(array $user, array $merchantData, string $referrer)
    {
        $phoneNumber = $user[Entity::CONTACT_MOBILE] ?? '';

        $orgHostName = $this->auth->getOrgHostName();

        $merchantLink = "https://{$orgHostName}/admin#/app/merchants/{$merchantData['id']}/detail";

        $message = "[New Signup]($merchantLink) as {$user[Entity::NAME]}";

        if (empty($referrer) === false)
        {
            $message .= " | REF: $referrer";
        }

        if (empty($phoneNumber) === false)
        {
            $message .= " | [Call - {$phoneNumber}](tel:$phoneNumber)";
        }

        return [
            'id'            => $merchantData['id'],
            'email'         => $user[Entity::EMAIL],
            'name'          => $merchantData['name'],
            'message'       => $message,
            'token'         => Config::get('app.sorting_hat.token')
        ];
    }

    /**
     * @param $userId
     *
     * @return array
     */
    public function sendConfirmationMail($userId)
    {
        $user = $this->repo->user->findOrFailPublic($userId);

        // Only send the confirmation email if the user isn't already confirmed
        if ($user->getConfirmedAttribute() === false)
        {
            $orgId = $this->auth->getOrgId();

            $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

            $org['hostname'] = $this->auth->getOrgHostName();

            $confirmationMail = new UserMail\AccountVerification($user, $org);

            Mail::queue($confirmationMail);
        }
        else
        {
            // if user is already confirmed then sending confirm as true.
            return ['confirm' => true];
        }

        return ['success' => true];
    }

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

    public function confirm(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->confirm($user);

        return $user->toArrayPublic();
    }

    public function confirmUserByData(array $input): array
    {
        $user = (new Core)->confirmUserByData($input);

        $data = $user->toArrayPublic();

        (new Core)->subscribeToMailingList($data);

        return $data;
    }

    public function changePassword(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->changePassword($user, $input);

        return $user->toArrayPublic();
    }

    public function updateUserMerchantMapping(string $id, array $input): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $user = (new Core)->updateUserMerchantMapping($user, $input);

        return $user->toArrayPublic();
    }

    public function login(array $input): array
    {
        return (new Core)->login($input);
    }

    public function get(string $id): array
    {
        $user = $this->repo->user->findOrFailPublic($id);

        $response = (new Core)->get($user);

        return $response;
    }

    public function updateMerchantManageTeam(string $userId, array $input): array
    {
        $input['merchant_id'] = $this->merchant->getId();

        $teamData = [
            'merchant_id' => $input['merchant_id'],
            'user_id'     => $userId,
        ];

        (new User\Validator)->validateInput('teamManagement', $teamData);

        return $this->updateUserMerchantMapping($userId, $input);
    }

    /**
     * Upgrades given user to merchant.
     * @param array $input
     *
     * @return array
     */
    public function upgradeUserToMerchant(array $input)
    {
        $userId = $input['user_id'];

        $user = $this->repo->user->findOrFailPublic($userId)->toArrayPublic();

        $merchantData = [
            'name'  => $input['business_name'],
            'email' => $user['email'],
        ];

        $data = $this->createMerchantFromUser($merchantData, $user);

        return $data;
    }

    /**
     * Resend verification mail for not confirmed user.
     * @param string $userId
     *
     * @return array
     */
    public function resendVerificationMail()
    {
        $dashboardHeaders = $this->auth->getDashboardHeaders();

        $data = $this->sendConfirmationMail($dashboardHeaders['user_id']);

        return $data;
    }

    /**
     * Returns user by email.
     * @param string $email
     *
     * @return array
     */
    public function getUserByEmail(string $email)
    {
        $user = $this->repo->user->findByEmail($email);

        $responseData = [
            'id' => $user->getId(),
        ];

        return $responseData;
    }
}
