<?php

namespace RZP\Models\FundTransfer\Hdfc;

use Mail;
use Carbon\Carbon;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Base;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\FundTransfer\Base\Beneficiary\FileProcessor;

class Beneficiary extends FileProcessor
{
    use FileHandlerTrait;

    protected $channel = Channel::HDFC;

    protected function getData(PublicCollection $bankAccounts): array
    {
        $data = [];

        $emptyRecord = $this->getEmptyArray();

        foreach ($bankAccounts as $ba)
        {
            $array    = $emptyRecord;

            // If there is no city available then `NA` will be filled
            $city = $ba->getBeneficiaryCity() ?? 'NA';

            $ifscCode = $ba->getIfscCode();

            list ($paymentType, $copyFlag) = $this->getPaymentType($ifscCode);

            $beneficiaryName = $ba->getBeneficiaryName();

            $address = $ba->getBeneficiaryAddress1() ?? $city;

            $array[Headings::FLAG] = Constants::ADD;
            $array[Headings::IFSC] = $ifscCode;
            $array[Headings::PAYMENT_TYPE] = $paymentType;
            $array[Headings::BENE_ADDRESS_1] = $this->normalizeString($address, 35);
            $array[Headings::CITY] = $this->normalizeString($city, 35);
            $array[Headings::CREDIT_ACCOUNT] = $ba->getAccountNumber();
            $array[Headings::BENEFICIARY_CODE] = $ba->getId();
            $array[Headings::BENEFICIARY_NAME] = $this->normalizeString($beneficiaryName, 35);
            $array[Headings::BENE_FUNCTION_TYPE] = Constants::BENE_FUNCTION_TYPE;
            $array[Headings::COPY_TO_PAYMENT_TYPE] = $copyFlag;

            $data[] = $array;
        }

        return $data;
    }

    /**
     * Gives payment type of bene and ifentifier for copy payment type
     * based on the bank ifsc code
     *
     * @param string $ifscCode
     *
     * @return array
     */
    protected function getPaymentType(string $ifscCode): array
    {
        $ifscFirstFour = substr($ifscCode, 0, 4);

        //
        // If the destination account is of HDFC then payment mode will be IFT
        // In this case copy_to_payment field will be `N`
        // Because we should always use IFT to for such transfers
        // We can not add the bene to NEFT or RTGS for the same bank
        //
        if ($ifscFirstFour === IFSC::HDFC)
        {
            return [Constants::IFT, Constants::DO_NOT_COPY];
        }

        //
        // For accounts other than HDFC we add the bene for payment type NEFT and RTGS
        // To do this we can give payment type as NEFT and set copy_to_payment filed as `Y`
        // In this case bene account will be allowed for NEFT and RTGS transfers
        //
        return [Constants::NEFT, Constants::COPY_TO_PAYMENT_TYPE];
    }

    protected function generateFile($data): FileStore\Creator
    {
        $fileName = $this->getFileToWriteNameWithoutExt();

        $filePath = 'hdfc/beneficiary/' . $fileName;

        $content  = $this->generateText($data, ',', true);

        $creator  = new FileStore\Creator;

        return $creator->content($content)
                       ->name($filePath)
                       ->store(FileStore\Store::S3)
                       ->type(FileStore\Type::BENEFICIARY_FILE)
                       ->save();
    }

    protected function getFileToWriteNameWithoutExt(): string
    {
        $date = Carbon::now(Timezone::IST)->format('dmy');

        $sequenceNumber = $this->getSequenceNumber();

        $fileName = 'BEN' . $date . '.' . $sequenceNumber;

        return $fileName;
    }

    /**
     * This will generate a number sequence from 996 - 0
     * Every 1-5 min (90 sec) the number will be decremented by 1
     * - Formula : (EOD time in sec - Current time in sec) / 90
     *
     * This is done because we dont have track on number of bene file generated per day.
     * Bank always expects unique file name and sequence number can very from 000 - 999
     *
     * @return string
     */
    protected function getSequenceNumber(): string
    {
        $eodTime = Carbon::now(Timezone::IST)->endOfDay()->getTimestamp();

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $sequenceNumber = round(($eodTime - $currentTime) / 90);

        return str_pad($sequenceNumber, 3, 0);
    }

    /**
     * Gives an array with all fields required for settlement file generation.
     * All the fields are set to null
     * Generated array will be in the order acceptable from the bank.
     *
     * @return array
     */
    protected function getEmptyArray(): array
    {
        $headings = Headings::getBeneficiaryFileHeadings();

        $count    = count($headings);

        return array_combine($headings, array_fill(0, $count, null));
    }

    /**
     * Currently csv file is generated like excel in `creator`. Which will not consider delimiter being part of string.
     * Also base on observation its understood that file should not contain special characters.
     * So this method will take care of those things.
     *
     * {@inheritdoc}
     */
    protected function normalizeString($string, int $length = 0, string $default = 'NA'): string
    {
        if (empty($string) === true)
        {
            return $default;
        }

        $normalizedString = preg_replace("/\r\n|\r|\n|,|'/", ' ', $string);

        if ($length > 0)
        {
            $normalizedString = substr($normalizedString, 0, $length);
        }

        return $normalizedString;
    }
}
