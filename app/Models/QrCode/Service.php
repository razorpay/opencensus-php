<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;

class Service extends Base\Service
{
    /**
     * This return qr code file
     * path downloading the qr code
     *
     * @param string $id
     *
     * @return string
     */
    public function fetchQrCodePath(string $id)
    {
        // Can't use merchant here because this is a direct route
        $qrCode = $this->repo->qr_code->findByPublicId($id);

        $qrCodeFile = $this->core()->fetchQrCodePathFromUfh($qrCode);

        return $qrCodeFile;
    }

    public function tokenizeExistingQrStringMpans($input)
    {
        $this->trace->info(
            TraceCode::TOKENIZE_QR_STRING_MPANS_REQUEST,
            $input
        );

        $validator = new Validator();

        $validator->validateInput('tokenize_existing_qr_string_mpans', $input);

        $response = [
            Constants::QR_STRING_MPAN_TOKENIZATION_SUCCESS_COUNT => 0,
            Constants::QR_STRING_MPAN_TOKENIZATION_FAILED_COUNT  => 0,
            Constants::QR_STRING_MPAN_TOKENIZATION_SUCCESS_IDS   => [],
            Constants::QR_STRING_MPAN_TOKENIZATION_FAILED_IDS    => [],
        ];

        $count = $input['count'] ?? 100;

        $qrCodes = $this->repo->useSlave(function() use ($count) {
            return $this->repo->qr_code->fetchQrCodesForMpanTokenization($count);
        });

        foreach ($qrCodes as $qrCode)
        {
            try
            {
                $this->core()->tokenizeExistingQrCodeMpans($qrCode);

                $response[Constants::QR_STRING_MPAN_TOKENIZATION_SUCCESS_COUNT]++;
                $response[Constants::QR_STRING_MPAN_TOKENIZATION_SUCCESS_IDS][] = $qrCode->getId();

            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException($ex,
                                             Trace::ERROR,
                                             TraceCode::MPAN_TOKENIZATION_FAILED,
                                             [
                                             ]);

                $response[Constants::QR_STRING_MPAN_TOKENIZATION_FAILED_COUNT]++;
                $response[Constants::QR_STRING_MPAN_TOKENIZATION_FAILED_IDS][] = $qrCode->getId();
            }
        }

        $this->trace->info(
            TraceCode::TOKENIZE_EXISTING_MPANS_RESPONSE,
            $response
        );

        return $response;
    }

    public function create($input, $virtualAccount = null)
    {
        $this->trace->info(TraceCode::QR_CODE_CREATE_REQUEST, [
            'input'           => $input,
            'virtual_account' => $virtualAccount->getId() ?? null
        ]);

        try
        {
            $qrCode = (new Core($virtualAccount))->buildQrCode($input);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        }

        $this->trace->info(TraceCode::QR_CODE_CREATED, $qrCode->toArrayPublic());

        return $qrCode;
    }
}
