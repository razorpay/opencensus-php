<?php

namespace RZP\Models\Invitation;

use RZP\Models\Base;

class Service extends Base\Service
{
    /**
     * Create invitation for a merchant.
     *
     * @param  array  $input [
     *                            id      => as generated from Dashboard
     *                            email   => member's email to whom invite has to be sent
     *                            role    => member's role
     *                            token   => as generated from Dashboard
     *                        ]
     * @return array
     */
    public function create(array $input): array
    {
        $invitation = $this->core()->create($input);

        return $invitation->toArrayPublic();
    }

    /**
     * Fetch Invitation by Token
     *
     * @param  array  $input
     * @return array
     */
    public function fetchByToken(string $token): array
    {
        $invitation = $this->core()->fetchByToken($token);

        return $invitation->toArrayPublic();
    }

    /**
     * Get all pending invitations of a merchant
     * @return array
     */
    public function list(): array
    {
        $invitations = $this->core()->list();

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
        $invitation = $this->repo->invitation->findOrFailPublic($inviteId);

        $this->core()->resend($invitation, $input);

        return $invitation->toArrayPublic();
    }

    /**
     * Update the given invitation only if its in pending state.
     *
     * @param  string $inviteId
     * @param array   $input
     *
     * @return array
     */
    public function edit(string $inviteId, array $input): array
    {
        $invitation = $this->repo->invitation->findOrFailPublic($inviteId);

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
        $invitation = $this->repo->invitation->findOrFailPublic($inviteId);

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
        $invitation = $this->repo->invitation->findOrFailPublic($inviteId);

        $this->core()->action($invitation, $input);

        return $invitation->toArrayPublic();
    }
}
