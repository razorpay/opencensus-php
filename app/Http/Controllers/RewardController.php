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

    public function getRewardTerms($id)
    {
        $data = $this->service()->getRewardTerms($id);

        return View::make('reward.terms')->with('data', $data);
    }
    public function expireRewards()
    {
        $data = $this->service()->expireRewards();

        return ApiResponse::json($data);
    }
}
