<?php

namespace RZP\Models\Invitation;

use RZP\Error\PublicErrorDescription;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Constants\Product;
use RZP\Models\User\AxisUserRole;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Entity as MerchantEntity;

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

        if (empty($input[Entity::INVITATIONTYPE]) === false && $input[Entity::INVITATIONTYPE] == 'integration_invitation')
        {
            return $this->core()->createXAccountingIntegrationInvitation($input,true);
        }

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
     * @throws BadRequestException
     */
    public function createBankLmsUserInvitation(array $input)
    {
        (new Validator())->validateInput(Validator::CREATE_BANK_LMS_USER, $input);

        $this->merchant = $this->repo->banking_account_bank_lms->fetchPartnerMerchant();

        $input[Entity::PRODUCT] = Product::BANKING;

        $invitation = $this->core()->createBankLmsUserInvitation($input, $this->merchant);

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

    public function createVendorPortalInvitation(MerchantEntity $merchant, array $request): array
    {
        // Validation check: Email id is mandatory
        if (empty($request['contact_id'])) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, null, null, PublicErrorDescription::BAD_REQUEST_CONTACT_ID_MISSING_FOR_INVITATION);
        }

        $contact = $this->repo->contact->findByPublicIdAndMerchant($request['contact_id'], $merchant);

        if (empty($contact->getEmail())) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, null, null, PublicErrorDescription::BAD_REQUEST_CONTACT_WITHOUT_EMAIL);
        }

        $input = [
            Entity::EMAIL   => $contact->getEmail(),
            Entity::ROLE    => Role::VENDOR,
            Entity::PRODUCT => Product::BANKING,
        ];

        $invitation = $this->core()->createVendorPortalInvitation($input, $contact->getPublicId());

        return $invitation->toArrayPublic();
    }

    public function resendVendorPortalInvitation(MerchantEntity $merchant, array $request): array
    {
        if (empty($request['contact_id'])) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, null, null, PublicErrorDescription::BAD_REQUEST_CONTACT_ID_MISSING_FOR_INVITATION);
        }

        $invitation = $this->core()->resendVendorPortalInvitation($merchant, $request['contact_id']);

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

    public function resendXAccountingIntegrationInvites(array $request): array
    {
        if (empty($request['to_email_id'])) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, null, null, PublicErrorDescription::BAD_REQUEST_TO_EMAIL_ID_MISSING_FOR_INTEGRATION_INVITATION);
        }

        return $this->core()->resendXAccountingIntegrationInvites($request['to_email_id']);
    }

}
