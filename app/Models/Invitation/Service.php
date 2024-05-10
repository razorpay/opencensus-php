<?php

namespace RZP\Models\Invitation;

use RZP\Error\PublicErrorDescription;
use RZP\Models\User;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Constants\Product;
use RZP\Models\User\AxisUserRole;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Trace\TraceCode;

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

        $properties = [
            'id'            => $input[Entity::EMAIL],
            'experiment_id' => $this->app['config']->get('app.invite_merchant_with_2FA_experiment_id'),
        ];

        $isLinkedAccount = $this->merchant->isLinkedAccount();

        $isOtpVerificationExperimentEnabled = $this->core()->isSplitzExperimentEnable($properties, 'enabled');

        if ($isLinkedAccount === false && ($this->auth->getProduct() === Product::BANKING || $isOtpVerificationExperimentEnabled))
        {
            (new Validator())->setStrictFalse()->validateInput(Validator::CREATE_INVITATION_VERIFY_OTP, $input);
            $userCore = new User\Core;

            $userCore->verifyOtp($input + ['action' => $input['action']],
                $this->merchant,
                $this->user,
                $this->mode === Mode::TEST);

            $input = array_except($input, ['otp', 'token', 'action']);
        }

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

    public function createVendorPortalInvitationV2(array $input): array
    {
        $this->trace->info(TraceCode::VENDOR_EXPERIENCE_VENDOR_PORTAL_INVITE, [
            'create_input_input' => $input,
        ]);

        (new Entity)->getValidator()->setStrictFalse()->validateInput(Validator::CREATE_INVITATION_VENDOR_PORTAL_V2, $input);

        $input = [
            Entity::EMAIL   => $input[Entity::EMAIL],
            Entity::ROLE    => Role::VENDOR,
            Entity::PRODUCT => Product::BANKING,
        ];

        return $this->core()->createVendorPortalInvitationV2($input);
    }

    public function handleVendorPortalV2Invitation(string $invitationId, array $input): Entity
    {
        $this->trace->info(TraceCode::VENDOR_EXPERIENCE_VENDOR_PORTAL_INVITE, [
            'accept_invite_input' => $input,
        ]);

        (new Entity)->getValidator()->setStrictFalse()->validateInput(Validator::HANDLE_INVITATION_VENDOR_PORTAL_V2, $input);

        $userEmail = $input[Entity::EMAIL];

        $invitation = $this->repo->invitation->findByIdAndEmail($invitationId, $userEmail);

        $vendorPortalMerchantId = $this->app['config']['applications.vendor_payments']['vendor_portal_merchant_id'];

        if ($vendorPortalMerchantId != $invitation->getMerchantId())
        {
            $this->trace->info(TraceCode::VENDOR_EXPERIENCE_SIGNUP_WITH_INVLAID_TOKEN, [
                'invitation_merchant_id' => $invitation->getMerchantId(),
            ]);

            // No point in throwing the error, registration will work, is already created
            // We can just log the error and return without deleting the invitation
            // throw new BadRequestException(ErrorCode::BAD_REQUEST_INVITATION_ACCEPT_INVALID_INVITATION, null, null, PublicErrorDescription::BAD_REQUEST_INVITATION_ACCEPT_INVALID_INVITATION);

            return $invitation;
        }

        $response = $this->core()->handleVendorPortalInvitationV2($invitation, $input);

        $user = $this->repo->user->findByEmail($userEmail);

        (new User\Service())->createVendorEntities([
            User\Entity::EMAIL  => $user->getEmail(),
            User\Entity::NAME   => $user->getName(),
        ]);

        return $response;
    }

    public function resendVendorPortalInvitation(MerchantEntity $merchant, array $request): array
    {
        if (empty($request['contact_id'])) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, null, null, PublicErrorDescription::BAD_REQUEST_CONTACT_ID_MISSING_FOR_INVITATION);
        }

        $invitation = $this->core()->resendVendorPortalInvitation($merchant, $request['contact_id']);

        return $invitation->toArrayPublic();
    }

    public function createXperienceUserInvitation(array $input): array
    {
        $input[Entity::PRODUCT] = $input[Entity::PRODUCT] ?? Product::BANKING;

        $invitation = $this->core()->createXperienceUserInvitation($input);

        return $invitation->toArrayInternal();
    }

    public function resendXperienceUserInvitation(string $inviteId, array $input): array
    {
        (new Validator())->setStrictFalse()->validateInput(Validator::RESEND_XPERIENCE_USER_INVITE, $input);

        return $this->resend($inviteId, $input);
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
