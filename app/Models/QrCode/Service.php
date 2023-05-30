<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Core as NonVaQrCore;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVaQrEntity;

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

        // If the above line doesn't throw an exception, it means the qrCode entity was found
        // We should now immediately set the merchant context.
        $this->app['basicauth']->setMerchantById($qrCode->merchant->getId());

        $this->trace->info(TraceCode::QR_CODE_IMAGE_DOWNLOAD_REQUEST,
                           [
                               'usage_type'  => $qrCode instanceof NonVaQrEntity ? $qrCode->getUsageType() : null,
                               'provider'    => $qrCode->getProvider(),
                               'entity_type' => $qrCode->getEntityType(),
                           ]
        );

        try
        {
            $qrCodeFile = $this->core()->fetchQrCodePathFromUfh($qrCode);
        }
        catch(\Exception $ex)
        {
            $this->trace->info(TraceCode::QR_CODE_DOWNLOAD_FAILED_REGENERATING, $qrCode->toArrayPublic());

            if($qrCode->getEntityType() == EntityConstants::VIRTUAL_ACCOUNT)
            {
                $this->core()->generateQrCodeFile($qrCode);
            }
            else
            {
                (new NonVaQrCore())->generateQrCodeFile($qrCode);
            }

            $qrCodeFile = $this->core()->fetchQrCodePathFromUfh($qrCode);
        }

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

        if ($qrCode->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
        {
            (new NonVirtualAccountQrCode\Service())->handleReminderForQrCode($qrCode);
        }

        $this->trace->info(TraceCode::QR_CODE_CREATED, $qrCode->toArrayPublic());

        return $qrCode;
    }

    public function fetch($input)
    {
        $input[Entity::ENTITY_TYPE] = 'virtual_account';

        $qrCodes = (new Repository)->fetchQrCodes($input, $this->merchant->getId());

        return $qrCodes->toArrayPublic();
    }
}
