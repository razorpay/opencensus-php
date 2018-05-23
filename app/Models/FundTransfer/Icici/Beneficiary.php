<?php

namespace RZP\Models\FundTransfer\Icici;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Mail\Banking\BeneficiaryFile as BeneficiaryFileMail;
use RZP\Models\FundTransfer\Base\Beneficiary as BaseBeneficiary;

class Beneficiary extends BaseBeneficiary
{
    protected $id;

    protected $channel = Channel::ICICI;

    public function __construct()
    {
        parent::__construct();

        $this->id = Base\UniqueIdEntity::generateUniqueId();
    }


    /**
     * @param $bankAccounts
     * @param array $input
     *
     * @return array
     * @return array with keys 'signed_url'
     *                         'local_file_path'
     *                         'file_name'
     *                         'merchants_count'
     */
    public function register(PublicCollection $bankAccounts, array $input = []): array
    {
        $rows = $this->getData($bankAccounts);

        $txt = $this->getTxt($rows);

        $file = $this->generateFile($txt);

        $merchantCount = count($rows);

        $response = $this->makeResponse($file, $merchantCount);

        $recipientEmails = $input[BankAccount::RECIPIENT_EMAILS] ?? null;

        $mailData = array_merge($response, [BankAccount::RECIPIENT_EMAILS => $recipientEmails]);

        $this->sendEmail($mailData);

        return $response;
    }

    protected function getData(PublicCollection $bankAccounts): array
    {
        $rows = [];

        foreach ($bankAccounts as $ba)
        {
            $address = $ba->source->merchantDetail->getBusinessRegisteredAddress();

            // Removes line break from the string
            $address = $this->normalizeString($address);

            $address = substr($address, 0, 30);

            $row = [
                'A',
                $ba->getId(),
                $ba->getBeneficiaryName(),
                $ba->getAccountNumber(),
                'vendor',
                $address,
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    protected function getTxt(array $rows): string
    {
        $txt = '';

        $totalElements = count($rows);

        foreach ($rows as $index => $row)
        {
            $txt .= implode('|', $row);

            if ($index < $totalElements - 1)
            {
                //
                // Double quote is required to suggest new line
                // Single quote will NOT work
                //
                $txt .= "\r\n";
            }
        }

        return $txt;
    }

    protected function generateFile($txt): FileStore\Creator
    {
        $fileName = 'icici/outgoing/NRPSS_NRPSSBENEUPLD_' . $this->id;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::TXT)
                        ->content($txt)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->id($this->id)
                        ->metadata($metadata)
                        ->save();

        return $file;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    protected function sendEmail(array $data)
    {
        $beneficiaryFileMail = new BeneficiaryFileMail($data, $this->channel, $data['merchants_count']);

        Mail::queue($beneficiaryFileMail);
    }
}
