<?php


namespace RZP\Models\Reward;

use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Offer\EntityOffer\Repository as EntityOfferRepository;
use RZP\Models\Reward\MerchantReward\Validator as MerchantRewardValidator;
use RZP\Diag\EventCode;

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

    public function sendRewardMailToMerchants(array $input)
    {
        (new Validator())->validateInput('mailer', $input);

        $rewardIds = array_unique($input['reward_ids']);

        $merchantIds = array_unique($input['merchant_ids']);

        $content = $input['content'];

        $subject = $input['subject'];

        $response['failed_reward_ids'] = [];

        $response['failed_merchant_ids'] = [];

        $data = [];

        $data['content'] = $content;

        foreach($rewardIds as $rewardId)
        {
            $reward = $this->repo->reward->findOrFailPublic($rewardId);

            if(isset($reward) === true)
            {
                $data['reward'][] = array(
                    'id'            => $reward->getId(),
                    'logo'          => $reward->getLogo(),
                    'ends_at'       => date("d M Y", $reward->getEndsAt()),
                    'starts_at'     => date("d M Y", $reward->getStartsAt()),
                    'coupon_code'   => $reward->getCouponCode(),
                    'terms'         => $reward->getTerms(),
                    'name'          => $reward->getName(),
                    'display_text'  => $reward->getDisplayText(),
                    'percent_rate'  => $reward->getPercentRate(),
                    'flat_cashback' => $reward->getFlatCashback(),
                    'max_cashback'  => $reward->getMaxCashback(),
                    'min_amount'    => $reward->getMinAmount(),
                    'merchant_website_redirect_link' => $reward->getMerchantWebsiteRedirectLink(),
                    'brand_name'    => $reward->getBrandName()
                );
            }
            else
            {
                $response['failed_reward_ids'] = $rewardId;
            }
        }

        foreach($merchantIds as $merchantId)
        {
            try
            {
                $result = (new Core())->sendMailToMerchant($merchantId, $subject, $data);

                if($result === false)
                {
                    $response['failed_merchant_ids'][] = $result;
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $response['failed_merchant_ids'][] = $merchantId;
            }
        }
        return $response;
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

    public function rewardRedirectUrl($reward_id, $payment_id)
    {

        $entityOffer = (new EntityOfferRepository())->findByEntityIdAndOfferIdAndType($payment_id, $reward_id);

        if (isset($entityOffer) === true)
        {
            try
            {
                $payment = $this->repo->payment->find($payment_id);

                if ((isset($payment) === true) and
                    ($payment->isAuthorized() === true) or
                    ($payment->isCaptured() === true))
                {
                    $reward = $this->repo->reward->findOrFailPublic($reward_id);

                    try
                    {
                        $properties = [];

                        $this->app['rzp.mode'] = 'live';

                        $properties['payment_id'] = $payment->getId();

                        $properties['reward_id'] = $reward->getId();

                        $properties['coupon_code'] = $reward->getCouponCode();

                        $properties['publisher merchant_id'] = $payment->getMerchantId();

                        $properties['contact_number'] =  $payment->getContact();

                        $this->app['diag']->trackRewardEvent(EventCode::REWARD_REDIRECT, null, null, $properties);

                    }
                    catch(\Exception $e)
                    {
                        $this->trace->traceException($e);
                    }

                    return ["url" => $reward->getMerchantWebsiteRedirectLink()];
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }
        }

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
