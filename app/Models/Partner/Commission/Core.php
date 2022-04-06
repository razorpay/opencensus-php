<?php

namespace RZP\Models\Partner\Commission;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;
use RZP\Constants\HyperTrace;
use RZP\Models\Partner\Metric;
use RZP\Models\Merchant\Detail;
use RZP\Jobs\CommissionCapture;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use RZP\Models\Settlement\Channel;
use RZP\Jobs\CommissionTdsSettlement;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Jobs\CommissionFinanceTriggeredOnHoldClear;
use RZP\Models\Partner\Commission\Invoice as CommissionInvoice;

class Core extends Base\Core
{
    const COMMISSIONS_BULK_CAPTURE_LIMIT = 200;

    const COMMISSIONS_TRANSACTION_FETCH_LIMIT = 5000;

    public function build(
        Base\PublicEntity $source,
        Merchant\Entity $partner,
        PartnerConfig\Entity $partnerConfig,
        array $input = [],
        Transaction\Entity $txn = null): Entity
    {
        $commission = new Entity;

        $commission->build($input);

        $commission->source()->associate($source);

        $commission->partner()->associate($partner);

        $commission->partnerConfig()->associate($partnerConfig);

        $commission->transaction()->associate($txn);

        return $commission;
    }

    /**
     * Creates partner commission entities from a captured payment
     *
     * @param Payment\Entity $payment
     *
     * @return array
     * @throws \RZP\Exception\LogicException
     */
    public function createFromCapturedPayment(Payment\Entity $payment): array
    {
        return $this->createCommission($payment);
    }

    /**
     * Creates partner commission entities from a payout
     *
     * @param Payout\Entity $payout
     *
     * @return array
     * @throws Exception\LogicException
     */
    public function createFromPayout(Payout\Entity $payout) : array
    {
        return $this->createCommission($payout);
    }

