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
}
