<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\SlackActions as SlackActions;

class Service extends Merchant\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /**
     * Retrieve a single account entity
     *
     * @param  string       $id
     * @return array
     */
    public function fetch(string $id) : array
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);
        
        return $account->toArrayPublic();
    }

    /**
     * Retrieve a collection of accounts
     *
     * @param  array        $input
     * @return array
     */
    public function fetchMultiple($input) : array
    {
        $accounts = $this->repo->account->fetch($input, $this->merchant->getId());

        return $accounts->toArrayPublic();
    }

    public function create(array $input): array
    {
        $merchant = $this->core->createAccount($input, $this->merchant);

        return $merchant->toArrayPublic();
    }

    /**
     * Returns the settlement destinations for an account
     *
     * @param string $id
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function getSettlementDestinations(string $id) : array
    {
        $merchant = $this->repo->account->findOrFailPublic($id);

        # Fetch all settlement destinations, not only the bank accounts
        $bankAccounts = $this->repo->bank_account->getAllBankAccounts($merchant);

        if (count($bankAccounts) === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        return $bankAccounts;
    }

    /**
     * Adds a new settlement destination
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function addSettlementDestination(string $id, array $input) : array
    {
        $merchant = $this->repo->account->findOrFailPublic($id);

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $merchant);

        $this->logActionToSlack($merchant, SlackActions::EDIT_BANK_DETAILS, $input);

        return $ba->toArray();
    }
}
