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

    public function createCorrectionInvoice(array $input)
    {
        return (new Core)->queueCorrectionInvoiceInvoice($input);
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

        if (isset($input[Balance\Entity::ACCOUNT_NUMBER]) === true)
        {
            $this->processAccountNumber($input);
        }

        $invoices = $this->repo->merchant_invoice->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
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
}
