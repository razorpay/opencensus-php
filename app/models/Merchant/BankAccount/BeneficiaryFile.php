<?php

namespace Models\Merchant\BankAccount;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Models\Merchant\BankAccount;

class BeneficiaryFile
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Beneficiary_File';

    public static $headings = array(
        'Client_Code ',
        'Merchant_Code',
        'Merchant_Name',
        'Merchant_Add_1',
        'Merchant_Add_2',
        'Merchant_Add_3',
        'Merchant_Add_4',
        'Agreement date',
        'Bene_City',
        'Bene_Pin',
        'State',
        'Country',
        'Bene_Email',
        'Bene_Mobile',
        'Agreement expiry date',
        'Agreed rates with Merchant/participating bank',
        'IFSC',
        'Bene_A/c No.',
    );

    public function generate()
    {
        $list = BankAccount\Repository::getAll();

        $data = array();

        foreach ($list as $ba)
        {
            $agreementDate = $ba->getAttribute(BankAccount::CREATED_AT);
            $agreementDate = (new Carbon('Asia/Kolkata'))->setTimestamp($agreementDate);
            $agreementDateText = $agreementDate->format('dMy');
            $agreementExpiryDateText = $agreementDate->addYear()->format('dMy');

            $array = array(
                'Client_Code'           => $ba->getAttribute(BankAccount::BENEFICIARY_CODE),
                'Merchant_Code'         => '',
                'Merchant_Name'         => $ba->getAttribute(BankAccount::BENEFICIARY_NAME),
                'Merchant_Add_1'        => $ba->getAttribute(BankAccount::BENEFICIARY_ADDRESS1),
                'Merchant_Add_2'        => $ba->getAttribute(BankAccount::BENEFICIARY_ADDRESS2),
                'Merchant_Add_3'        => $ba->getAttribute(BankAccount::BENEFICIARY_ADDRESS3),
                'Merchant_Add_4'        => $ba->getAttribute(BankAccount::BENEFICIARY_ADDRESS4),
                'Agreement date'        => $agreementDateText,
                'Bene_City'             => $ba->getAttribute(BankAccount::BENEFICIARY_CITY),
                'Bene_Pin'              => $ba->getAttribute(BankAccount::BENEFICIARY_PIN),
                'State'                 => $ba->getAttribute(BankAccount::BENEFICIARY_STATE),
                'Country'               => $ba->getAttribute(BankAccount::BENEFICIARY_COUNTRY),
                'Bene_Email'            => $ba->getAttribute(BankAccount::BENEFICIARY_EMAIL),
                'Bene_Mobile'           => $ba->getAttribute(BankAccount::BENEFICIARY_MOBILE),
                'Agreement expiry date' => $agreementExpiryDateText,
                'Agreed rates with Merchant/participating bank' => '30000000',
                'IFSC'                  => $ba->getAttribute(BankAccount::IFSC_CODE),
                'Bene_A/c No.'          => $ba->getAttribute(BankAccount::ACCOUNT_NUMBER),
            );

            array_push($data, $array);
        }

        $urlExcel = $this->writeToExcelFile($data, $this->getFileToWriteNameWithoutExt());

        $txt = $this->generateText($textData);

        $urlText = $this->writeToTextFile($txt);

        return [$urlText, $urlExcel];
    }
}
