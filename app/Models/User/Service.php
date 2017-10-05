<?php

namespace RZP\Models\User;

use Hash;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Invitation;
use RZP\Models\Admin\AdminLead;

class Service extends Base\Service
{
    public function register(array $input): array
    {
        $referer = $input['ref'] ?? null;

        $invitationToken = $input['invitation'] ?? null;

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
                $adminId = $tokenData['admin_id'];

                $tokenSignUpInput = [
                    'signed_up' => 1
                ];

                (new AdminLead\Service)->editInvitation(
                    $tokenData['org_id'],
                    $tokenData['id'],
                    $tokenSignUpInput);
            }
        }

        /**
         * $user would not be null in a very rare edge case here
         * which happens when two subsequent invitations without either being
         * accepted. Once the second one is accepted, this block
         * is ignored and the $user found above will be used
         */
        if (empty($user) === true)
        {
            $input['password'] = Hash::make($input['password']);

            $input['password_confirmation'] = $input['password'];

            $input['name'] = '';

            unset($input['ref']);

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

            // Session::put('current_merchant_id', $invitation['merchant_id']);

            $this->confirm($user['id']);

            // $this->subscribeToMailingList($user);

            // $data['login'] = true;
        }
        else
        {
            $merchantInputData = [
                'email' => $user['email'],
                'name'  => $input['business_name'] ?? ''
            ];

            $merchantData = (new Merchant\Service)->create($merchantInputData);

            if ($referer)
            {
                $tagInputData = [
                    'tags' => ['ref-'.$referer],
                ];
            }

            (new Merchant\Service)->addTags($merchantData['id'], $tagInputData);

            $userMerchantMappingInputData = [
                'action' => 'attach',
                'role' => 'owner',
                'merchant_id' => $merchantData['id']
            ];

            $this->updateUserMerchantMapping($user['id'], $userMerchantMappingInputData);
        }

        return $user;
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

        return $user->toArrayPublic();
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
}
