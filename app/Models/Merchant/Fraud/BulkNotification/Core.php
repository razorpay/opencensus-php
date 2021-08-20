<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function notify(array $input): array
    {
        (new Validator())->validateInput('notify', $input);

        $bulkFraudNotificationEntity = (new Entity())->generateId();

        $this->trace->info(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_STARTED, [
            'input'     => $input,
            'entity_id' => $bulkFraudNotificationEntity->getId(),
        ]);

        $file = $input[Constants::FILE];

        (new File())->saveLocalFile($file, $bulkFraudNotificationEntity);

        $data = (new File())->getFileData($file);

        $headers = array_shift($data);

        $this->trace->debug(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_DATA, [
            'data'      => $data,
            'header'    => $headers,
            'entity_id' => $bulkFraudNotificationEntity->getId(),
        ]);

        $creator = (new Processor($bulkFraudNotificationEntity))->process($data, $headers);

        return [
            'link'      => $creator->getSignedUrl()['url'],
            'entity_id' => $bulkFraudNotificationEntity->getId(),
        ];
    }
}
