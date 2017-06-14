<?php

namespace RZP\Models\Invitation;

use RZP\Models\Base;
use RZP\Models\User;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $invitation = (new Entity);

        $invitation->merchant()->associate($this->merchant);

        $invitation->build($input);

        // Associate user only if it exists
        try
        {
            $invitedUser = $this->repo->user->findByEmail($input[Entity::EMAIL]);

            $invitation->user()->associate($invitedUser);
        }
        catch (\Exception $e)
        {
        }

        $this->repo->saveOrFail($invitation);

        return $invitation;
    }

    public function list(): array
    {
        $merchant = $this->merchant;

        return $merchant->invitations->callOnEveryItem('toArrayPublic');
    }

    public function edit(Entity $invitation, array $input): Entity
    {
        $invitation->edit($input);

        $this->repo->saveOrFail($invitation);

        return $invitation;
    }

    /**
     * User can either accept or reject an invitation.
     * In both cases we need to delete the invitation.
     *
     * @param Entity $invitation
     * @param array  $input
     *
     * @return Entity
     */
    public function action(Entity $invitation, array $input): Entity
    {
        $invitation->edit($input, 'action');

        $action = $input[Entity::ACTION];

        $this->$action($invitation, $input[Entity::USER_ID]);

        $invitation->deleteOrFail();

        return $invitation;
    }

    /**
     * Accept an invitation.
     *
     * @param Entity $invitation
     * @param string $userId
     */
    public function accept(Entity $invitation, string $userId)
    {
        $updateParams = [
            Entity::ACTION           => User\Action::ATTACH,
            User\Entity::MERCHANT_ID => $invitation->getMerchantId(),
            User\Entity::ROLE        => $invitation->getRole()
        ];

        $user = $this->repo->user->findOrFailPublic($userId);

        $user = (new User\Core)->updateUserMerchantMapping($user, $updateParams);

        // We need to update the user in the invitation entity
        // Case 1: Invitation created for existing user
        //         In this case user_id would already be set
        // Case 2: Invitation created for non existent user
        //         In this case user_id would be not be set in create call,
        //         so we need to update the association on accepting the invite
        if ($invitation->getUserId() === null)
        {
            $invitation->user()->associate($user);

            $this->repo->saveOrFail($invitation);
        }
    }

    /**
     * In case of reject we just need to delete the invitation which is done in calling function
     *
     * @param string $userId
     * @param string $inviteId
     */
    public function reject(string $userId, string $inviteId)
    {

    }
}
