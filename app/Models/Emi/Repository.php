<?php

namespace RZP\Models\Emi;

use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Card\IIN;
use RZP\Models\Card\Network;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Card\CobrandingPartner;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'emi_plan';

    protected $appFetchParamRules = array(
        Entity::BANK               => 'sometimes|string|size:4',
        Entity::NETWORK            => 'sometimes|string|max:12',
        Entity::COBRANDING_PARTNER => 'sometimes|string',
    );

    private function fetchEmiPlansFromCardPaymentsService(string $merchantId): PublicCollection
    {
        $input = [
            Entity::MERCHANT_ID => $merchantId
        ];

        $plans = (new Migration)->handleMigration(Migration::QUERY, null, '', $input);

        // If the CPS call fails or if CPS returns empty plans
        if (empty($plans) === true)
        {
            return new PublicCollection;
        }

        return (new Migration)->getEntityList($plans[Migration::EMI_PLANS]);
    }

    public function fetchEmiPlanByMerchantId(string $merchantId): PublicCollection
    {
        $emiPlans = new PublicCollection();

        // If CPS fetch enabled, fetch from CPS
        if ((new Migration)->isCpsFetchEnabled() == true)
        {
            $emiPlans = $this->fetchEmiPlansFromCardPaymentsService($merchantId);
        }

        // If CPS fetch disabled or CPS fetch failed, fetch from db
        if ($emiPlans->isEmpty() === true)
        {
            $emiPlans = $this->newQuery()
                ->where([
                    [Entity::MERCHANT_ID, '=', $merchantId],
                ])->get();
        }

        return $emiPlans;
    }

    public function fetchRelevantMerchantEmiPlan(IIN\Entity $iin, int $duration, $merchant, $type = null)
    {
        $bank = $iin->getIssuer();

        $network = $iin->getNetworkCode();

        $cobrandingPartner = $iin->getCobrandingPartner();

        $query = $this->newQuery()
                      ->where(Entity::DURATION, '=', $duration);

        $cpsQuery = [
            Entity::DURATION => $duration,
        ];

        if ($cobrandingPartner === CobrandingPartner::ONECARD)
        {
            $query->where(Entity::COBRANDING_PARTNER, '=', $cobrandingPartner);

            $cpsQuery[Entity::COBRANDING_PARTNER] = $cobrandingPartner;
        }
        else if ($bank)
        {
            $query->where(Entity::BANK, '=', $bank);

            $cpsQuery[Entity::BANK] = $bank;
        }
        else if (($network === Network::AMEX) or ($network === Network::BAJAJ))
        {
            $query->where(Entity::NETWORK, '=', $network);

            $cpsQuery[Entity::NETWORK] = $network;
        }

        if ($type !== null)
        {
            $query->where(Entity::TYPE, '=', $type);

            $cpsQuery[Entity::TYPE] = $type;
        }

        $merchantIds = [$merchant->getId(), Account::SHARED_ACCOUNT];

        $query->whereIn(Entity::MERCHANT_ID, $merchantIds);

        $cpsQuery[Migration::MERCHANT_IDS] = $merchantIds;

        $cpsResp = (new Migration) -> handleMigration(Migration::EMI_QUERY, null, '', $cpsQuery);

        if ($cpsResp != null)
        {
            return (new Migration)->getEntityList($cpsResp[Migration::EMI_PLANS]);
        }

        return $query->get();
    }


    /**
     *Description:- Method to fetch emi plans based on durations, bank, network, type
     *
     * @param array $durations
     * @param string|null $bank
     * @param string|null $network
     * @param string|null $type | payment method type (credit, debit)
     *
     * final query if everything is not null:-
     * select * from emil_plans where duration in($durations) and bank = $bank
     * and network = $network and type = $type
     *
     * @return list of emi_plans
     */

    public function fetchByParams(array $durations = [], string $bank = null, string $network = null, string $type = null)
    {
        $cpsQuery = [];

        $query = $this->newQuery();

        if (empty($durations) === false)
        {
            $query->whereIn(Entity::DURATION, $durations);

            $cpsQuery[Migration::DURATIONS] = $durations;
        }

        if (empty($bank) === false)
        {
            $query->where(Entity::BANK, $bank);

            $cpsQuery[Entity::BANK] = $bank;
        }

        if (empty($network) === false)
        {
            $query->where(Entity::NETWORK, $network);

            $cpsQuery[Entity::NETWORK] = $network;
        }

        if (empty($type) === false)
        {
            $query->where(Entity::TYPE, $type);

            $cpsQuery[Entity::TYPE] = $type;
        }

        $cpsResp = (new Migration) -> handleMigration(Migration::EMI_QUERY, null, '', $cpsQuery);

        if ($cpsResp != null)
        {
            return (new Migration)->getEntityList($cpsResp[Migration::EMI_PLANS]);
        }

        return $query->get();
    }

    public function fetchDurationsByMerchantAndIssuer(string $merchantId, string $issuer)
    {
        $bank = IFSC::getIssuingBank($issuer);

        $cpsData = (new Migration)->handleMigration(Migration::EMI_QUERY,null, '', [
            Migration::MERCHANT_IDS => [$merchantId, Account::SHARED_ACCOUNT],
            Entity::BANK            => $bank,
        ]);

        if($cpsData != null)
        {
            return (new Migration)->getDurationsArray($cpsData[Migration::EMI_PLANS]);
        }

        return $this->newQuery()
            ->select(Entity::DURATION)
            ->whereIn(Entity::MERCHANT_ID, [$merchantId, Account::SHARED_ACCOUNT])
            ->where(Entity::BANK, '=', $bank)
            ->pluck(Entity::DURATION)
            ->all();
    }

    public function fetchDurationsByMerchantAndNetwork(string $merchantId, string $paymentNetwork)
    {
        $cpsData = (new Migration)->handleMigration(Migration::EMI_QUERY,null, '', [
            Migration::MERCHANT_IDS => [$merchantId, Account::SHARED_ACCOUNT],
            Entity::NETWORK         => $paymentNetwork,
        ]);

        if($cpsData != null)
        {
            return (new Migration)->getDurationsArray($cpsData[Migration::EMI_PLANS]);
        }

        return $this->newQuery()
            ->select(Entity::DURATION)
            ->whereIn(Entity::MERCHANT_ID, [$merchantId, Account::SHARED_ACCOUNT])
            ->where(Entity::NETWORK, '=', $paymentNetwork)
            ->pluck(Entity::DURATION)
            ->all();
    }

    public function fetchAllLivePlans()
    {
        return $this->newQuery()
            ->get();
    }

    //Tries to filter plans based on params. If no plans are found empty collection
    //is returned.
    public function handleFetch($params)
    {
        $cpsData = (new Migration)->handleMigration(Migration::QUERY,null, '', $params);

        if($cpsData != null)
        {
            return (new Migration)->getEntityList($cpsData[Migration::EMI_PLANS]);
        }

        return $this->fetch($params);
    }

    //Tries to find an emi plan based on the given id. If no plan is found it returns
    //DB query exception.
    public function handleFindOrFail($id)
    {
        if ((new Migration)->isCpsFetchEnabled())
        {
            $cpsData = (new Migration)->migrationRequestHandler(Migration::FETCH,null, $id, null, true);

            if($cpsData == null)
            {
                $e = array(
                    'model' => 'emi_plans',
                    'operation' => 'find',
                    'attributes' => [
                        'id' => $id,
                    ]
                );

                $this->throwException($e);
            }

            return (new Migration)->getEntity($cpsData);
        }

        return $this->findOrFail($id);
    }

    //Tries to find and emi plans based on the given id. If not plan is found it returns
    //BAD_REQUEST_INVALID_ID exception.
    public function handleFindOrFailPublic($id)
    {
        if ((new Migration)->isCpsFetchEnabled())
        {
            $cpsData = (new Migration)->migrationRequestHandler(Migration::FETCH,null, $id, null, true);

            if($cpsData == null)
            {
                $e = array(
                    'model' => 'emi_plans',
                    'attributes' => $id,
                    'operation' => 'find');

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID, null, $e);
            }

            return (new Migration)->getEntity($cpsData);
        }

        return $this->findOrFailPublic($id);
    }
 }
