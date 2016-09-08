<?php

namespace RZP\Models\BankAccount;

use Carbon\Carbon;
use RZP\Models\BankAccount;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;
use RZP\Error\ErrorCode;
use RZP\Exception;

class BeneficiaryFile2
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Beneficiary_File';

    const DEFAULT_PRICING_RATE = 30000000;

    public static $headings = array(
        'Client_Code',
        'Bene_Code',
        'Bene Name',
        'Bene Add 1',
        'Bene Add 2',
        'Bene Add 3',
        'Bene Add 4',
        'Bene Add 5',
        'Bene_City',
        'Bene_Pin',
        'State',
        'Country',
        'Bene_Email',
        'Bene_Mobile',
        'Bene_Tel',
        'Bene_Fax',
        'IFSC',
        'Bene_A/c No',
    );

    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();
    }

    public function generate()
    {
        $list = (new BankAccount\Repository)->getAllActivatedMerchantAccountsOrderedByCreatedAt();

        $data = array();

        foreach ($list as $ba)
        {
            $array = array(
                'Client_Code'           => $ba->getAttribute(BankAccount\Entity::ID),
                'Bene_Code'             => '',
                'Bene_Name'             => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_NAME),
                'Bene_Add_1'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS1),
                'Bene_Add_2'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS2),
                'Bene_Add_3'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS3),
                'Bene_Add_4'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_ADDRESS4),
                'Bene_Add_5'            => '',
                'Bene_City'             => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_CITY),
                'Bene_Pin'              => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_PIN),
                'State'                 => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_STATE),
                'Country'               => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_COUNTRY),
                'Bene_Email'            => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_EMAIL),
                'Bene_Mobile'           => $ba->getAttribute(BankAccount\Entity::BENEFICIARY_MOBILE),
                'Bene_Tel'              => '',
                'Bene_Fax'              => '',
                'IFSC'                  => $ba->getAttribute(BankAccount\Entity::IFSC_CODE),
                'Bene_A/c No.'          => "'".$ba->getAttribute(BankAccount\Entity::ACCOUNT_NUMBER),
            );

            array_push($data, $array);
        }

        $urlExcel = $this->writeToExcelFile($data, $this->getFileToWriteNameWithoutExt());
        $fullpath = $this->getExcelFullFilePath();
        $merchantsCount = count($list);

        $this->sendKotakBeneficiaryFileMail($fullpath, $merchantsCount);

        return ['url' => $fullpath];
    }

    protected function sendKotakBeneficiaryFileMail($fullpath, $merchantsCount)
    {
        $data['body'] = 'Please find attached updated beneficiary file for ' .
                        'Razorpay and kindly update it on your end.' .
                        'Beneficiaries Count is '. $merchantsCount .' .';

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
