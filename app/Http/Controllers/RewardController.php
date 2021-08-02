<?php


namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;


class RewardController extends Controller
{
    public function createReward()
    {
        $input = Request::all();
        $data = $this->service()->create($input);
        return ApiResponse::json($data);
    }

    public function updateReward()
    {
        $input = Request::all();

        $data = $this->service()->update($input);

        return ApiResponse::json($data);
    }

    public function activateDeactivateReward()
    {
        $input = Request::all();

        $data = $this->service()->activateDeactivateReward($input);

        return ApiResponse::json($data);
    }

    public function deleteReward($id)
    {
        $data = $this->service()->delete($id);

        return ApiResponse::json($data);
    }

    public function fetchReward()
    {
        $data = $this->service()->fetch();

        return ApiResponse::json($data);
    }

    public function rewardRedirectUrl($reward_id, $payment_id)
    {
        $data = $this->service()->rewardRedirectUrl($reward_id, $payment_id);

        if (isset($data) === false)
        {
            return null;
        }

        return View::make('reward.redirect')->with('data', $data);


    }

    public function getRewardTerms($id, $paymentId)
    {
        $input = Request::All();

        $data = $this->service()->getRewardTerms($id, $paymentId);

        if (isset($data) === false)
        {
            return View::make('reward.terms_error');
        }

        if(isset($input['coupon_code']))
        {
            $data['reward']['coupon_code'] = $input['coupon_code'];
        }

        if(isset($input['email_variant']))
        {
            $data['email_variant'] = $input['email_variant'];
        }

        return View::make('reward.terms')->with('data', $data);
    }

    // instrument redirection to  redirect link via different events
    // current event types: coupon / icon
    public function getRewardMetrics($id, $paymentId, $eventType)
    {
        $input = Request::All();

        $data = $this->service()->getRewardMetrics($id, $paymentId, $eventType, $input);

        if (isset($data) === false)
        {
            return View::make('reward.terms_error');
        }

        return View::make('reward.redirect')->with('data', $data);
    }

    public function expireRewards()
    {
        $data = $this->service()->expireRewards();

        return ApiResponse::json($data);
    }
    public function getAdvertiserLogo($id)
    {
        $data = $this->service()->getAdvertiserLogo($id);

        return ApiResponse::json($data);
    }
    public function sendRewardMailToMerchants()
    {
        $input = Request::all();

        $data = $this->service()->sendRewardMailToMerchants($input);

        return ApiResponse::json($data);
    }
}
