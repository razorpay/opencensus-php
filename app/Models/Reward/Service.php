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



    public function update(array $input)
    {
        (new Validator())->validateInput('update', $input);

        $newMerchantIds = [];

        $reward = $input['reward'];

        $reward['id'] = (new Entity())::verifyIdAndStripSign($reward['id']);

        if (isset($input['merchant_ids']) === true)
        {
            $newMerchantIds = $input['merchant_ids'];
        }


        $updatedRewardFields = $this->core->update($reward);

        $failed_merchants_id = (new MerchantReward\Core())->update($reward);

        foreach ($newMerchantIds as $merchantId)
        {
            try
            {
                if($this->core->merchantRewardAlreadyExists($merchantId, $reward['id']) === false)
                {
                        (new MerchantReward\Core())->create($merchantId, $reward['id']);
                }
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);

                $failed_merchants_id[] = $merchantId;
            }
        }
        $response = ["failed_merchant_ids" =>  $failed_merchants_id, "reward" => $updatedRewardFields];

        return $response;
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

    public function getAdvertiserLogo($id)
    {
        $response["logo_url"] = null;

        try
        {
            $merchant = $this->repo->merchant->find($id);

            if(isset($merchant) === true)
            {
                $response["logo_url"] = $merchant->getFullLogoUrlWithSize();
            }
            return $response;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);
        }
        return $response;
    }
}
