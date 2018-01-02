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
     * @param  string       $accountId
     * @return array
     */
    public function fetch(string $accountId) : array
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($accountId, $this->merchant);
        
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

    /**
     * @param array $input
     *
     * @return array
     */
    public function create(array $input): array
    {
        $account = $this->core->createAccount($input, $this->merchant);

        return $account->toArrayPublic();
    }

    /**
     * Returns the settlement destinations for an account
     *
     * @param string $accountId
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fetchSettlementDestinations(string $accountId) : array
    {
        $merchant = $this->repo->account->findByPublicIdAndMerchant($accountId, $this->merchant);

        # Fetch all settlement destinations, not only the bank accounts
        $bankAccounts = $this->repo->bank_account->getAllBankAccounts($merchant);

        $bankAccounts = $bankAccounts->toArrayPublic();

        if (count($bankAccounts) === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        return $bankAccounts;
    }

    /**
     * Adds a new settlement destination - Bank account
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function postBankAccounts(string $id, array $input) : array
    {
        $merchant = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $merchant);

        $this->logActionToSlack($merchant, SlackActions::EDIT_BANK_DETAILS, $input);

        return $ba->toArrayPublic();
    }
}
