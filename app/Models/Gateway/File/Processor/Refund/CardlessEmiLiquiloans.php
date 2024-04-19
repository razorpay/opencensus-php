<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Exception\GatewayFileException;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Mozart\PaylaterIcici\RefundFields;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class CardlessEmiLiquiloans extends Base
{
    const FILE_NAME              = 'Razorpay Cancellation ';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::LIQUILOANS_CARDLESS_EMI_REFUND;
    const GATEWAY                = Payment\Gateway::CARDLESS_EMI_LIQUILOANS;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::WALLET;
    const ACQUIRER               = Payment\Processor\CardlessEmi::LIQUILOANS;
    const CARDLESS_EMI            = Payment\Gateway::CARDLESS_EMI;
    const BASE_STORAGE_DIRECTORY = 'Liquiloans/Refund/CardlessEmi/';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $formattedData[] = [
                'LoanId' => ($row['gateway']['gateway_reference_number'])
            ];
        }

        return $formattedData;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
            ->first();

        $date = Carbon::now(Timezone::IST)->subDay()->format('jS F Y');

        $totalAmount = array_reduce($data, function ($carry, $item)
        {
            $carry += ($item['refund']['amount'] / 100);

            return $carry;
        });

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'  => basename($file->getLocation()),
            'signed_url' => $signedUrl,
            'count'      => count($data),
            'amount'     => $totalAmount,
            'date'       => $date,
        ];

        return $mailData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->subDay()->format('d-m-Y');

        // the serial no is hardcoded as the file is generated only once
        return self::FILE_NAME . $date;
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $refundIds
     * @return array
     */
    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        $input = [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY          => static::CARDLESS_EMI,
                    RefundConstants::SCROOGE_GATEWAY_ACQUIRER => static::ACQUIRER,
                    RefundConstants::SCROOGE_CREATED_AT       => [
                        RefundConstants::SCROOGE_GTE => $from,
                        RefundConstants::SCROOGE_LTE => $to,
                    ],
                    RefundConstants::SCROOGE_BASE_AMOUNT => [
                        RefundConstants::SCROOGE_GT => 0,
                    ],
                ],
            ],
            RefundConstants::SCROOGE_COUNT => $this->fetchFromScroogeCount,
        ];

        if (empty($refundIds) === false) {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function shouldRefundsBeFetchedFromScrooge(): bool
    {
        return true;
    }

    protected function addNbplusGatewayEntitiesToDataWithNbPlusPaymentIds(array $data, array $nbplusPaymentIds, string $entity): array
    {
        // Fetching NBPlus Payments Gateway Data from NBPlus
        if (empty($nbplusPaymentIds) === false)
        {
            list($nbPlusGatewayEntities, $fetchSuccess) = $this->fetchNbPlusGatewayEntities($nbplusPaymentIds, 'cardless_emi_gateway');

            // Throwing an error in case of NBPlus fetch failure
            if ($fetchSuccess === false)
            {
                throw new GatewayFileException(
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                    [
                        'id' => $this->gatewayFile->getId(),
                    ]
                );
            }

            $data = array_map(function($row) use ($nbPlusGatewayEntities)
            {
                $paymentId = $row['payment']['id'];

                if (isset($nbPlusGatewayEntities[$paymentId]) === true)
                {
                    $row['gateway'] = $nbPlusGatewayEntities[$paymentId];
                }

                return $row;
            }, $data);
        }

        return $data;
    }
}
