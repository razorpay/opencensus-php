<?php

namespace RZP\Models\Merchant\Consent\Processor;

use App;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\LegalDocumentBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ConsentDocumentBaseResponse;

class LegalDocumentProcessorMock extends LegalDocumentProcessor
{
    /**
     * @param array|null $input
     * @param string $platform
     * @param bool $isExpEnabled
     * @return LegalDocumentBaseResponse|ConsentDocumentBaseResponse
     */
    public function processLegalDocuments(array $input = null, string $platform = 'pg', bool $isExpEnabled = false): LegalDocumentBaseResponse|ConsentDocumentBaseResponse
    {
        $response = new LegalDocumentsManagerResponse();

        $response->setId(DEConstants::DUMMY_REQUEST_ID);

        $app = App::getFacadeRoot();

        $mock = $app['config']['services.send.notification'];

        if  ($mock === true)
        {
            $actualSendSMSData = $input[ DEConstants::NOTIFICATION_DETAILS]['send_sms'];
            $actualSendEmailData = $input[ DEConstants::NOTIFICATION_DETAILS]['send_email'];

            $expectedSendSMSData = true;
            $expectedSendEmailData = true;

            if ($actualSendSMSData !== $expectedSendSMSData or $actualSendEmailData !== $expectedSendEmailData)
            {
                $response->setStatus(DEConstants::FAILED);
            }
            else
            {
                $response->setStatus(DEConstants::INITIATED);
            }
        }
        else
        {
            $response->setStatus(DEConstants::INITIATED);
        }

        return new LegalDocumentBaseResponse($response);
    }
}
