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

        $mode = strtolower($input['mode']);

        $mode = $validator->validateOfflineMode($mode, $input['mode']);

        $status = strtolower($input['status']);

        $status = $validator->validateOfflineStatus($status, $input['status']);

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
            'challan_number' => $input['challan_no'],
            'amount' => stringify($input['amount']),
            'mode' => $mode,
            'status' => $status,
            'description' => $input['description'] ?? null,
            'bank_reference_number' => $input['bank_reference_number'] ?? null,
            'payment_instrument_details' => $input['payment_instrument_details'] ?? '',
            'payer_details' => $input['payer_details'] ?? '',
            'payment_timestamp' => $time ?? null,
            'additional_info' => $input['additional_info'] ?? null,
            'client_code' => $input['client_code'] ?? null,
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
