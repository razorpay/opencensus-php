<?php

namespace RZP\Models\Invitation;

use Mail;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Mail\Invitation\Invite as InvitationMail;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $input[Entity::TOKEN] = str_random(40);

        $invitation = (new Entity);

        $invitation->merchant()->associate($this->merchant);

        $invitation->build($input);

        $senderName = $this->getSenderName($input);

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

        $this->trace->info(TraceCode::INVITATION_CREATE, $invitation->toArrayPublic());

        $this->sendEmail($invitation, $senderName);

        return $invitation;
    }

    public function fetchByToken(string $token): Entity
    {
        $invitation = $this->repo->invitation->fetchByToken($token);

        return $invitation;
    }

    public function list($product = Product::PRIMARY): array
    {
        $merchant = $this->merchant;

        return $merchant->invitations()->where(Entity::PRODUCT, $product)->get()->callOnEveryItem('toArrayPublic');
    }

    public function edit(Entity $invitation, array $input): Entity
    {
        $invitation->edit($input);

        $this->repo->saveOrFail($invitation);

        $this->trace->info(TraceCode::INVITATION_EDIT, $invitation->toArrayPublic());

        return $invitation;
    }

    public function resend(Entity $invitation, array $input): Entity
    {
        $invitation->edit($input, 'resend');

        $senderName = $this->getSenderName($input);

        $this->sendEmail($invitation, $senderName);

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
    protected function accept(Entity $invitation, string $userId)
    {
        $updateParams = [
            Entity::ACTION           => User\Action::ATTACH,
            User\Entity::MERCHANT_ID => $invitation->getMerchantId(),
            User\Entity::ROLE        => $invitation->getRole(),
            Entity::PRODUCT          => $invitation->getProduct(),
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

        $this->trace->info(
            TraceCode::INVITATION_ACCEPT,
            [
                'invitation' => $invitation->toArrayPublic(),
                'user_id'    => $userId
            ]);
    }

    /**
     * In case of reject we just need to delete the invitation which is done in calling function
     *
     * @param Entity $invitation
     * @param string $userId
     */
    protected function reject(Entity $invitation, string $userId)
    {
        $this->trace->info(
            TraceCode::INVITATION_REJECT,
            [
                'invitation' => $invitation->toArrayPublic(),
                'user_id'    => $userId
            ]);
    }

    protected function getSenderName(array $input)
    {
        if (empty($input[Entity::SENDER_NAME] === true))
        {
            return $this->merchant->getName();
        }
        else
        {
            return $input[Entity::SENDER_NAME];
        }
    }

    protected function sendEmail(Entity $invitation, string $senderName)
    {
        $data = [
            'sender_name' => $senderName,
            'email'       => $invitation->getEmail(),
            'name'        => $this->merchant->getName(),
            'token'       => $invitation->getToken(),
            'user_id'     => $invitation->getUserId(),
            'product'     => $invitation->getProduct(),
        ];

        $this->trace->info(
            TraceCode::INVITATION_EMAIL,
            [
                'invitation_id' => $invitation->getId(),
                'sender_name'   => $senderName,
                'email'         => $invitation->getEmail(),
                'name'          => $this->merchant->getName(),
                'user_id'       => $invitation->getUserId(),
                'product'     => $invitation->getProduct(),
            ]);

        $invitationMail = new InvitationMail($data);

        Mail::queue($invitationMail);
    }
}
