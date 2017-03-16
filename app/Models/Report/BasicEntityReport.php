<?php

namespace RZP\Models\Report;

use Carbon\Carbon;

use RZP\Base\JitValidator;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity as E;
use RZP\Exception;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Trace\TraceCode;

class BasicEntityReport extends Base
{
    use FileHandlerTrait;

    const BATCH_LIMIT = 20000;

    // Maps the entity to the relations that need to be fetched for it
    protected $entityToRelationFetchMap = [
        E::TRANSACTION => [
            // Maps transaction source to entities that need to be fetched
            E::PAYMENT  => [E::ORDER],
            E::REFUND   => [
                E::PAYMENT,
                E::PAYMENT . '.' . E::ORDER
            ],
        ],
        E::PAYMENT => [E::CARD],
        E::REFUND => [E::PAYMENT],
        E::ORDER => [],
        E::SETTLEMENT => []
    ];

    protected $entity;

    protected $relationsToFetch;

    protected $allowed = array(
        E::ORDER,
        E::REFUND,
        E::PAYMENT,
        E::SETTLEMENT,
        E::TRANSACTION,
    );

    public function __construct(string $entity)
    {
        parent::__construct();

        $this->entity = $entity;

        $this->relationsToFetch = $this->entityToRelationFetchMap[$this->entity];
    }

    public function getReport(array $input)
    {
        $this->preReportProcessing($input);

        list($from, $to) = $this->getTimestamps($input);

        //list($count, $skip) = $this->getFetchLimits($input);

        // currently limiting the api response can break the merchant integration
        // so overwriting the limits for now
        list($count, $skip) = [200000, 0];

        list($data, $count) = $this->getReportData($from, $to, $count, $skip);

        return $data;
    }

    public function getReportUrl(array $input): array
    {
        $this->preReportProcessing($input);

        list($from, $to) = $this->getTimestamps($input);

        list($count, $skip) = $this->getFetchLimits($input);

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $fileName = $this->merchant->getId() . '_' . $this->entity . '_' . $now;

        $append = false;

        while ($count === self::BATCH_LIMIT)
        {
            list($data, $count) = $this->getReportData($from, $to, self::BATCH_LIMIT, $skip);

            $fullpath = $this->createCsvFile($data, $fileName, null, 'files/report', $append);

            $skip += $count;

            $append = true;
        }

        $zipPath = $this->makeZipFileWithPath($fullpath);

        $zipMimeType = 'application/zip';

        $key = 'report/' . $fileName . '.csv';

        $url = $this->saveToAws($key, $zipPath, $zipMimeType);

        $signedUrl = $this->getPreSignedUrlFromAws($key);

        if (file_exists($zipPath) === true)
        {
            unlink($zipPath);
        }

        return ['url' => $signedUrl];
    }

    protected function getReportData($from, $to, $count, $skip): array
    {
        $merchantId = $this->merchant->getId();

        $begin = time();

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'method'        => __METHOD__,
                'entity'        => $this->entity,
                'from'          => $from,
                'to'            => $to,
                'count'         => $count,
                'skip'          => $skip,
                'merchantId'    => $merchantId,
                'time_started'  => $begin
            ]);

        $entities = $this->fetchEntitiesForReport(
                                $merchantId, $from, $to, $count, $skip);

        $timeTaken = time() - $begin;

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'method'        => __METHOD__,
                'entity'        => $this->entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId,
                'time_taken'    => $timeTaken
            ]);

        $formattedData = $this->fetchFormattedDataForReport($entities);

        $timeTaken = time() - $begin;

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'method'        => __METHOD__,
                'entity'        => $this->entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId,
                'time_taken'    => $timeTaken
            ]);

        $fetchCount = $entities->count();

        return [$formattedData, $fetchCount];
    }

    protected function preReportProcessing(array $input)
    {
        $this->checkAllowedEntity();

        $this->increaseAllowedSystemLimits();

        (new JitValidator)->rules(self::$rules)->input($input)->validate();

        date_default_timezone_set('Asia/Kolkata');
    }

    protected function checkAllowedEntity()
    {
        if (in_array($this->entity, $this->allowed, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot get report for the given entity');
        }
    }

    protected function fetchFormattedDataForReport($entities): array
    {
        return $entities->toArrayReport();
    }

    protected function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip)
    {
        $entity = $this->entity;

        $repo = $this->repo->$entity;

        return $repo->fetchEntitiesForReport(
                        $merchantId,
                        $from,
                        $to,
                        $count,
                        $skip,
                        $this->relationsToFetch);
    }

    protected function getFetchLimits($input): array
    {
        $count = self::BATCH_LIMIT;
        $skip = 0;

        if (isset($input['count']))
        {
            $count = min($count, (int) $input['count']);
        }

        if (isset($input['skip']))
        {
            $skip = (int) $input['skip'];
        }

        return [$count, $skip];
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(501);
    }
}
