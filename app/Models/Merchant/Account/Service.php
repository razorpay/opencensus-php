<?php

namespace RZP\Models\Merchant\Account;

use RZP\Constants\HyperTrace;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Notify;
use RZP\Exception\BadRequestException;
use RZP\Trace\Tracer;
use RZP\Jobs\Transfers\AutoLinkedAccountCreation;

class Service extends Merchant\Service
{
    protected $response;

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
        $this->setAccountCodeIfApplicable($input);

        $accounts = $this->repo->account->fetch($input, $this->merchant->getId());

        return $accounts->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function createLinkedAccount(array $input): array
    {
        $account = $this->core()->createLinkedAccount($input, $this->merchant);

        return $account->toArrayPublic();
    }

    /**
     * Returns the settlement destinations for an account
     *
     * @param string $id
     *
     * @return array
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

        // @todo: log to slack once the bank account update API is ready. Refer Merchant\Service::addBankAccount().
        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $account);

        return $ba->toArrayPublic();
    }

    /**
     * Used for dashboard
     *
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\InvalidArgumentException
     */
    public function listLinkedAccounts(array $input)
    {
        (new Validator)->validateInput('fetch', $input);

        $this->setAccountCodeIfApplicable($input);

        $input[Entity::PARENT_ID] = $this->merchant->getId();

        $accounts = $this->repo->account->fetch($input);

        return $accounts->toArrayPublic();
    }

    public function fetchAccount(string $accountId): array
    {
        $this->core()->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($accountId);

        $account = Tracer::inspan(['name' => HyperTrace::FETCH_ACCOUNTS_CORE], function () use ($accountId) {

            return $this->core()->fetchAccount($accountId);
        });

        return $this->getResponseObject()->generateResponse($account);
    }

    public function fetchAccountByExternalId(string $externalId)
    {
        $account = Tracer::inspan(['name' => HyperTrace::FETCH_ACCOUNTS_BY_EXTERNAL_ID_CORE], function () use ($externalId) {

            return $this->core()->fetchAccountByExternalId($this->merchant, $externalId);
        });

        return $this->getResponseObject()->generateResponse($account);
    }

    public function createAccount(array $input): array
    {
        $account = Tracer::inspan(['name' => HyperTrace::CREATE_ACCOUNTS_CORE], function () use ($input) {

            return $this->core()->createAccount($this->merchant, $input);
        });

        return $this->getResponseObject()->generateResponse($account);
    }

    public function editAccount(string $accountId, array $input): array
    {
        $this->core()->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($accountId);

        $account = Tracer::inspan(['name' => HyperTrace::EDIT_ACCOUNTS_CORE], function () use ($accountId, $input) {

            return $this->core()->editAccount($this->merchant, $accountId, $input);
        });

        return $this->getResponseObject()->generateResponse($account);
    }

    public function listAccounts(array $input): array
    {
        $accounts = Tracer::inspan(['name' => HyperTrace::LIST_ACCOUNTS_CORE], function () use ($input) {

            return $this->core()->listAccounts($this->merchant, $input);
        });

        return $accounts->map(function($account) {
            return $this->getResponseObject()->generateResponse($account);
        })->all();
    }

    public function performAction(string $accountId, string $action): array
    {
        $this->core()->validatePartnerAccess($this->merchant, $accountId);

        $input = [
            Merchant\Entity::ACTION => Action::validateInputAndGetAccountAction($action),
        ];

        $this->trace->info(
            TraceCode::ACCOUNT_EDIT_ACTION,
            [
                'account_id' => $accountId,
                'input'      => $input,
            ]);

        Entity::verifyIdAndStripSign($accountId);

        $account = $this->repo->merchant->findOrFail($accountId);

        $account = $this->core()->action($account, $input, false);

        return $this->getResponseObject()->generateResponse($account);
    }

    protected function getResponseObject()
    {
        if ($this->response === null)
        {
            return new Response;
        }

        return $this->response;
    }

    protected function setAccountCodeIfApplicable(array & $input)
    {
        if (isset($input[Entity::CODE]) === false)
        {
            return;
        }

        $this->core()->checkRouteCodeFeature($this->merchant);

        $input[Entity::ACCOUNT_CODE] = $input[Entity::CODE];

        unset($input[Entity::CODE]);
    }

    public function fetchLinkedAccountsForMerchant(array $input, string $merchantId)
    {
        $input[Entity::PARENT_ID] = $merchantId;

        $linkedAccounts = $this->repo->account->fetch($input);

        return [
            'linked_account_ids' => $linkedAccounts->getIds()
        ];
    }

    public function createAMCLinkedAccountViaAdmin(array $input)
    {
        $this->trace->info(TraceCode::AMC_LINKED_ACCOUNT_CREATION_INITIATED_VIA_ADMIN);

        (new Validator())->validateInput('createAMCLinkedAccountViaAdmin', $input);

        $merchantIds = array_get($input, Constants::MERCHANT_IDS);

        return $this->core()->createAMCLinkedAccountViaAdmin($merchantIds);
    }
}
