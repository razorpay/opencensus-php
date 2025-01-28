<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Payment\Refund;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\Feature\Constants as Features;

class Service extends Base\Service
{
    public function fetch(string $id, array $input): array
    {
        $txn = null;

        $merchant = $this->app['basicauth']->getMerchant();

        $isReverseShadowMerchant = empty($merchant) === false ? $merchant->isFeatureEnabled(Features::PG_LEDGER_REVERSE_SHADOW) : false;

        if($isReverseShadowMerchant === true
            and $this->app['basicauth']->getAuthType() === AuthType::PROXY_AUTH)
        {
            if((empty($input) === false) and (isset($input[Base\Repository::EXPAND]) === true)
                and (in_array('transaction.settlement', $input[Base\Repository::EXPAND]) === true))
            {
                array_delete('transaction.settlement', $input['expand']);

                //Doing this as strip sign uses pointer
                $reversalId = $id;

                $txn = $this->repo->transaction->findByEntityIdWithoutMerchantTidb(Entity::stripSignWithoutValidation($reversalId));

                if(empty($txn) === false and empty($txn->getSettlementId()) === false)
                {
                    $settlement = $this->repo->settlement->find($txn->getSettlementId());

                    if (empty($settlement) === false)
                    {
                        $txn['settlement'] = $settlement->toArrayPublic();
                    }
                    else
                    {
                        $txn['settlement'] = null;
                    }
                }
            }
        }

        $reversal = $this->repo
                         ->reversal
                         ->findByPublicIdAndMerchant($id, $this->merchant, $input);

        $response = $reversal->toArrayPublicWithExpand();

        if ($txn != null)
        {
            $response['transaction'] = $txn->toArrayPublicWithExpand();
        }
        else if ($isReverseShadowMerchant === true and $readExp === true)
        {
            $response['transaction'] = null;
        }

        return $response;
    }

    public function fetchMultiple(array $input): array
    {
        $merchantId = $this->merchant->getId();

        $skip = 0;

        $count = 25;

        if(isset($input['skip']))
        {
            $skip = $input['skip'];
        }
        if(isset($input['count']))
        {
            $count = $input['count'];
        }

        $reversals = $this->repo->reversal->fetchreversals($merchantId, $count, $skip);

        return $reversals->toArrayPublic();
    }

    public function fetchLinkedAccountReversal(string $id): array
    {
        (new Merchant\Validator)->validateLinkedAccount($this->merchant);

        $merchantId = $this->merchant->getId();

        $relations = ['reversal'];

        Reversal\Entity::verifyIdAndStripSign($id);

        $refund = $this->repo->refund->findByReversalIdAndMerchant($id, $merchantId, $relations);

        $reversal = $this->createReversalResponseFromRefund($refund);

        return $reversal;
    }

    public function fetchLinkedAccountReversals(array $input): array
    {
        (new Merchant\Validator)->validateLinkedAccount($this->merchant);

        $merchantId = $this->merchant->getId();

        $input['expand'] = ['reversal'];

        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchLinkedAccountReversals',
        ]);

        $refunds = $this->repo->refund->fetch($input, $merchantId);

        $refunds = $this->createReversalsResponse($refunds);

        $reversals = new Base\PublicCollection($refunds);

        $reversals = $reversals->toArrayWithItems();

        return $reversals;
    }

    /**
     * Returns a list of transformed entities for la reversals.
     * @param $refunds
     *
     * @return array
     */
    private function createReversalsResponse($refunds): array
    {
        $reversals = [];

        foreach ($refunds as $refund)
        {
            $reversals[] = $this->createReversalResponseFromRefund($refund);
        }

        return $reversals;
    }

    /**
     * Replaces notes from refund entity to reversal entity.
     * @param $refund
     *
     * @return array
     */
    private function createReversalResponseFromRefund($refund): array
    {
        $refund->loadMissing('reversal');
        $result = $refund->toArrayPublic();

        $reversalData = $result[Refund\Entity::REVERSAL];
        $reversalData[Reversal\Entity::NOTES] = $result[Refund\Entity::NOTES];

        return $reversalData;
    }

    public function createReversalEntryForPayoutService(array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_SERVICE_REVERSAL_CREATE_REQUEST,
            ['input' => $input]);

        $response = (new Core)->createReversalEntryForPayoutService($input);

        return $response;
    }

    public function createReversalViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        (new Core)->createReversalViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
    }

    public function reverseCreditsViaPayoutService(array $input)
    {
        return (new Core)->reverseCreditsViaPayoutService($input);
    }
}
