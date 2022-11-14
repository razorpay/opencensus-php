<?php

namespace RZP\Models\Merchant\Consent\Processor;

use App;
use RZP\Models\Merchant\Consent\Processor\LegalDocumentProcessor;
use RZP\Models\Merchant\Consent\Processor\LegalDocumentProcessorMock;

class Factory
{
    /**
     * @return LegalDocumentProcessor|LegalDocumentProcessorMock
     */
    public function getLegalDocumentProcessor()
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['services.bvs.mock'];

        if ($mock === true)
        {
            $processor = new LegalDocumentProcessorMock();
        }
        else
        {
            $processor = new LegalDocumentProcessor();
        }

        return $processor;
    }
}