    /**
     * Creates partner commission entities from any
     * Entity which implements CommissionSourceInterface
     *
     * @param CommissionSourceInterface $sourceEntity
     *
     * @return array
     * @throws Exception\LogicException
     */
    protected function createCommission(CommissionSourceInterface  $sourceEntity)
    {
        $calculator = new Calculator($sourceEntity);

        if ($calculator->shouldCreateCommission() === false)
        {
            return [];
        }

        $calculator->calculateAndSaveCommission();

        return $calculator->getCommissions();
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Base\PublicCollection
     * @throws \RZP\Exception\BadRequestException
     */
    public function list(Merchant\Entity $merchant, array $input) : Base\PublicCollection
    {
        // resellers should not see transaction commissions data
        (new Merchant\Validator)->validateIsNotResellerPartner($merchant);

        // check to get only logged in partner's commission list
        $input[Entity::PARTNER_ID] = $merchant->getId();

        // add expands to fetch merchant details
        $input[Repository::EXPAND] = [Entity::SOURCE_MERCHANT];

        $commissions = $this->repo->commission->fetch($input);

        $this->trace->count(Metric::COMMISSION_FETCH, $input);

        return $commissions;
    }

    public function clearOnHoldForPartner(Merchant\Entity $partner, array $input): array
    {
        (new Validator)->validateInput('mark_for_settlement', $input);

        Tracer::inspan(['name' => HyperTrace::VALIDATE_TDS_DEFINED], function () use ($partner) {

            $this->validateTdsDefined($partner);
        });

        // For clearing commission, use invoice Id if present.
        if (isset($input[Constants::INVOICE_ID]) === true)
        {
            $invoice = $this->repo->commission_invoice->findByIdAndMerchant($input[Constants::INVOICE_ID], $partner);

            $data = (new CommissionInvoice\Core)->convertMonthAndYearToTimeStamp($invoice->getMonth(), $invoice->getYear());

            $data[Constants::INVOICE_ID] = $input[Constants::INVOICE_ID];

            $attrs = [
                'partnerId'        =>  $partner->getId(),
                'invoiceId'        =>  $data[Constants::INVOICE_ID]
            ];
            Tracer::inspan(['name' => HyperTrace::COMMISSION_TDS_SETTLEMENT, 'attributes' => $attrs], function () use ($partner, $data) {

                CommissionTdsSettlement::dispatch($this->mode, $partner->getId(), $data);
            });
        }
        else
        {
            $attrs = [
                'partnerId'        =>  $partner->getId()
            ];
            Tracer::inspan(['name' => HyperTrace::COMMISSION_FINANCE_TRIGGERED_ONHOLD_CLEAR, 'attributes' => $attrs], function () use ($partner, $input) {

                // finance triggered payout.
                CommissionFinanceTriggeredOnHoldClear::dispatch($this->mode, $partner->getId(), $input);
            });
        }

        return [];
    }

    public function fetchAggregateCommissionDetails(Merchant\Entity $partner, array $input)
    {
        (new Validator)->validateInput('mark_for_settlement', $input);

        $input[Constants::FROM] = $input[Constants::FROM] ?? null;

        if (empty($input[Constants::INVOICE_ID]) === false)
        {
            $invoice = $this->repo->commission_invoice->findByIdAndMerchantId($input[Constants::INVOICE_ID], $partner->getId());

            $totalCommission = $invoice->getGrossAmount() - $invoice->getTaxAmount();
            $totalTax        = $invoice->getTaxAmount();
        }
        else
        {
            $commissionAggregate = $this->fetchAggregateCommissionDataFromHarvester($partner, $input);

            $totalCommission = $commissionAggregate[Constants::TOTAL_COMMISSION];
            $totalTax        = $commissionAggregate[Constants::TOTAL_TAX];
        }

        list($totalTds, $tdsPercentage) = $this->calculateTds($partner, $totalCommission);

        $netAmount = $totalCommission + $totalTax - $totalTds;

        return [
            Constants::TOTAL_TAX        => $totalTax,
            Constants::TOTAL_TDS        => $totalTds,
            Constants::TOTAL_COMMISSION => $totalCommission,
            Constants::TOTAL_NET_AMOUNT => $netAmount,
            Constants::TDS_PERCENTAGE   => ($tdsPercentage/100),
        ];
    }

    public function calculateTds(Merchant\Entity $partner, int $totalCommission): array
    {
        $tdsPercentage = $this->getTdsPercentage($partner);

        $tds = (int) round(($tdsPercentage * $totalCommission) / 10000);

        return [$tds, $tdsPercentage];
    }

    public function getTdsPercentage(Merchant\Entity $partner): int
    {
        $configs = (new PartnerConfig\Core)->fetchAllDefaultConfigsByPartner($partner);

        if ($configs->isEmpty() === true)
        {
            return 0;
        }

        $merchantDetail = (new Detail\Core)->getMerchantDetails($partner);

        $gstin = $merchantDetail->getGstin();
        $gstin = (empty($gstin) === true) ? null : $gstin;

        $promotorPan   = $merchantDetail->getPromoterPan();
        $companyPan    = $merchantDetail->getPan();

        // if company pan, promoter pan and gstin are empty, charge 20% as tds by default.
        // https://jira.corp.razorpay.com/browse/ME-4988
        if (empty($gstin) === true and empty($promotorPan) === true and empty($companyPan) === true)
        {
            return PartnerConfig\Entity::TDS_PERCENTAGE_FOR_MISSING_DETAILS;
        }

        return $configs->first()->getTdsPercentage();
    }

    public function createCommissionTds(Merchant\Entity $partner, int $totalTds)
    {
        // adj should be on yes_bank channel as commission channel is also yes_bank
        $input = [
            Adjustment\Entity::TYPE        => Balance\Type::COMMISSION,
            Adjustment\Entity::AMOUNT      => (-1 * $totalTds), // tds should be debit
            Adjustment\Entity::CURRENCY    => Currency::INR,
            Adjustment\Entity::CHANNEL     => Channel::YESBANK,
            Adjustment\Entity::DESCRIPTION => Constants::ADJUSTMENT_TDS_DESCRIPTION,
        ];

        (new Adjustment\Core)->createAdjustment($input, $partner);
    }

    public function setOnHoldFalse($transactionId): Transaction\Entity
    {
        $result = $this->repo->transaction(function () use ($transactionId)
        {
            $txn = $this->repo->transaction->lockForUpdate($transactionId);

            $txn->setOnHold(false);

            $this->repo->saveOrFail($txn);

            return $txn;
        });

        return $result;
    }

    /**
     * Find all commissions yet to be captured for a partner and insert into queue
     *
     * @param Merchant\Entity $partner
     *
     * @return int
     */
    public function captureByPartner(Merchant\Entity $partner): int
    {
        $commissionIds = $this->repo->commission->getCommissionIdsToBeCaptured($partner->getId());

        $this->trace->info(
            TraceCode::COMMISSION_CAPTURE_BY_PARTNER_REQUEST,
            [
                'partner_id' => $partner->getId(),
                'count'      => count($commissionIds),
            ]);

        $batches = array_chunk($commissionIds, self::COMMISSIONS_BULK_CAPTURE_LIMIT, true);

        $attrs = [
            'partnerId'        =>  $partner->getId()
        ];

        foreach ($batches as $batch)
        {
            CommissionCapture::dispatch($this->mode, $batch);
        }

        return count($commissionIds);
    }

    public function bulkCaptureByPartner(array $input): int
    {
        (new Validator)->validateInput('bulk_capture', $input);

        $count = 0;

        foreach ($input[Constants::PARTNER_IDS] as $partnerId)
        {
            $partner = $this->repo->merchant->findOrFailPublic($partnerId);

            $count += $this->captureByPartner($partner);
        }

        return $count;
    }

    public function capture(Entity $commission): Entity
    {
        return $this->repo->transaction(function() use ($commission)
        {
            $this->trace->info(
                TraceCode::COMMISSION_TRANSACTION_CREATE_REQUEST,
                [
                    'mode' => $this->mode,
                    'id'   => $commission->getId(),
                ]
            );

            // if commission should be captured or not
            if ($this->checkIfProceedCapture($commission) === false)
            {
                return $commission;
            }

            list($txn, $feeSplit) = Tracer::inspan(['name' => HyperTrace::COMMISSIONS_CAPTURE_CORE], function () use ($commission) {

                return (new Transaction\Core)->createTransactionForSource($commission);
            });

            $this->repo->saveOrFail($txn);

            // fee breakup is not created for commission transaction, so skipping the saving of feeSplit

            $this->trace->info(
                TraceCode::COMMISSION_TRANSACTION_CREATED,
                [
                    'id'   => $txn->getKey(),
                    'mode' => $this->mode,
                ]
            );

            // update commission status to captured
            $commission->setStatus(Status::CAPTURED);

            $this->repo->saveOrFail($commission);

            $this->trace->count(Metric::COMMISSION_CAPTURE_TOTAL, $commission->getMetricDimensions());

            return $commission;
        });
    }

    /**
     * @param Merchant\Entity $partner
     *
     * @return bool
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\LogicException
     */
    public function shouldShowAggregateCommissionReportForPartner(Merchant\Entity $partner): bool
    {
        if ($partner->isResellerPartner() === true)
        {
            $activatedSubMerchants = (new Merchant\Core)->fetchActivatedSubMerchantsForPartner($partner);

            if ($activatedSubMerchants->count() < Constants::RESELLER_SUBMERCHANT_LIMIT)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether the commission needs to be captured
     *
     * @param Entity $commission
     *
     * @return bool
     */
    protected function checkIfProceedCapture(Entity $commission): bool
    {
        $traceCode = null;

        if ($commission->isCaptured() === true)
        {
            $traceCode = TraceCode::COMMISSION_TRANSACTION_ALREADY_CAPTURED;
        }

        if ($commission->isRecordOnly() === true)
        {
            $traceCode = TraceCode::COMMISSION_TRANSACTION_SKIPPED_FOR_RECORD_ONLY;
        }

        if ($commission->isSubventionModel() === true)
        {
            $traceCode = TraceCode::COMMISSION_TRANSACTION_SKIPPED_FOR_SUBVENTION;
        }

        if (empty($traceCode) === false)
        {
            $this->trace->info($traceCode, ['id' => $commission->getId()]);

            return false;
        }

        return true;
    }

    protected function fetchAggregateCommissionDataFromHarvester(Merchant\Entity $partner, array $input): array
    {
        // if harvester is mocked, return hardcoded values
        $harvesterClientMock = $this->config->get('applications.harvester.mock');

        if ($harvesterClientMock === true)
        {
            return [
                Constants::TOTAL_TAX        => 36,
                Constants::TOTAL_COMMISSION => 200,
            ];
        }

        $query = (new Analytics)->fetchAggregateCommissionDetailsQuery($input);

        // send mode in query if its only test
        if ($this->mode === Mode::TEST)
        {
            foreach ($query['aggregations'] as $aggregateType => $aggregateQuery)
            {
                $query['aggregations'][$aggregateType]['details']['mode'] = Mode::TEST;
            }
        }

        $query = (new Merchant\Core)->processMerchantAnalyticsQuery($partner->getId(), $query);

        $aggregateData = $this->app['eventManager']->query($query);

        $totalTax               = $aggregateData[Analytics::TOTAL_TAX][Analytics::RESULT][0][Analytics::VALUE];
        $totalCommissionWithTax = $aggregateData[Analytics::TOTAL_COMMISSION_WITH_TAX][Analytics::RESULT][0][Analytics::VALUE];

        $totalCommission = $totalCommissionWithTax - $totalTax;

        return [
            Constants::TOTAL_TAX        => $totalTax,
            Constants::TOTAL_COMMISSION => $totalCommission,
        ];
    }

    protected function validateTdsDefined(Merchant\Entity $partner)
    {
        $configs = (new PartnerConfig\Core)->fetchAllDefaultConfigsByPartner($partner);

        if ($configs->isEmpty() === true)
        {
            throw new Exception\LogicException('Default partner config not found for partner');
        }
    }
}
