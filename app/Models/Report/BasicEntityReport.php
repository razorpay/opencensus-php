<?php

namespace RZP\Models\Report;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Base\JitValidator;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity as E;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class BasicEntityReport extends Base
{
    use FileHandlerTrait;

    const BATCH_LIMIT = 20000;

    // Maps the entity to the relations that need to be fetched for it
    protected $entityToRelationFetchMap = [
        E::TRANSACTION  => [
            // Maps transaction source to entities that need to be fetched
            E::PAYMENT  => [E::ORDER],
            E::REFUND   => [
                E::PAYMENT,
                E::PAYMENT . '.' . E::ORDER
            ],
        ],
        E::MERCHANT     => [],
        E::PAYMENT      => [E::CARD],
        E::REFUND       => [E::PAYMENT],
        E::ORDER        => [],
        E::SETTLEMENT   => []
    ];

    protected $entity;

    protected $relationsToFetch;

    protected $allowed = array(
        E::ORDER,
        E::REFUND,
        E::PAYMENT,
        E::SETTLEMENT,
        E::TRANSACTION,
        E::MERCHANT
    );

    public function __construct(string $entity)
    {
        parent::__construct();

        //
        // For linked account report, the entity is exposed as 'account' but is
        // the merchant entity.
        // @todo: Change this when account onboarding goes live.
        //
        if ($entity === 'account')
        {
            $entity = 'merchant';
        }

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

        $signedUrl = $this->createFileAndSave($fullpath, $fileName);

        if (file_exists($fullpath) === true)
        {
            unlink($fullpath);
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

        if (($this->entity === E::MERCHANT) and
            ($this->merchant->isMarketplace() === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Exporting this data is not allowed for the merchant');
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

    protected function createFileAndSave($filePath, $fileName)
    {
        $file = new UploadedFile($filePath, $fileName);

        $creator = new FileStore\Creator;

        $s3FileUrl = $creator->extension(FileStore\Format::ZIP)
                            ->localFile($file)
                            ->name($fileName)
                            ->store(FileStore\Store::S3)
                            ->type(FileStore\Type::MERCHANT_REPORT)
                            ->save()
                            ->getSignedUrl();

        return $s3FileUrl;
    }
}
