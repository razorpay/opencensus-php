<?php

namespace RZP\Models\Invitation;

use Mail;
use Illuminate\Support\Collection;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Mail\Invitation\Invite as InvitationMail;
use RZP\Mail\Invitation\Razorpayx\Invite as RazorpayXInvitationMail;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $input[Entity::TOKEN] = str_random(40);

        $invitation = (new Entity);

        $invitation->merchant()->associate($this->merchant);

        $invitation->build($input);

        $senderName = $this->getSenderName($input);

        $invitedUser = $this->repo->user->getUserFromEmail(strtolower($input[Entity::EMAIL]));

        $allMerchantsForInvitedUser = optional($invitedUser)->merchants;

        if (empty($invitedUser) === false)
        {
            $variant = $this->app->razorx->getTreatment(
                $this->app['request']->getId(),
                Merchant\RazorxTreatment::SECOND_FACTOR_AUTH_PROJECT_EXP,
                $this->mode
            );

            if (strtolower($variant) === 'on')
            {
                $merchantCollections = $invitedUser->merchants()->get();

                //
                // if the invitedUser is restricted or
                // merchant is restricted and user is associated with any other merchant
                // then invitation action is not performed
                //
                if ((count($merchantCollections) > 0 and
                     $this->merchant->getRestricted() === true) or
                    $invitedUser->getRestricted() === true)
                {
                    $this->trace->info(
                        TraceCode::INVITATION_CREATE_FAILED, [
                        Entity::MERCHANT_ID                       => $this->merchant['id'],
                        'invited_user_merchant_count'             => count($merchantCollections),
                        'merchant_' . Merchant\Entity::RESTRICTED => $this->merchant->getRestricted(),
                        'user_' . Merchant\Entity::RESTRICTED     => $invitedUser->getRestricted(),
                    ]);

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVITATION_CREATE_FAILED);
                }
            }

            // Associate user only if it exists
            $invitation->user()->associate($invitedUser);
        }

        $this->repo->saveOrFail($invitation);

        $this->trace->info(TraceCode::INVITATION_CREATE, $invitation->toArrayPublic());

        $invitedUserExists = (empty($invitedUser) === false);

        $this->sendEmail($invitation, $senderName, $invitedUserExists, $allMerchantsForInvitedUser);

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

        $invitedUser = $this->repo->user->getUserFromEmail($invitation->getEmail());

        $allMerchantsForInvitedUser = optional($invitedUser)->merchants;

        $invitedUserExists = (empty($invitedUser) === false);

        $this->sendEmail($invitation, $senderName, $invitedUserExists, $allMerchantsForInvitedUser);

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
     *
     * @throws Exception\BadRequestException
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

        $variant = $this->app->razorx->getTreatment(
            $this->app['request']->getId(),
            Merchant\RazorxTreatment::SECOND_FACTOR_AUTH_PROJECT_EXP,
            $this->mode
        );

        if (strtolower($variant) === 'on')
        {
            $merchantCollections = $user->merchants()->get();

            $merchantInvited = $this->repo->merchant->findOrFailPublic($invitation->getMerchantId());

            //
            // [ if the invitedUser is restricted (belongs to restricted merchant) ] or
            // [ merchant who has send the invitation becomes restricted
            //   and user is associated with any other merchant ]
            //   then invitation accept is not performed or will be failed.
            //
            if ((count($merchantCollections) > 0 and
                 $merchantInvited->getRestricted() === true) or
                $user->getRestricted() === true)
            {
                // delete the invitation
                $invitation->deleteOrFail();

                $this->trace->info(
                    TraceCode::INVITATION_ACCEPT_FAILED, [
                    Entity::MERCHANT_ID                       => $merchantInvited['id'],
                    'invited_user_merchant_count'             => count($merchantCollections),
                    'merchant_' . Merchant\Entity::RESTRICTED => $merchantInvited->getRestricted(),
                    'user_' . Merchant\Entity::RESTRICTED     => $user->getRestricted(),
                ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVITATION_ACCEPT_FAILED);
            }
        }

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

    protected function sendEmail(Entity $invitation, string $senderName, bool $invitedUserExists, Collection $allMerchantsForInvitedUser = null)
    {
        $product = $invitation->getProduct();

        $this->trace->info(
            TraceCode::INVITATION_EMAIL,
            [
                'invitation_id' => $invitation->getId(),
                'sender_name'   => $senderName,
                'email'         => $invitation->getEmail(),
                'name'          => $this->merchant->getName(),
                'user_id'       => $invitation->getUserId(),
                'product'       => $product,
            ]);

        if ($product === Product::PRIMARY)
        {
            $data = [
                'sender_name' => $senderName,
                'email'       => $invitation->getEmail(),
                'name'        => $this->merchant->getName(),
                'token'       => $invitation->getToken(),
                'user_id'     => $invitation->getUserId(),
                'product'     => $product,
            ];

            $invitationMail = new InvitationMail($data);

            Mail::queue($invitationMail);
        }
        elseif ($product === Product::BANKING)
        {
            $inviteMailer = new RazorpayXInvitationMail($invitation->getId(), $senderName, $invitedUserExists, $allMerchantsForInvitedUser);

            Mail::queue($inviteMailer);
        }
    }
}
