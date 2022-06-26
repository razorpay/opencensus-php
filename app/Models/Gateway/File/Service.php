<?php

namespace RZP\Models\Gateway\File;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Services\UfhService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\Gateway\File\Constants as GatewayConstants;

class Service extends Base\Service
{
    protected $ufh;

    public function __construct()
    {
        parent::__construct();

        $this->ufh = (new UfhService($this->app));
    }

    public function create(array $input)
    {
        $input = $this->formatInput($input);

        $gatewayFiles = $this->core()->create($input);

        return $gatewayFiles->toArrayAdmin();
    }

    public function acknowledge(string $id, array $data)
    {
        $this->trace->info(TraceCode::GATEWAY_FILE_ACKNOWLEDGE_REQUEST, [
            'id'   => $id,
            'data' => $data,
        ]);

        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        $gatewayFile = $this->core()->acknowledge($gatewayFile, $data);

        return $gatewayFile->toArrayAdmin();
    }

    public function retry(string $id)
    {
        $gatewayFile = $this->repo->gateway_file->findOrFailPublic($id);

        $this->core()->process($gatewayFile);

        return $gatewayFile->toArrayAdmin();
    }

    protected function formatInput(array $input): array
    {
        if ((isset($input['targets']) === false) or
            (is_sequential_array($input['targets']) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'targets are required and should be sent');
        }

        $targets = $input['targets'];
        unset($input['targets']);

        $this->updateTimePeriodIfApplicable($input, $targets);

        foreach ($targets as $target)
        {
            $input[Entity::TARGET] = $target;

            $data[] = $input;
        }

        return $data;
    }

    protected function updateTimePeriodIfApplicable(array & $input, array $targets)
    {
        // Adding special case for citi mandate file generation to accommodate high load on file generation.
        // if begin and end is sent from cron request consider that else continue

        if (($this->app['basicauth']->isCron() === true) and
            ($input[Entity::TYPE] === Type::NACH_DEBIT) and
            (in_array(Constants::PAPER_NACH_CITI, $targets) === true) and
            (empty($input[Entity::BEGIN]) === false) and
            (empty($input[Entity::END]) === false))
        {
            return;
        }

        if (($input[Entity::TYPE] === Type::CARDSETTLEMENT) and
            (in_array(Constants::AXIS, $targets) === true))
        {
            $input[Entity::BEGIN] = $input[Entity::BEGIN] ?? 946684800;

            $input[Entity::END] = $input[Entity::END] ?? 946684801;

            return;
        }

        // When called via cron, we update the timestamps for the
        // gateway file for the to indicate the previous days time period.

        if ($this->app['basicauth']->isCron() === false)
        {
            return;
        }

        $input[Entity::BEGIN] = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $input[Entity::END] = Carbon::today(Timezone::IST)->getTimestamp() - 1;
    }

    /**
     * Send Upload file request to Ufh Service
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function uploadBankRefundFile(array $input)
    {
        $gateway = $input[GatewayConstants::GATEWAY];

        if (in_array($gateway, GatewayConstants::MANUAL_FILE_SUPPORTED_GATEWAYS, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY,
                'gateway',
                $gateway
            );
        }

        $file            = $input[GatewayConstants::FILE];
        $fileName        = $file->getClientOriginalName();
        $storageFileName = GatewayConstants::STORAGE_FILE_PATH[$gateway] . '/' . $fileName;

        return $this->uploadFileToUfh($file, $storageFileName, GatewayConstants::MANUAL_REFUND_FILE);
    }

    /**
     * @param UploadedFile $file
     * @param string       $storageFileName
     * @param string       $type
     *
     * @return array
     * @throws Exception\ServerErrorException
     */
    protected function uploadFileToUfh(UploadedFile $file, string $storageFileName, string $type): array
    {
        $response = $this->ufh->uploadFileAndGetResponse($file, $storageFileName, $type, null);

        $this->trace->info(
            TraceCode::UFH_FILE_UPLOAD, [
            'response' => $response,
        ]);

        return [
            GatewayConstants::FILE_ID => $response[GatewayConstants::ID],
            GatewayConstants::SUCCESS => isset($response[GatewayConstants::ID]),

        ];
    }

}
