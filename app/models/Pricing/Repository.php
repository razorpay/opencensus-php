<?php

namespace Models\Pricing;

use Models\Base;
use Models\Pricing;
use EE\Exception;
use EE\Error\ErrorCode;

class Repository extends Base\Repository
{
    protected $entity = 'Pricing';

    public function getPricingPlanById($id)
    {
        $repo = $this->repo;

        return $repo::where(Pricing\Entity::PLAN_ID, '=', $id)
                     ->orderBy(Pricing\Entity::PLAN_ID, 'desc')
                     ->orderBy(Pricing\Entity::ID, 'desc')
                     ->get();
    }

    public function getPricingPlanByIdOrFailPublic($id)
    {
        $plan = $this->getPricingPlanById($id);

        if ($plan->count() === 0)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return $plan;
    }

    public function getPricingPlanByMerchantAndPaymentNetworks($merchantId, array $networks = array())
    {
        $repo = $this->repo;

        return $repo::where(Pricing\Entity::MERCHANT_ID, '=', $merchantId)
                    ->whereIn(Pricing\Entity::PAYMENT_NETWORK, $networks);
    }

    public function getPricingPlans()
    {
        $repo = $this->repo;

        return $repo::orderBy(Pricing\Entity::ID, 'desc')->take(10)->get();
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