<?php

namespace RZP\Models\Merchant\FreeCredits;

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
    public function grantFreeCreditsForMerchantInCampaign($mid, array $input)
    {
        $campaign = $input['campaign'];
        $freeCreditsLogExists = (new FreeCredits\Core)->checkIfFreeCreditsLogExists(
            $mid, $campaign);
        if ($freeCreditsLogExists)
        {
            throw new Exception\BadRequestValidationFailureException(
            'The record already exists for given campaign and merchant.');
        }
        $freeCreditsLog = (new FreeCredits\Core)->create($mid, $input);
        $response = array(
            'success' => true,
            'error'   => null,
            'log_id'  => $freeCreditsLog->id,
        );

        return $response;
    }

    public function fetchFreeCreditsLog($mid, $id)
    {
        // Raises Exception if record does not exist.
        $freeCreditsLog = (new FreeCredits\Core)->retrieveById($id);

        return $freeCreditsLog->toArrayPublic();
    }

    public function UpdateFreeCreditsLog($mid, $id, $input)
    {
        $response = array(
            'success' => true,
            'error'   => null,
        );

        $credits = $input['credits'];
        if ($credits > 0)
        {
            $op = 'add';
        }
        else
        {
            $op = 'deduct';
        }

        $credits = abs($credits);
        if ($op === 'add')
        {
            (new FreeCredits\Core)->grantFreeCredits($id, $credits);
            return $response;
        }
        else if ($op === 'deduct')
        {
            (new FreeCredits\Core)->deductFreeCredits($id, $credits);
            return $response;
        }
        else {
                $response['success'] = false;
                $response['error'] = 'Invalid Op Code Given';
        }

        return $response;
    }

    public function fetchFreeCreditsGrantedInCampaign($campaign)
    {
        $freeCredits = (new FreeCredits\Repository)->getFreeCreditsGrantedInCampaign($campaign);
        $response = array(
            'credits' => $freeCredits,
        );

        return $response;
    }

    public function fetchFreeCreditsGrantedToMerchant($merchantId)
    {
        $logs = (new FreeCredits\Repository)->getFreeCreditsLogsOfMerchant($merchantId);
        $logArray = array();
        foreach($logs as $log)
        {
            array_push($logArray, $log->toArrayPublic());
        }

        return $logArray;
    }
}
