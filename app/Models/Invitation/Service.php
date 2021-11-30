<?php

namespace RZP\Models\Invitation;

use RZP\Models\Base;
use RZP\Models\User\AxisUserRole;


class Service extends Base\Service
{
    /**
     * Create invitation for a merchant.
     *
     * @param  array  $input
     * @return array
     */
    public function create(array $input): array
    {
        $input[Entity::PRODUCT] = $this->auth->getRequestOriginProduct();

        $invitation = $this->core()->create($input);

        return $invitation->toArrayPublic();
    }

    /**
     * Fetch Invitation by Token
     *
     * @param  string  $token
     * @return array
     */
    public function fetchByToken(string $token): array
    {
        $invitation = $this->core()->fetchByToken($token);

        return $invitation->toArrayPublic();
    }

    /**
     * Get all pending invitations of a merchant
     *
     * @return array
     */
    public function list(): array
    {
        $product = $this->auth->getRequestOriginProduct();

        $invitations = $this->core()->list($product);

        return $invitations;
    }

    /**
     * Resend Invitation Mail
     *
     * @param  string $inviteId
     * @return array
     */
    public function resend(string $inviteId, array $input): array
    {
        $invitation = $this->repo->invitation->findByIdAndMerchant($inviteId, $this->merchant);

        $this->core()->resend($invitation, $input);

        return $invitation->toArrayPublic();
    }

    /**
     * Update the given invitation only if its in pending state.
     *
     * @param  string $inviteId
     * @param array   $input
     * @return array
     */
    public function edit(string $inviteId, array $input): array
    {
        $invitation = $this->repo->invitation->findByIdAndMerchant($inviteId, $this->merchant);

        $invitation = $this->core()->edit($invitation, $input);

        return $invitation->toArrayPublic();
    }

    /**
     * This operation will only be done by merchant.
     *
     * @param  string $inviteId
     * @return array
     */
    public function delete(string $inviteId): array
    {
        $invitation = $this->repo->invitation->findByIdAndMerchant($inviteId, $this->merchant);

        $invitation->deleteOrFail();

        return $invitation->toArrayPublic();
    }

    /**
     * User can either accept or reject the invitation
     *
     * @param  string $inviteId
     * @param  array  $input
     * @return array
     */
    public function action(string $inviteId, array $input): array
    {
        $user = $this->app['basicauth']->getUser();

        $userEmail = $user ? $user->getEmail() : $input['email'];

        unset($input['email']);

        $invitation = $this->repo->invitation->findByIdAndEmail($inviteId, $userEmail);

        $this->core()->action($invitation, $input);

        return $invitation->toArrayPublic();
    }

    /**
     * Create Axis draft invitation for a merchant.
     *
     * @param  array  $input
     * @return array
     */

    public function sendAxisInvitations(array $input): array
    {
        $invitation = $this->core()->createInvitationDraft($input);

        if($input[Entity::ROLE] == AxisUserRole::AUTHORISED_SIGNATORY) {

            unset($input[Entity::MERCHANT_ID]);

            $this->core()->create($input);

        }
        return $invitation->toArrayPublic();
    }


    /**
     * Email Invitation Mail
     *
     * @param  array $input
     *
     * @return array
     */
    public function acceptDraftInvitations(array $input)
    {
        return $this->core()->acceptDraftInvitations($input);
    }

    /**
     * Get all draft invitations of a merchant
     *
     * @return array
     */
    public function listDraftInvitations(array $input): array
    {
        $product = $input[Entity::PRODUCT];

        $invitations = $this->core()->listDraftInvitations($product);

        return $invitations;
    }

}
