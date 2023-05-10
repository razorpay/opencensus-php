<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class AcsController extends Controller
{
    protected $service = Merchant\Acs\Service::class;

    public function triggerSync()
    {
        $input = Request::all();
        $parityCheckEnabled = $input[Constant::PARITY_CHECK_ENABLED] ?? false;

        $data = [];
        if($parityCheckEnabled === true){
            /**
             * Triggering Parity Check
             */
            $data = $this->service()->triggerParityCheck($input);
        }else{
            $data = $this->service()->triggerSync($input);
        }

        return ApiResponse::json($data);
    }

    public function triggerFullSync()
    {
        $input = Request::all();
        $parityCheckEnabled = $input[Constant::PARITY_CHECK_ENABLED] ?? false;

        $data = [];

        if ($parityCheckEnabled === true){
            /**
             * Triggering Full Parity Check
             */
            $data = $this->service()->triggerFullParityCheck($input);
        }else{
            $data = $this->service()->triggerFullSync($input);
        }

        return ApiResponse::json($data);
    }

    public function handleAccountUpdateEvent()
    {
        $input = Request::all();

        $this->service()->handleAccountUpdateEvent($input);

        return ApiResponse::json();
    }
}
