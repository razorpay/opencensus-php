<?php


namespace RZP\Models\Merchant\PaymentLimit;

use RZP\Models\Base;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Action as Actions;

class Processor extends Base\Core
{
    use FileHandlerTrait;

    protected $validator;

    public function __construct(Entity $entity)
    {
        parent::__construct();

        $this->entity = $entity;

        $this->validator = new Validator();
    }

    public function generateFile(array $fileData, string $fileName)
    {
        $ufh = (new File())->saveFileWithFormattedHeader($fileData, $fileName, $this->entity);

        $signedFileUrl = $ufh->getSignedUrl(constants::SIGNED_URL_DURATION);

        $this->trace->info(TraceCode::MERCHANT_MAX_PAYMENT_LIMIT_CSV_UPLOADED, [
                'fileData' => $fileData,
                'ufh' => $ufh->getFileInstance()->toArrayPublic(),
                'signedFileUrl' => $signedFileUrl,
            ]
        );

        return [
            'file_path' => $ufh->getFullFileName(),
            'signed_url' => $signedFileUrl["url"],
        ];

    }

    protected function processForCSVConversion(array $data, array $headers, string $entity_id)
    {
        $this->validator->validateInput('csvHeader', $headers);
        $this->validator->validateRowsLimit($data);

        $dataWithHeader = array_merge([$headers], $data);

        $fileName = Constants::MAX_PAYMENT_LIMIT_OUTPUT . '_' . $entity_id;

        return $this->generateFile($dataWithHeader, $fileName);
    }


    public function process(array $data, array $headers, string $entity_id)
    {
        $csvFilePath = $this->processForCSVConversion($data, $headers, $entity_id);

        $input = [
            "action" => Actions::UPDATE_MAX_PAYMENT_LIMIT,
            "signed_url" => $csvFilePath['signed_url'],
            "file_path" => $csvFilePath['file_path'],
            "entity_id" => $entity_id,
        ];

        $workflowActionId = (new Core())->handleBulkAction($input);

        return [
            "csv_file_path" => $csvFilePath,
            "workflow_action_id" => $workflowActionId,
        ];
    }
}
