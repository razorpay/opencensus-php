<?php

namespace RZP\Models\Card;

use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use RZP\Jobs\ParAsyncTokenisationJob;
use RZP\Models\Merchant;


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

    public function migtateCardVaultToken($cardId, $bulkUpdate = false, $gateway = null)
    {
        $card = null;

        try
        {
            $card = $this->repo->card->findOrFail($cardId);
        }
        catch (\Throwable $exception) {}

        if ($bulkUpdate == true)
        {
            $this->updateVaultTokenBulk($card, $gateway);
            return;
        }

        $this->updateVaultToken($card, $gateway);

        if ($card->hasGlobalCard() === true)
        {
            $this->updateVaultToken($card->globalCard, $gateway);
        }
    }

    public function updateVaultTokenBulk(Entity $card, $gateway = null)
    {
        $cardVault = new CardVault;

        $token = $card->getVaultToken();
        $vault = $card->getVault();

        if (($token === null) or ($vault !== Vault::RZP_VAULT))
        {
            return;
        }
        else
        {
            $vaultResponse = $cardVault->getVaultTokenFromTempToken($token, $card->toArray(), $gateway);

            $vaultToken = $vaultResponse['token'];

            $fingerprint = $vaultResponse['fingerprint'];

            $this->repo->card->migrateCardVaultTokenBulk($token, $vaultToken, $fingerprint);
        }
    }

    public function updateVaultToken(Entity $card, $gateway=null)
    {
        $cardVault = new CardVault;

        $token = $card->getVaultToken();
        $vault = $card->getVault();

        if (($token === null) or ($vault !== Vault::RZP_ENCRYPTION))
        {
            return;
        }

        $vaultResponse = $cardVault->getVaultTokenFromTempToken($token, $card->toArray(), $gateway);

        $vaultToken = $vaultResponse['token'];

        $fingerprint = $vaultResponse['fingerprint'];

        $card->setVaultToken($vaultToken);

        $card->setVault(Vault::RZP_VAULT);

        $card->setGlobalFingerPrint($fingerprint);

        (new Card\Core())->saveParValue($card, null);

        $this->repo->saveOrFail($card);

        // not calling delete api in case of paysecure
        if ((isset($gateway) === true) and ($gateway === 'paysecure')){
            return ;
        }

        //deleting data from cache.
        $cardVault->deleteToken($token);
    }
}
