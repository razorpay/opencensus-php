<?php

namespace RZP\Reconciliator\NetbankingKotakV2\SubReconciliator;

use App;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\NetbankingKotakV2\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::AMOUNT;

    protected function getPaymentId(array $row)
    {
        $request = [
            'fields'           => ['payment_id'],
            'verification_ids' => [$row[Reconciliate::ENTITY_REFERENCE_NUMBER]]
        ];

        $response = App::getFacadeRoot()['nbplus.payments']->fetchNbplusData($request, 'netbanking');

        if ((isset($response['count']) === true) and ($response['count'] > 0))
        {
           return $response['items'][$row[Reconciliate::ENTITY_REFERENCE_NUMBER]]['payment_id'];
        }
        else
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_NOT_FOUND,
                [
                    'gateway'  => $this->gateway,
                    'trace_id' => $row[Reconciliate::ENTITY_REFERENCE_NUMBER],
                    'count'    => $response['count'],
                ]);
        }
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $row[Reconciliate::ACCOUNT_NUMBER] ?? null
        ];
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::AMOUNT]);
    }

    protected function getGatewayUniqueId(array $row)
    {
        return $row[Reconciliate::ENTITY_REFERENCE_NUMBER] ?? null;
    }
}
