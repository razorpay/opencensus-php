<?php

namespace RZP\Models\Invitation;

use RZP\Models\Base;
use RZP\Models\Invitation\Entity;
use Lib\PhoneBook;

class Repository extends Base\Repository
{
    protected $entity = 'invitation';

    public function fetchByToken($token)
    {
    	return $this->newQuery()
                    ->where(Entity::TOKEN, $token)
                    ->firstOrFailPublic();
    }

    public function findByIdAndEmail(string $id, string $email)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->where(Entity::EMAIL, $email)
                    ->firstOrFailPublic();
    }

    public function findByIdAndEmailNullable(string $id, string $email)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->where(Entity::EMAIL, $email)
                    ->first();
    }

    public function updateIsDraftToFalse($inviteIdArray)
    {
       return $this->newQuery()
            ->wherein(Entity::ID, $inviteIdArray)
            ->update([
                Entity::IS_DRAFT => 0,
            ]);
    }

    public function fetchInvitations(string $product, string $merchantId): array
    {
        return $this->newQuery()
            ->where(Entity::PRODUCT, $product)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::IS_DRAFT, 0)
            ->orwhere(Entity::IS_DRAFT, null)
            ->get()->callOnEveryItem('toArrayPublic');
    }

    public function getInvitationToken(string $product, string $merchantId, string $email): Entity
    {
        return $this->newQuery()
            ->where(Entity::PRODUCT, $product)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::EMAIL, $email)
            ->firstOrFailPublic();
    }

    public function listDraftInvitations(string $product, string $merchantId): array
    {
        return $this->newQuery()
            ->where(Entity::PRODUCT, $product)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::IS_DRAFT, 1)
            ->get()->callOnEveryItem('toArrayPublic');
    }

    public function findByIdAndContactMobile(string $id, string $rawContactMobile)
    {

        $validContactMobileFormats = (new PhoneBook($rawContactMobile, true))->getMobileNumberFormats();
        // Add unformatted contact to the query
        array_unshift($validContactMobileFormats, $rawContactMobile);

        return $this->newQuery()
            ->where(Entity::ID, $id)
            ->whereIn(Entity::CONTACT_MOBILE, $validContactMobileFormats)
            ->firstOrFailPublic();
    }

    public function getInvitationById($id)
    {
        return $this->newQuery()
            ->where(Entity::ID, $id)
            ->first();
    }

    public function getInvitationsForMerchantId($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->get()
            ->toArray();
    }

    public function updateInvitation($invitation)
    {
        $this->newQuery()
            ->where(Entity::ID, array_get($invitation, Entity::ID))
            ->update([
                Entity::PRODUCT => array_get($invitation, Entity::PRODUCT),
                Entity::EMAIL => array_get($invitation, Entity::EMAIL),
                Entity::ROLE => array_get($invitation, Entity::ROLE),
                Entity::IS_DRAFT => array_get($invitation, Entity::IS_DRAFT),
                Entity::METADATA => array_get($invitation, Entity::METADATA),
                Entity::CONTACT_MOBILE => array_get($invitation, Entity::CONTACT_MOBILE),
            ]);

        $invitation = $this->newQuery()
            ->where(Entity::ID, array_get($invitation, Entity::ID))
            ->first();

        $invitation->id = intval($invitation->id);

        return $invitation;
    }

    public function createInvitation($invitation)
    {
        $invitation =  $this->create([
            Entity::PRODUCT => array_get($invitation, Entity::PRODUCT),
            Entity::EMAIL => array_get($invitation, Entity::EMAIL),
            Entity::ROLE => array_get($invitation, Entity::ROLE),
            Entity::IS_DRAFT => array_get($invitation, Entity::IS_DRAFT),
            Entity::METADATA => array_get($invitation, Entity::METADATA),
            Entity::CONTACT_MOBILE => array_get($invitation, Entity::CONTACT_MOBILE),
            Entity::TOKEN => array_get($invitation, Entity::TOKEN),
            Entity::USER_ID => array_get($invitation, Entity::USER_ID),
            Entity::MERCHANT_ID => array_get($invitation, Entity::MERCHANT_ID),
        ]);

        $invitation->id = intval($invitation->id);

        return $invitation;
    }

    public function deleteInvitationById($id)
    {
        return $this->newQuery()
            ->where(Entity::ID, $id)
            ->delete();
    }
}
