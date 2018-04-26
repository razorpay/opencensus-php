<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use RZP\Models\Gateway\File\Type;

class Obc extends Base
{
    public function createFile($data)
    {
        if (isset($data['refunds']) === true)
        {
            $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

            $refundFileProcessor->createFile($data['refunds']);
        }
    }

    protected function formatDataForMail(array $data)
    {

    }
}
