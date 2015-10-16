<?php

namespace Models\Pricing;

use Models\Base;
use Models\Payment;
use Models\Pricing;
use EE\Exception;
use EE\Error\ErrorCode;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'Pricing';

    public function getPricingPlanById($id, $fail = false, $public = false)
    {
        $repo = $this->repo;

        $pricing = $repo::where(Pricing\Entity::PLAN_ID, '=', $id)
                     ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                     ->orderBy(Pricing\Entity::ID, 'desc')
                     ->get();

        if (($pricing->count() === 0) and
            ($fail))
        {
            if ($public)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID);
            }
            else
            {
                throw new Exception\LogicException(
                    'No pricing plan found for id: ' . $id);
            }
        }

        return $pricing;
    }

    public function getPricingPlanByIdOrFailPublic($id)
    {
        return $this->getPricingPlanById($id, true, true);
    }

    public function getPricingRulesForGivenCardNetwork($id, $network)
    {
        $repo = $this->repo;

        // cannot use laravel's whereIn here because it doesn't give correct result with 'null'
        return $repo::where(Pricing\Entity::PLAN_ID, '=', $id)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', Payment\Method::CARD)
                    ->where(function($query) use ($network)
                    {
                        $query->where(Pricing\Entity::PAYMENT_NETWORK, '=', null)
                              ->orWhere(Pricing\Entity::PAYMENT_NETWORK, '=', $network);
                    })
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getPricingRulesForNetBanking($id)
    {
        $repo = $this->repo;

        return $repo::where(Pricing\Entity::PLAN_ID, '=', $id)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', Payment\Method::NETBANKING)
                    ->get();
    }

    public function getPricingRulesForWallet($id)
    {
        $repo = $this->repo;

        return $repo::where(Pricing\Entity::PLAN_ID, '=', $id)
                    ->where(Pricing\Entity::PAYMENT_METHOD, '=', Payment\Method::WALLET)
                    ->get();
    }

    public function getPricingPlans()
    {
        $repo = $this->repo;

        return $repo::orderBy(Pricing\Entity::ID, 'desc')->get();
    }

    public function getMerchantPricingPlans()
    {
        $repo = $this->repo;

        // For merchant pricing plans, gateway will not be specified
        return $repo::whereNull(Pricing\Entity::GATEWAY)
                    ->orderBy(Pricing\Entity::ID, 'desc')->take(10)->get();
    }

    public function getGatewayPricingPlans()
    {
        $repo = $this->repo;

        return $repo::whereNotNull(Pricing\Entity::GATEWAY)
                    ->orderBy(Pricing\Entity::ID, 'desc')->take(10)->get();
    }

    public function getPricingPlanByName($name)
    {
        $repo = $this->repo;

        return $repo::where(Pricing\Entity::PLAN_NAME, '=', $name)
                    ->orderBy(Pricing\Entity::ID, 'desc')
                    ->get();
    }

    public function getPricingPlanRule($id)
    {
        $repo = $this->repo;

        $repo::findOrFailPublic($id);
    }

    public function deletePlanRule($id)
    {
        $repo = $this->repo;

        $repo::delete($id);
    }
}