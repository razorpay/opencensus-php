<?php

namespace RZP\Models\Merchanti\FreeCredits;

use RZP\Constants\Mode;
use Carbon\Carbon;
use Mail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\FreeCredits;

use RZP\Exception;
use RZP\Error\ErrorCode;

use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function addFreeCreditsForMerchant(array $input)
    {
        $freeCreditLogExists = (new FreeCredits\Core)->checkFreeCreditLogExists($id);
        if (!$freeCreditLogExists)
        {
            return array(
                'success' => false,
                'error'   => 'Free Credits already given for the merchant in the campaign. Update the log',
            );
        }
        $freeCreditLog = (new FreeCredits\Core)->create($input);
        $response = array(
            'success' => true,
            'error'   => null,
            'log_id'  => $freeCreditLog->id,
        );
        // TODO: Add Free Credits to the Merchant Model
        return $response;
    }

    public function fetchFreeCreditsLog($id)
    {
        $freeCreditsLog = (new FreeCredits\Core)->retrieveOrFailById($id);
        $response = $freeCreditsLog->toArrayPublic();
        return $response;
    }

    public function UpdateFreeCreditsLog($id, $op, $input)
    {
        $credits = $input['credits'];
        $response = array(
            'success' => true,
            'error'   => null,
        );
        if ($op === 'add')
        {
            $res = $this->addMoreFreeCredits($id, $credits);
            if ($res['added'])
            {
                //TODO: Send Mail or Notify Merchant
                return $response;
            }
            else
            {
                $response['success'] = false;
                $response['error'] = $res['error'];
            }
            return $response;
        }
        else if ($op === 'deduct')
        {
            $res = $this->deductFreeCredits($id, $credits);
            if ($res['deducted'])
            {

                //TODO: Send Mail or Notify Merchant
                return $response;
            }
            else
            {
                $response['success'] = false;
                $response['error'] = $res['error'];
            }
            return $response;
        }
        else {
                $response['success'] = false;
                $response['error'] = 'Invalid Op Code Given';
        }
        return $response;
    }
}
