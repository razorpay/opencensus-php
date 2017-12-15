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
     * @param  mixed        $input
     * @return array
     */
    public function fetchMultiple($input) : array
    {
        $accounts = $this->repo->account->fetch($input, $this->merchant->getId());

        return $accounts->toArrayPublic();
    }

    public function create(array $input)
    {
        $merchant = $this->core->createAccount($input, $this->merchant);

        return $merchant->toArrayPublic();
    }

    public function edit(string $id, array $input)
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        $this->setSettlementScheduleIdIfNeeded($account, $input);

        $account = $this->core->edit($account, $input);

        return $account->toArrayPublic();
    }

    /**
     * Upload activation files for an account
     *
     * @param  string       $id
     * @param  mixed        $input
     * @return array
     */
    public function uploadFiles(string $id, $input) : array
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        $this->core->uploadFiles($account, $input);

        return [
            'success'  => true
        ];
    }

    /**
     * Add or edit account details
     *
     * @param  string       $id
     * @param  array        $input
     * @return array
     */
    public function updateDetails(string $id, array $input) : array
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        $accountDetails = $this->core->updateDetails($account, $input);

        return $accountDetails;
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
