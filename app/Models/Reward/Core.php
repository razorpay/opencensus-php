<?php


namespace RZP\Models\Reward;
use Mail;
use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Mail\Reward as RewardMail;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

    }

    public function create($input)
    {
        $this->trace->info(TraceCode::REWARD_CREATE_REQUEST, $input);

        $reward = new Entity;

        $reward->build($input);

        if (isset($input['starts_at']) === false)
        {
            $reward->setStartsAt(Carbon::today()->getTimestamp());
        }

        $this->repo->saveOrFail($reward);
        return $reward;
    }

    public function update($reward)
    {
        $this->trace->info(TraceCode::REWARD_UPDATE_REQUEST, $reward);

        $rewardEntity = $this->repo->reward->find($reward['id']);

        $now = Carbon::now()->getTimestamp();

        $validator = new Validator();

        ($validator->validateInput('update_reward', $reward));

        ($validator->validateIfRewardExists($rewardEntity));

        if(isset($reward['starts_at']) === true)
        {
            ($validator->validateStartTime($reward, $rewardEntity));
        }

        $rewardStartTime = isset($reward['starts_at']) ? $reward['starts_at'] : $rewardEntity->getStartsAt();

        $rewardEndTime = isset($reward['ends_at']) ? $reward['ends_at'] : $rewardEntity->getEndsAt();

        ($validator->validateRewardPeriodForUpdation(["starts_at" => $rewardStartTime, "ends_at" => $rewardEndTime]));

        $columnsToUpdate = [];

        $columns = [ENTITY::NAME, ENTITY::STARTS_AT, ENTITY::ENDS_AT, ENTITY::DISPLAY_TEXT, ENTITY::LOGO, ENTITY::TERMS, ENTITY::MERCHANT_WEBSITE_REDIRECT_LINK, ENTITY::COUPON_CODE ];

        foreach($columns as $column)
        {
            if (isset($reward[$column]) === true)
            {
                $columnsToUpdate[$column] = $reward[$column];
            }
        }

        array_unshift($columns, ENTITY::ID);

        $columnsToUpdate[ENTITY::UPDATED_AT] = $now;

        $this->repo->reward->update($reward['id'], $columnsToUpdate);

        $response = $this->repo->reward->fetchUpdatedRewardColumnsById($reward['id'], $columns);

        $properties = [];

        try
        {
            $properties['reward_id'] = $reward['id'];

            $properties['updated_fields'] = $columnsToUpdate;

            $properties['coupon'] = $rewardEntity->getCouponCode();

            $this->app['diag']->trackRewardEvent(EventCode::REWARD_UPDATED, null, null, $properties);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e);
        }

        return $response->toArrayPublic();
    }

    public function sendMailToMerchant( $merchantId, $subject, $data)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if(isset($merchant) === false || empty($data['reward']) === true)
        {
            return false;
        }
        $data['merchant_name'] = $merchant->getBillingLabelNotName();

        $data['subject'] = $subject;


        Mail::queue(new RewardMail\NewReward($merchant->getEmail() , $subject, $data));

        return true;
    }

    public function fetchReward($merchantId)
    {
        $merchantRewards = $this->repo->merchant_reward->fetchMerchantRewardByMerchantId($merchantId);

        $response = [];

        foreach ($merchantRewards as $merchantReward)
        {
            $reward = $this->repo->reward->find($merchantReward->getRewardId());

            $response [] = array_merge($reward->toArrayPublic(), $merchantReward->toArrayPublic());
        }
        $created_at_column = array_column($response, 'created_at');

        //sort by reward created_at column in desc order
        array_multisort($created_at_column, SORT_DESC, $response);

        return $response;
    }

    public function merchantRewardAlreadyExists($merchantId, $rewardId)
    {
        $reward = $this->repo->merchant_reward->fetchMerchantRewardByMerchantIdAndRewardId($merchantId, $rewardId);

        if($reward === Null)
        {
            return false;
        }
        return true;
    }
}
