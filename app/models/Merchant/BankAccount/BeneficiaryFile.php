<?php

namespace Models\Merchant\BankAccount;

use Carbon\Carbon;
use EE\Error\ErrorCode;
use EE\Exception;
use Models\Merchant\BankAccount;
use Models\Settlement\Kotak\FileHandlerTrait;

class BeneficiaryFile
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Beneficiary_File';

    const DEFAULT_PRICING_RATE = 30000000;

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

    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();
    }

    public function generate()
    {
        $list = (new BankAccount\Repository)->getAllOrderedByCreatedAt();

        $data = array();

        foreach ($list as $ba)
        {
            $agreementDate = $ba->getAttribute(BankAccount\Entity::CREATED_AT);
            $agreementDate = (new Carbon('Asia/Kolkata'))->setTimestamp($agreementDate);
            $agreementDateText = $agreementDate->format('dmY');
            $agreementExpiryDateText = $agreementDate->addYear()->format('dmY');

            $ratesColumnHeader = 'Agreed rates with Merchant/participating bank';
            $array = array(
                'Client_Code'           => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_CODE),
                'Merchant_Code'         => '',
                'Merchant_Name'         => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_NAME),
                'Merchant_Add_1'        => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS1),
                'Merchant_Add_2'        => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS2),
                'Merchant_Add_3'        => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS3),
                'Merchant_Add_4'        => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS4),
                'Agreement date'        => $agreementDateText,
                'Bene_City'             => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_CITY),
                'Bene_Pin'              => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_PIN),
                'State'                 => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_STATE),
                'Country'               => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_COUNTRY),
                'Bene_Email'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_EMAIL),
                'Bene_Mobile'           => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_MOBILE),
                'Agreement expiry date' => $agreementExpiryDateText,
                $ratesColumnHeader      => self::DEFAULT_PRICING_RATE,
                'IFSC'                  => $ba->getAttribute(BankAccount\Entity::IFSC_CODE),
                'Bene_A/c No.'          => $ba->getAttribute(BankAccount\Entity::ACCOUNT_NUMBER),
            );

            array_push($data, $array);
        }

        $urlExcel = $this->writeToExcelFile($data, $this->getFileToWriteNameWithoutExt());
        $fullpath = $this->getExcelFullFilePath();

        $this->sendKotakBeneficiaryFileMail($fullpath);

        return ['url' => $fullpath];
    }

    protected function sendKotakBeneficiaryFileMail($fullpath)
    {
        $data['body'] = 'Please find attached updated beneficiary file for ' .
                        'Razorpay and kindly update it on your end';

        $data['file'] = $fullpath;

        $this->mail->queue('emails.message', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('kotak_beneficiary_file@razorpay.com', 'Razorpay Kotak Beneficiary File');

            $message->subject('Razorpay updated beneficiary file for Kotak');

            $message->to($emails);

            $message->attach($data['file']);
        });
    }
}
