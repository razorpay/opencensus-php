<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as Headings;

class EMandateRegistrationReconFile extends Base\EMandateRegistrationReconFile
{
    use FileHandlerTrait;

    const GATEWAY_STATUS_TO_TOKEN_STATUS_MAP = [
        'success'   => Token\RecurringStatus::CONFIRMED,
        'reject'    => Token\RecurringStatus::REJECTED,
    ];

    protected $fileContents;

    public function process(array $input)
    {
        $file = $input['file'];

        $this->fileContents = $this->parseExcelSheets($file);

        $response = $this->processFileContents();

        $this->trace->info(TraceCode::EMANDATE_REGISTER_RESPONSE, $response);

        return $response;
    }

    protected function processFileContents(): array
    {
        $totalCount = count($this->fileContents);

        $processedCount = 0;

        foreach ($this->fileContents as $row)
        {
            $this->trace->info(
                TraceCode::EMANDATE_REGISTER_RECON_ROW,
                [
                    'gateway'   => 'netbanking_hdfc',
                    'row'       => $row,
                ]);

            try
            {
                $this->updateToken($row);

                $processedCount++;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::EMANDATE_REGISTER_RECON_FAILED
                );
            }
        }

        return ['total_count' => $totalCount, 'processed_count' => $processedCount];
    }

    protected function updateToken(array $row)
    {
        $tokenId = $row[Headings::MANDATE_ID];

        $gatewayTokenStatus = $row[Headings::STATUS] ?? '';
        $tokenStatus = $this->getTokenStatus($gatewayTokenStatus);

        $remark = $row[Headings::REMARK];

        $accountNumber = $row[Headings::CUSTOMER_ACCOUNT_NUMBER];

        $token = $this->repo->token->getInitiatedTokenByIdAndAccountNumber($tokenId, $accountNumber);

        $gatewayData = [
            Token\Entity::RECURRING_STATUS          => $tokenStatus,
            Token\Entity::RECURRING_FAILURE_REASON  => $remark,
        ];

        (new Token\Core)->updateTokenFromNetbankingGatewayData($token, $gatewayData);

        $this->repo->saveOrFail($token);
    }

    protected function getTokenStatus(string $gatewayTokenStatus): string
    {
        $gatewayTokenStatus = strtolower($gatewayTokenStatus);

        if (isset(self::GATEWAY_STATUS_TO_TOKEN_STATUS_MAP[$gatewayTokenStatus]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Unrecognized gateway token status ' . $gatewayTokenStatus);
        }

        return self::GATEWAY_STATUS_TO_TOKEN_STATUS_MAP[$gatewayTokenStatus];
    }
}
