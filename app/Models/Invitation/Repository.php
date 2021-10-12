<?php

namespace RZP\Models\Invitation;

use RZP\Models\Base;
use RZP\Models\Invitation\Entity;

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

    public function listDraftInvitations(string $product, string $merchantId): array
    {
        return $this->newQuery()
            ->where(Entity::PRODUCT, $product)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::IS_DRAFT, 1)
            ->get()->callOnEveryItem('toArrayPublic');
    }
}
