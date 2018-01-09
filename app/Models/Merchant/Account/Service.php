<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Notify;
use RZP\Models\Merchant\SlackActions as SlackActions;

class Service extends Merchant\Service
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

        return $account->toArrayPublic();
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
}
