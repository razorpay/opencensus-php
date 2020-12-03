<?php

namespace RZP\Services\Mock;

use Config;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\HashAlgo;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Services\RazorpayXClient as BaseRazorpayXClient;

class RazorpayXClient extends BaseRazorpayXClient
{
    public function createContact($data)
    {
        if (empty($data['name']) === true)
        {
            throw new Exception\InvalidArgumentException(
                'contact_name is mandatory');
        }

        $uniqueId = UniqueIdEntity::generateUniqueId();

        $response = [
            'id'           => 'cont_' . $uniqueId,
            'entity'       => 'contact',
            'name'         => $data['name'],
            'contact'      => $data['contact'],
            'email'        => $data['email'],
            'type'         => $data['type'],
            'reference_id' => NULL,
            'batch_id'     => NULL,
            'active'       => TRUE,
            'notes'        => [],
            'created_at'   => Carbon::now()->getTimestamp(),
        ];

        return $response;
    }

    public function createFundAccount($contactId, $data)
    {
        if (empty($data['name']) === true ||
            empty($data['ifsc']) === true ||
            empty($data['account_number']) === true)
        {
            throw new Exception\InvalidArgumentException(
                'bank_account_name, bank_branch_ifsc and bank_account_number are mandatory');
        }

        $uniqueId = UniqueIdEntity::generateUniqueId();

        $response = [
            'id'           => 'fa_' . $uniqueId,
            'entity'       => 'fund_account',
            'contact_id'   => $contactId,
            'account_type' => 'bank_account',
            'bank_account' => [
                'ifsc'           => $data['ifsc'],
                'bank_name'      => 'Ramdom Bank',
                'name'           => $data['name'],
                'notes'          => [],
                'account_number' => $data['account_number'],
            ],
            'batch_id'   => NULL,
            'active'     => TRUE,
            'created_at' => Carbon::now()->getTimestamp(),
        ];

        return $response;
    }

    public function makePayoutRequest($data, $idempotencyKey, $isMerchantWithXSettlementAccount)
    {
        if (empty($data['fund_account_id']) === true ||
            empty($data['amount']) === true ||
            empty($data['currency']) === true ||
            empty($data['mode']) === true )
        {
            throw new Exception\InvalidArgumentException(
                'fund_account_id, amount, currency, mode and reference_id are mandatory');
        }

        $uniqueId = UniqueIdEntity::generateUniqueId();

        $response = [
            'id'             => 'pout_' . $uniqueId,
            'entity'         => 'payout',
            'fund_account_id'=> $data['fund_account_id'],
            'amount'         => $data['amount'],
            'currency'       => 'INR',
            'notes'          => [],
            'fees'           => 1770,
            'tax'            => 270,
            'status'         => 'processing',
            'purpose'        => 'payout',
            'utr'            => NULL,
            'mode'           => $data['mode'],
            'reference_id'   => $data['reference_id'],
            'narration'      => 'Test Account Fund Transfer',
            'batch_id'       => NULL,
            'failure_reason' => NULL,
            'created_at'     => Carbon::now()->getTimestamp(),
        ];

        // for settlement.ondemand of 440000, special case of payout status as processed instead of processing
        if ($data['amount'] === 429616)
        {
            $response['status'] = 'processed';
        }

        // for settlement.ondemand of 880000, special case of payout status as reversed instead of processing
        if ($data['amount'] === 859232)
        {
            $response['status'] = 'reversed';

            if ($isMerchantWithXSettlementAccount)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::SERVER_ERROR_RAZORPAYX_PAYOUT_REVERSAL,
                    null,
                    null,
                    ['response' => $response]);
            }
        }


        return $response;
    }
}
