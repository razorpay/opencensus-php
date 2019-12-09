<?php

namespace RZP\Models\Card;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function fetchById(string $id)
    {
        /** @var Entity $card */
        $card = $this->repo->card->findByPublicIdAndMerchant($id, $this->merchant);

        return $card->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        /** @var Base\PublicCollection $cards */
        $cards = $this->repo->card->fetch($input, $this->merchant->getId());

        return $cards->toArrayPublic();
    }

    public function updateSavedCards()
    {
        $data = $this->repo->card->updateSavedCardsWithIins();

        $this->trace->info(TraceCode::SAVED_CARDS_UPDATED_WITH_IIN, $data);

        return $data;
    }

    public function getCardRecurring(array $input)
    {
        (new Card\Validator)->validateInput('recurring', $input);

        $iin = $input[Entity::IIN];

        /** @var IIN\Entity|null $iinEntity */
        $iinEntity = $this->repo->iin->find($iin);

        $responseKey = 'recurring';

        if ($iinEntity === null)
        {
            return [$responseKey => false];
        }

        $recurring = (new Card\Entity)->isRecurringSupportedOnIIN(
                                            $this->merchant,
                                            $iinEntity);

        return [$responseKey => $recurring];
    }

    public function migtateCardVaultToken($cardId, $bulkUpdate = false)
    {
        $card = $this->repo->card->find($cardId);

        if ($bulkUpdate == true)
        {
            $this->updateVaultTokenBulk($card);
            return;
        }

        $this->updateVaultToken($card);

        if ($card->hasGlobalCard() === true)
        {
            $this->updateVaultToken($card->globalCard);
        }
    }

    public function updateVaultTokenBulk(Entity $card)
    {
        $cardVault = new CardVault;

        $token = $card->getVaultToken();
        $vault = $card->getVault();

        if (($token === null) or ($vault !== Vault::RZP_VAULT))
        {
            return;
        }
        elseif (starts_with($token, "pay_"))
        {
            #Reset Invalid tokens to null.
            $this->repo->card->resetCardVaultToken($token);
        }
        else
        {
            $vaultResponse = $cardVault->getVaultTokenFromTempToken($token);

            $vaultToken = $vaultResponse['token'];

            $fingerprint = $vaultResponse['fingerprint'];

            $this->repo->card->migrateCardVaultTokenBulk($token, $vaultToken, $fingerprint);
        }
    }

    public function updateVaultToken(Entity $card)
    {
        $cardVault = new CardVault;

        $token = $card->getVaultToken();
        $vault = $card->getVault();

        if (($token === null) or
            ($vault !== Vault::RZP_ENCRYPTION))
        {
            return;
        }

        $vaultResponse = $cardVault->getVaultTokenFromTempToken($token);

        $vaultToken = $vaultResponse['token'];

        $fingerprint = $vaultResponse['fingerprint'];

        $card->setVaultToken($vaultToken);

        $card->setVault(Vault::RZP_VAULT);

        $card->setGlobalFingerPrint($fingerprint);

        $this->repo->saveOrFail($card);

        //deleting data from cache.
        $cardVault->deleteToken($token);
    }
}
