<?php


namespace RZP\Models\Reward;

use RZP\Models\Base;
use RZP\Models\Offer\EntityOffer\Repository as EntityOfferRepository;
use RZP\Models\Reward\MerchantReward\Validator as MerchantRewardValidator;


class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    /**
     * @param array           $input
     *
     *
     * @return Entity
     */
    public function create(array $input)
    {
        (new Validator())->validateInput('create', $input);

        $reward = $this->core->create($input['reward']);

        $merchantIds = $input['merchant_ids'];

        $success  = 0;
        $failures = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                if ($merchantId === $input['reward']['advertiser_id'])
                {
                    $failures[] = $merchantId;
                }
                else
                {
                    (new MerchantReward\Core())->create($merchantId, $reward->getId());

                    $success +=1 ;
                }
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);

                $failures[] = $merchantId;
            }
        }

        $summary  = [
            'success'  => $success,
            'failures' => $failures
        ];

        return $summary;
    }

    public function activateDeactivateReward(array $input)
    {
        (new MerchantRewardValidator())->validateInput('activate_deactivate', $input);

        return (new MerchantReward\Core())->activateDeactivateRewardByMerchantIdAnRewardId($this->merchant->getId(), $input['reward_id'], $input['activate']);
    }

    public function delete($rewardId)
    {
        $success = [];

        $success['success'] = (new MerchantReward\Core())->deleteRewardByRewardId($rewardId);

        return $success;
    }

    public function fetch()
    {
        return (new Core())->fetchReward($this->merchant->getId());
    }

    public function getRewardTerms($id, $paymentId)
    {

        $entityOffer = (new EntityOfferRepository())->findByEntityIdAndOfferIdAndType($paymentId, $id);

        if (isset($entityOffer) === true)
        {
            try
            {
                $payment = $this->repo->payment->find($paymentId);

                if ((isset($payment) === true) and
                    ($payment->isAuthorized() === true) or
                    ($payment->isCaptured() === true))
                {
                    $reward = $this->repo->reward->findOrFailPublic($id);

                    return $reward;
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }
        }

        $this->app['basicauth']->setModeAndDbConnection('test');

        $entityOffer = (new EntityOfferRepository())->findByEntityIdAndOfferIdAndType($paymentId, $id);

        if (isset($entityOffer) === true)
        {
            try
            {
                $payment = $this->repo->payment->find($paymentId);

                if ((isset($payment) === true) and
                    ($payment->isAuthorized() === true) or
                    ($payment->isCaptured() === true))
                {
                    $reward = $this->repo->reward->findOrFailPublic($id);

                    return $reward;
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }
        }
        return;
    }

    public function expireRewards()
    {
        return (new MerchantReward\Core())->expireRewards();
    }
}
