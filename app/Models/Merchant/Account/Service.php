<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Notify;
use RZP\Models\Merchant\SlackActions as SlackActions;

class Service extends Base\Service
{
    use Notify;

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
        $account = $this->core()->createAccount($input, $this->merchant);

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
        $account = $this->repo->account->findByPublicIdAndMerchant($accountId, $this->merchant);

        // Fetch all settlement destinations, not only the bank accounts
        $bankAccounts = $this->repo->bank_account->getAllBankAccounts($account);

        $bankAccounts = $bankAccounts->toArrayPublic();

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
    public function postBankAccount(string $id, array $input) : array
    {
        $account = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $account);

        $this->logActionToSlack($account, SlackActions::EDIT_BANK_DETAILS, $input);

        return $ba->toArrayPublic();
    }
}
