<?php

namespace RZP\Models\OfflinePayment;

use Carbon\Carbon;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\OfflinePayment;

class Transformer extends Base\Service
{
    protected static $monthMap = [
        "jan"         => 1,
        "feb"         => 2,
        "mar"         => 3,
        "apr"         => 4,
        "may"         => 5,
        "jun"         => 6,
        "jul"         => 7,
        "aug"         => 8,
        "sep"         => 9,
        "oct"         => 10,
        "nov"         => 11,
        "dec"         => 12,
    ];

    public function convertInputToOfflineGenericRequest($input)
    {
        $validator = new OfflinePayment\Validator();

        (new JitValidator)->setStrictFalse()->rules(Validator::$OfflinePaymentRules)->caller($this)->validate($input);

        $mode = strtolower($input[Entity::MODE]);

        $mode = $validator->validateOfflineMode($mode, $input[Entity::MODE]);

        // Validate payment mode if API call is not from Batch service.

        if($input[Entity::SOURCE] !== Entity::FILE)
        {
            $validator->validateModeForHDFC($mode);
        }

        $status = strtolower($input[Entity::STATUS]);

        $status = $validator->validateOfflineStatus($status, $input[Entity::STATUS]);

        $dateArray = explode("-", $input['payment_date']);
        if (strlen($dateArray[1]) > 2) {

            $dateArray[1] = strtolower($dateArray[1]);
            $dateArray[1] = substr($dateArray[1], 0, 3);

            try {
                $month = $this->convertMonthNameToInt($dateArray[1]);
                $dateArray[1] = $month;
                $input['payment_date'] = implode("-", $dateArray);
            } catch (\Throwable $ex) {
                $input['payment_date'] = date('d-m-Y');
            }

        }

        if (empty($input['payment_date']) === false and
            (empty($input['payment_time']) === false)) {
            $data = $input['payment_date'] . ' ' . $input['payment_time'];
        }

        $time = Carbon::createFromFormat('d-m-Y H:i:s', $data, Timezone::IST)->getTimestamp();

        return [
            Entity::CHALLAN_NUMBER => $input[Entity::CHALLAN_NO],
            Entity::AMOUNT => stringify($input[Entity::AMOUNT]),
            Entity::MODE => $mode,
            Entity::STATUS => $status,
            Entity::DESCRIPTION => $input[Entity::DESCRIPTION] ?? null,
            Entity::BANK_REFERENCE_NUMBER => $input[Entity::BANK_REFERENCE_NUMBER] ?? null,
            Entity::PAYMENT_INSTRUMENT_DETAILS => $input[Entity::PAYMENT_INSTRUMENT_DETAILS] ?? '',
            Entity::PAYER_DETAILS => $input[Entity::PAYER_DETAILS] ?? '',
            Entity::PAYMENT_TIMESTAMP => $time ?? null,
            Entity::ADDITIONAL_INFO => $input[Entity::ADDITIONAL_INFO] ?? null,
            Entity::CLIENT_CODE => $input[Entity::CLIENT_CODE] ?? null,
            Entity::SOURCE => $input[Entity::SOURCE] ?? Entity::CALLBACK,
        ];
    }

    public function convertMonthNameToInt(string $month)
    {
        $month = self::$monthMap[[$month]?? ''];

        return $month;
    }

    public function validateCustomRequestPayload($request) {
        $auth = $this->auth->getInternalApp();

        switch ($auth) {
            case 'hdfc_otc':
                (new OfflinePayment\Entity())->setAuth('hdfc_otc');
                (new OfflinePayment\HdfcEcollect\Validator())->validateRequestPayload($request);
                break;
        }
    }

    public function createCustomOfflinePaymentResponse($response)
    {

        $auth = $this->auth->getInternalApp();

        switch ($auth) {

            case 'hdfc_otc':
                if ($response['error'] !== null)
                {
                    if ($response['error']['code'] === 'BAD_REQUEST_ERROR')
                    {
                        $response['error']['code'] = 'BAD_REQ_ER';
                    }

                    if ($response['error']['code'] === 'SERVER_ERROR')
                    {
                        $response['error']['code'] = 'SERVER_ER';
                    }

                }

                break;

            default:
        }

        return $response;
    }
}
