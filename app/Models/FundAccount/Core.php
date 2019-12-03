<?php

namespace RZP\Models\FundAccount;

use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Batch;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Exception\LogicException;
use RZP\Services\FTS\Constants;
use RZP\Services\FTS\CreateAccount;

/**
 * Class Core
 *
 * @package RZP\Models\FundAccount
 */
class Core extends Base\Core
{
    /**
     * Here, source is null for entity of type fund account validation
     * @param array                  $input
     * @param Merchant\Entity        $merchant
     * @param Base\PublicEntity|null $source
     * @param Batch\Entity|null      $batch
     *
     * @return Entity
     */
    public function create(array $input,
                           Merchant\Entity $merchant,
                           Base\PublicEntity $source = null,
                           Batch\Entity $batch = null,
                           string $batchId = null): Entity
    {
        if (isset($input[Entity::IDEMPOTENCY_KEY]) === true)
        {
            $result = $this->repo->fund_account->fetchByIdempotentKey($input[Entity::IDEMPOTENCY_KEY],
                                                                      $merchant->getId(),
                                                                      $batchId);

            if ($result !== null)
            {
                return $result;
            }
        }

        $fundAccount = (new Entity);

        // This needs to be done before the build since validator
        // uses the merchant association to check for a feature.
        $fundAccount->merchant()->associate($merchant);

        $fundAccount = $fundAccount->build($input);

        $this->repo->transaction(
            function() use ($input, $merchant, $source, $fundAccount, $batch, $batchId)
            {
                $account = $this->createAccount($input, $merchant, $source);

                $fundAccount->source()->associate($source);

                $fundAccount->account()->associate($account);

                $batchId ? ($fundAccount->setBatchId($batchId)) : ($fundAccount->batch()->associate($batch));

                $this->repo->saveOrFail($fundAccount);
            });

        try
        {
            if ($source !== null)
            {
                $account = $fundAccount->account;

                (new CreateAccount($this->app))->callFtsCreateAccount($account, Constants::PAYOUT);
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::FTS_CREATE_ACCOUNT_FAILED,
                [
                    'data' => $input,
                ]);
        }

        return $fundAccount;
    }

    /**
     * We were accepting the account details object in the `details` key, and then changed to accept this in a
     * key with a name corresponding to the account_type -> `bank_account` or `vpa`.
     *
     * This function handles this backward compatibilty modification of the request.
     *
     * UPDATE : We are deprecating `details` in all fund_account API requests. This function
     * is now used by fund_account_validation so that there is no change in the fund_account_validation APIs
     *
     * Consumers can send the details in only `bank_account`|`vpa`
     * Internally, the fund_account_validation API can still send details in `bank_account`|`vpa`|`details`
     *
     * @param array $input
     */
    public function modifyRequestForBackwardCompatibility(array & $input)
    {
        //
        // If the `details` key is unset, we assume the details are present in the new structure
        // under `bank_account` or `vpa`
        //
        if (isset($input[Entity::DETAILS]) === false)
        {
            return;
        }

        //
        // `account_type` is a required field, so if unset we just return
        // and let this fail at the Entity build validation stage.
        //
        if (isset($input[Entity::ACCOUNT_TYPE]) === false)
        {
            return;
        }

        $accountType = $input[Entity::ACCOUNT_TYPE];

        $input[$accountType] = $input[Entity::DETAILS];

        unset($input[Entity::DETAILS]);
    }

    protected function createAccount(array $input,
                                     Merchant\Entity $merchant,
                                     Base\PublicEntity $source = null): Base\PublicEntity
    {
        $accountType = $input[Entity::ACCOUNT_TYPE];

        $accountInput = $input[$accountType];

        $account = null;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $account = (new BankAccount\Core)->createBankAccountForFundAccount($accountInput, $merchant, $source);
                break;

            case Type::VPA:
                $account = (new Vpa\Core)->createForSource($accountInput, $source);
                break;

            case Type::CARD:
                // Card number is validated as part of fund_account create validator itself.
                $network = Card\Network::detectNetwork(substr($accountInput[Card\Entity::NUMBER], 0, 6));

                // cvv needs to be passed otherwise card creation will fail if it's
                // not present, hence passing a dummy value. It's not stored anyways.
                $accountInput[Card\Entity::CVV] = $accountInput[Card\Entity::CVV] ?? Card\Entity::getDummyCvv($network);

                // If the expiry is sent, we use that to validate and such.
                // If the expiry is not sent, we use a dummy expiry.
                // We do not expose expiry in either way.
                // Expiry is mandatory for card creation.
                $accountInput[Card\Entity::EXPIRY_MONTH] = $accountInput[Card\Entity::EXPIRY_MONTH] ?? Card\Entity::DUMMY_EXPIRY_MONTH;
                $accountInput[Card\Entity::EXPIRY_YEAR] = $accountInput[Card\Entity::EXPIRY_YEAR] ?? Card\Entity::DUMMY_EXPIRY_YEAR;

                // If name is sent, we use that. We also expose it.
                // If name is not sent, we use a dummy name. We do not expose it.
                // Name is mandatory for card creation.
                $accountInput[Card\Entity::NAME] = $accountInput[Card\Entity::NAME] ?? Card\Entity::DUMMY_NAME;

                $account = (new Card\Core)->createForFundAccount($accountInput, $merchant);
                break;

            default:
                throw new LogicException('Creation logic not defined for fund account type: ' . $accountType);
        }

        return $account;
    }

    public function update(Entity $fundAccount, array $input): Entity
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_UPDATE_REQUEST,
            [
                'id'     => $fundAccount->getId(),
                'entity' => $fundAccount->toArray(),
                'input'  => $input,
            ]);

        $fundAccount->edit($input);

        $this->repo->saveOrFail($fundAccount);

        return $fundAccount;
    }

    public function delete(Entity $fundAccount)
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_DELETE_REQUEST, ['id' => $fundAccount->getId()]);

        return $this->repo->deleteOrFail($fundAccount);
    }

    /**
     * @param string $id
     * @param Merchant\Entity $merchant
     * @return Entity
     */
    public function findByPublicIdAndMerchant(string $id, Merchant\Entity $merchant): Entity
    {
        return $this->repo->fund_account->findByPublicIdAndMerchant($id, $merchant);
    }
}
