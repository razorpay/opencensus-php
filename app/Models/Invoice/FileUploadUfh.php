<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base\PublicEntity;
use RZP\Services\UfhService;
use RZP\Trace\TraceCode;

class FileUploadUfh extends Core
{
    public function __construct()
    {
        parent::__construct();

        $this->ufhService = $this->getUfhService();
    }

    public function getUfhService()
    {
        $ufhServiceMock = $this->app['config']->get('applications.ufh.mock');

        if($ufhServiceMock === false)
        {
            $this->ufhService = new UfhService($this->app, $this->app['basicauth']->getMerchantId(), "invoice" );

            $this->trace->info(
                TraceCode::INVOICE_UFH_SERVICE_FETCHED,
                [
                    'merchant_id' => $this->app['basicauth']->getMerchantId(),
                    "clientType"  =>  "invoice"
                ]
            );
        }
        else
        {
            //returning null in case of test/local environment as mock ufh service doesn't work as expected and test cases fail
            $this->trace->info(
                TraceCode::INVOICE_UFH_SERVICE_NULL
            );

            return $this->app['ufh.service'];
        }

        return $this->ufhService;
    }

    public function getFiles($invoice)
    {
        $ufhQueryParams = [
            'entity_id'   => $invoice->getId(),
            'entity_type' => $invoice->getEntityName(),
        ];

        $response = $this->ufhService->fetchFiles($ufhQueryParams, $this->merchant->getId());

        $this->trace->info(
            TraceCode::INVOICE_PDF_UFH_FETCH_FILE_RESPONSE,
            $response
        );

        return $response;
    }

    public function getSignedUrl($invoice)
    {
        $response = $this->getFiles($invoice);

        if ($response['count'] === 0)
        {
            return null;
        }

        $fileId = $response['items'][0]['id'];

        $response = $this->ufhService->getSignedUrl($fileId);

        $this->trace->info(
            TraceCode::INVOICE_PDF_SIGNED_URL_FETCH_RESPONSE,
            $response
        );

        return $response['signed_url'];
    }
}
