<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Notify;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Merchant\SlackActions as SlackActions;

class Service extends Base\Service
{
    use Notify;

    /**
     * Retrieve a single account entity
     *
     * @param  string       $id
     * @return array
     */
    public function fetch(string $id): array
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        return $this->toArrayPublic($account);
    }

    /**
     * Retrieve a collection of accounts
     *
     * @param  array        $input
     * @return array
     */
    public function fetchMultiple(array $input): array
    {
        $accounts = $this->repo->account->fetch($input, $this->merchant->getId());

        return $this->toArrayPublic($accounts);
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function create(array $input): array
    {
        $account = $this->core()->createAccount($input, $this->merchant);

        return $this->toArrayPublic($account);
    }

    /**
     * Returns the settlement destinations for an account
     *
     * @param string $id
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fetchSettlementDestinations(string $id): array
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        // Fetch all settlement destinations, not only the bank accounts
        $bankAccounts = $this->repo->bank_account->getAllBankAccounts($account);

        $bankAccounts = $bankAccounts->toArrayPublic();

        return $bankAccounts;
    }

    /**
     * Adds / updates a new settlement destination - Bank account
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function createOrChangeBankAccount(string $id, array $input): array
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $account);

        $this->logActionToSlack($account, SlackActions::EDIT_BANK_DETAILS, $input);

        return $ba->toArrayPublic();
    }

    /**
     * Few fields like can_submit, fields_required are dynamically computed in the Core class.
     * To avoid calling Core's function from Entity's public setters, the response received from
     * toArrayPublic method (defined in the Base/PublicCollection) is not used here directly.
     *
     * @param $entity
     *
     * @return array
     */
    protected function toArrayPublic($entity): array
    {
        if (($entity instanceof PublicCollection) === true)
        {
            $items = $this->itemsToArrayPublic($entity);

            $array[PublicCollection::ENTITY] = 'collection';
            $array[PublicCollection::COUNT]  = count($items);
            $array[PublicCollection::ITEMS]  = $items;

            return $array;
        }

        $response = $entity->toArrayPublic();

        $response = $this->getCustomPublicAttributes($entity, $response);

        return $response;
    }

    /**
     * Applies toArrayPublic() function over a PublicCollection
     *
     * @param PublicCollection $entities
     *
     * @return array
     */
    protected function itemsToArrayPublic(PublicCollection $entities): array
    {
        $response = [];

        foreach ($entities as $entity)
        {
            $response[] = $this->toArrayPublic($entity);
        }

        return $response;
    }

    /**
     * Returns custom public attributes.
     * This cannot be handled in the Entity class as some of the params are
     * computed on the fly, using the Core class.
     *
     * @param Entity $entity
     * @param        $response
     *
     * @return array
     */
    protected function getCustomPublicAttributes(Entity $entity, array & $response): array
    {
        $merchantDetails = $entity->merchantDetail;

        $detailsResponse = (new MerchantDetail\Core)->createResponse($merchantDetails);

        $activationDetails = Entity::ACTIVATION_DETAILS;

        $response[$activationDetails][Entity::CAN_SUBMIT]      = $detailsResponse[MerchantDetail\Entity::CAN_SUBMIT];

        $verificationDetails = $detailsResponse[MerchantDetail\Entity::VERIFICATION];

        $requiredFields = [];

        if (isset($verificationDetails[MerchantDetail\Entity::REQUIRED_FIELDS]) === true)
        {
            $requiredFields = $verificationDetails[MerchantDetail\Entity::REQUIRED_FIELDS];
        }

        $response[$activationDetails][Entity::REQUIRED_FIELDS] = $requiredFields;

        return $response;
    }
}
