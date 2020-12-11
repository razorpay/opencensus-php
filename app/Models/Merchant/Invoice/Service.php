<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Services\UfhService;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\Traits\ProcessAccountNumber;

class Service extends Base\Service
{
    use ProcessAccountNumber;

    public function createInvoiceEntities(array $input)
    {
        return (new Core)->queueCreateInvoiceEntities($input);
    }

    public function createMultipleInvoiceEntities(array $input)
    {
        (new Core)->createMultipleInvoiceEntities($input);
    }

    public function updateGstin(string $merchantId, array $input): array
    {
        (new Validator)->validateInput('edit_gstin', $input);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $count = (new Core)->updateGstinForInvoice($input, $merchant);

        return ['count' => $count];
    }

    public function requestBankingInvoice(array $input)
    {
        (new Validator)->validateInput(Validator::BANKING_INVOICE_GENERATE, $input);

        $sendEmail = array_pull($input, Entity::SEND_EMAIL);

        $emailAddresses = array_pull($input, Entity::TO_EMAILS);

        list($ufhResponse, $data) = $this->core()->generateInvoiceReport($input);

        $data = array_merge($data, $input);

        if (boolval($sendEmail) === true)
        {
            $emailAddresses = $emailAddresses ?? $this->merchant->getEmail();

            $this->core()->sendInvoiceEmail($ufhResponse[UfhService::FILE_ID],
                                            $data,
                                            $emailAddresses);
            return [
                'file_id' => null,
            ];
        }
        else
        {
            return [
                'file_id' => $ufhResponse[UfhService::FILE_ID],
            ];
        }
    }

    public function fetchMultipleBankingInvoices(array $input)
    {
        $this->merchant->getValidator()->validateBusinessBankingActivated();

        $input[Entity::TYPE] = Type::RX_TRANSACTIONS;

        $invoices = $this->repo->merchant_invoice->fetch($input, $this->merchant->getId());

        $invoices = $invoices->toArrayPublic();

        $invoices = $this->getInvoicesGroupedByMonthAndYear($invoices);

        return [
            'entity' => 'collection',
            'count'  => count($invoices),
            'items'  => $invoices,
        ];
    }

    public function verify(array $input)
    {
        (new Validator)->validateInput(Validator::VERIFY, $input);

        $previousMonthTimestamp = Carbon::now(Timezone::IST)->subMonth(1);

        $year = $input['year'] ?? $previousMonthTimestamp->year;

        $month = $input['month'] ?? $previousMonthTimestamp->month;

        $data = (new Core)->verify($year, $month);

        return $data;
    }

    protected function getInvoicesGroupedByMonthAndYear(array $invoices): array
    {
        $template = [];

        $invoiceData = [];

        foreach ($invoices['items'] as $invoice)
        {
            $month = $invoice[Entity::MONTH];
            $year  = $invoice[Entity::YEAR];

            $template[$year][$month] = [
                Entity::AMOUNT => 0,
                Entity::TAX    => 0,
            ];
        }

        foreach ($invoices['items'] as $invoice)
        {
            $month = $invoice[Entity::MONTH];
            $year  = $invoice[Entity::YEAR];

            $template[$year][$month][Entity::AMOUNT] += $invoice[Entity::AMOUNT];
            $template[$year][$month][Entity::TAX] += $invoice[Entity::TAX];
        }

        foreach ($template as $year => $monthlyData)
        {
            foreach ($monthlyData as $month => $data)
            {
                $invoiceData[] = [
                    Entity::MONTH  => $month,
                    Entity::YEAR   => $year,
                    Entity::AMOUNT => $data[Entity::AMOUNT],
                    Entity::TAX    => $data[Entity::TAX],
                ];
            }
        }

        return $invoiceData;
    }

    public function generationControl($input)
    {
        (new Validator())->validateInput('generation_control', $input);

        $redis = $this->app->redis->Connection('mutex_redis');

        $values = $redis->LRANGE(Constants::MERCHANT_INVOICE_SKIPPED_MIDS_KEY, 0, -1);

        if ($input['action'] === Constants::SHOW_SKIPPED_MIDS_LIST)
        {
            return $values;
        }

        $result = [
            'failed_mids'  => [],
            'success_mids' => [],
        ];

        foreach ($input['merchant_ids'] as $merchantId)
        {
            $skip = 0;

            try
            {
                switch ($input['action'])
                {
                    case Constants::ADD_TO_SKIPPED_MIDS_LIST:
                        if(in_array($merchantId, $values) === true)
                        {
                            $result['failed_mids'][] = $merchantId;

                            $this->trace->info(
                                TraceCode::MERCHANT_INVOICE_GENERATION_CONTROL_FAILED,
                                [
                                    'merchant_id' => $merchantId,
                                    'reason'      => 'merchant is already present in the skipped list',
                                ]);
                            $skip = 1;
                            break;
                        }

                        $redis->LPUSH(Constants::MERCHANT_INVOICE_SKIPPED_MIDS_KEY, $merchantId);
                        break;

                    case Constants::REMOVE_FROM_SKIPPED_MIDS_LIST:
                        if(in_array($merchantId, $values) === false)
                        {
                            $result['failed_mids'][] = $merchantId;

                            $this->trace->info(
                                TraceCode::MERCHANT_INVOICE_GENERATION_CONTROL_FAILED,
                                [
                                    'merchant_id' => $merchantId,
                                    'reason'      => 'merchant is not present in the skipped list',
                                ]);

                            $skip = 1;
                            break;
                        }
                        $redis->LREM(Constants::MERCHANT_INVOICE_SKIPPED_MIDS_KEY, 0, $merchantId);
                        break;
                }
                if($skip === 0)
                {
                    $result['success_mids'][] = $merchantId;
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::MERCHANT_INVOICE_GENERATION_CONTROL_FAILED,
                    [
                       'merchant_id' => $merchantId,
                       'reason'      => 'failed to' . $input['action']. 'to redis skipped list'
                    ]);

                $result['failed_mids'][] = $merchantId;
            }
        }

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_GENERATION_CONTROL_RESULT,
            [
               'input'  => $input,
               'result' => $result,
            ]);

        return $result;
    }
}
