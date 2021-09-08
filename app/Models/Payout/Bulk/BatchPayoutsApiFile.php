<?php

namespace RZP\Models\Payout\Bulk;

use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Exception\LogicException;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\Contact\Entity as ContEntity;
use RZP\Models\FundAccount\Entity as FaEntity;
use RZP\Models\BankAccount\Entity as BaEntity;
use RZP\Models\WalletAccount\Entity as WalletEntity;

class BatchPayoutsApiFile extends Base
{
    const FILE_NAME     = 'payouts_batch';

    /**
     * @var array This will store the batch reference ID. Will be initialised in the constructor
     */
    protected $batchReferenceId;

    /**
     * @var array This will store the array of payouts to convert to a csv file. Will be initialised in the constructor
     */
    protected $payoutsArray;

    public function __construct($payoutsArray, $referenceId = null)
    {
        $this->batchReferenceId = $referenceId;
        $this->payoutsArray     = $payoutsArray;
    }

    protected function getInputEntries(Merchant\Entity $merchant)
    {
        $data = [];

        foreach($this->payoutsArray as $payoutData)
        {
            $isCompositePayout = (isset($payoutData[Payout\Entity::FUND_ACCOUNT]) === true);

            if ($isCompositePayout === true)
            {
                $singleRowData = $this->createMappingForCompositePayouts($payoutData);
            }
            else
            {
                $singleRowData = $this->createMappingForNormalPayouts($payoutData);
            }

            $data[] = $singleRowData;
        }

        return $data;
    }

    // Overloading this method to change return content
    public function createAndSaveSampleFile($extension, Merchant\Entity $merchant)
    {
        [$name, $outputFileLocalPath] = $this->createSampleFileWithExtension($extension, $merchant);

        $ufhFile = $this->saveFile($name, $outputFileLocalPath, $extension, $merchant);

        $ufhSignedUrl = $ufhFile->getSignedUrl();

        return [
            self::FILE_ID          => FileStore\Entity::getSignedId($ufhSignedUrl['id']),
            self::SIGNED_URL       => $ufhSignedUrl['url'],
            FileStore\Entity::NAME => $ufhFile->getFullFileName(),
        ];
    }

    /**
     * Inherited from Payout\Bulk\Base.php to add the call addBucketConfigForBatchService().
     * This ensures that the proper bucket and region are picked up while creating the ufh file.
     * Removal of this call will cause the batch to fail at Batch microservice on production.
     *
     * @param string $filePath
     * @param string $ext
     * @param Merchant\Entity $merchant
     *
     * @return FileStore\Creator
     *
     * @throws LogicException
     */
    protected function saveFile(string $uniqueId, string $filePath, string $ext, Merchant\Entity $merchant): FileStore\Creator
    {
        $filePrefix = 'payouts/' . $uniqueId . '/';

        $name = $filePrefix . static::FILE_NAME;

        $ufh = new FileStore\Creator;

        $ufh->localFilePath($filePath)
            ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][0])
            ->name($name)
            ->extension($ext)
            ->merchant($merchant)
            ->type(FileStore\Type::PAYOUT_SAMPLE)
            ->addBucketConfigForBatchService(FileStore\Type::BATCH_SERVICE);

        return $ufh->save();
    }

    protected function createMappingForCompositePayouts($payoutData)
    {
        return [
            Batch\Header::RAZORPAYX_ACCOUNT_NUMBER => $payoutData[Payout\Entity::ACCOUNT_NUMBER],
            Batch\Header::PAYOUT_AMOUNT            => $payoutData[Payout\Entity::AMOUNT],
            Batch\Header::PAYOUT_CURRENCY          => $payoutData[Payout\Entity::CURRENCY],
            Batch\Header::PAYOUT_MODE              => $payoutData[Payout\Entity::MODE],
            Batch\Header::PAYOUT_PURPOSE           => $payoutData[Payout\Entity::PURPOSE],
            Batch\Header::PAYOUT_NARRATION         => $payoutData[Payout\Entity::NARRATION] ?? '',
            Batch\Header::PAYOUT_REFERENCE_ID      => $payoutData[Payout\Entity::REFERENCE_ID] ?? '',
            Batch\Header::FUND_ACCOUNT_ID          => '',
            Batch\Header::FUND_ACCOUNT_TYPE        => $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::ACCOUNT_TYPE],

            Batch\Header::FUND_ACCOUNT_NAME
                =>  $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::BANK_ACCOUNT][BaEntity::NAME] ??
                    $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::WALLET][BaEntity::NAME] ??
                    '',
            Batch\Header::FUND_ACCOUNT_IFSC
                =>  $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::BANK_ACCOUNT][BaEntity::IFSC] ??
                    '',
            Batch\Header::FUND_ACCOUNT_NUMBER
                =>  $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::BANK_ACCOUNT][BaEntity::ACCOUNT_NUMBER] ??
                    '',
            Batch\Header::FUND_ACCOUNT_VPA
                =>  $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::VPA][VpaEntity::ADDRESS] ??
                    '',
            Batch\Header::FUND_ACCOUNT_PHONE_NUMBER
                =>  $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::WALLET][WalletEntity::PHONE] ??
                    '',
            Batch\Header::FUND_ACCOUNT_EMAIL
                =>  $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::WALLET][WalletEntity::EMAIL] ??
                    '',

            Batch\Header::CONTACT_NAME_2
                => $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::CONTACT][ContEntity::NAME],
            Batch\Header::CONTACT_EMAIL_2
                => $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::CONTACT][ContEntity::EMAIL] ?? '',
            Batch\Header::CONTACT_MOBILE_2
                => $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::CONTACT][ContEntity::CONTACT] ?? '',
            Batch\Header::CONTACT_TYPE
                => $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::CONTACT][ContEntity::TYPE] ?? '',
            Batch\Header::CONTACT_REFERENCE_ID
                => $payoutData[Payout\Entity::FUND_ACCOUNT][FaEntity::CONTACT][ContEntity::REFERENCE_ID] ?? '',

            Batch\Header::NOTES_BATCH_REFERENCE_ID => $this->batchReferenceId ?? '',
        ];
    }

    protected function createMappingForNormalPayouts($payoutData)
    {
        return [
            Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => $payoutData[Payout\Entity::ACCOUNT_NUMBER],
            Batch\Header::PAYOUT_AMOUNT             => $payoutData[Payout\Entity::AMOUNT],
            Batch\Header::PAYOUT_CURRENCY           => $payoutData[Payout\Entity::CURRENCY],
            Batch\Header::PAYOUT_MODE               => $payoutData[Payout\Entity::MODE],
            Batch\Header::PAYOUT_PURPOSE            => $payoutData[Payout\Entity::PURPOSE],
            Batch\Header::PAYOUT_NARRATION          => $payoutData[Payout\Entity::NARRATION] ?? '',
            Batch\Header::PAYOUT_REFERENCE_ID       => $payoutData[Payout\Entity::REFERENCE_ID] ?? '',
            Batch\Header::FUND_ACCOUNT_ID           => $payoutData[Payout\Entity::FUND_ACCOUNT_ID],
            Batch\Header::FUND_ACCOUNT_TYPE         => '',
            Batch\Header::FUND_ACCOUNT_NAME         => '',
            Batch\Header::FUND_ACCOUNT_IFSC         => '',
            Batch\Header::FUND_ACCOUNT_NUMBER       => '',
            Batch\Header::FUND_ACCOUNT_VPA          => '',
            Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
            Batch\Header::FUND_ACCOUNT_EMAIL        => '',
            Batch\Header::CONTACT_NAME_2            => '',
            Batch\Header::CONTACT_EMAIL_2           => '',
            Batch\Header::CONTACT_MOBILE_2          => '',
            Batch\Header::CONTACT_TYPE              => '',
            Batch\Header::CONTACT_REFERENCE_ID      => '',
            Batch\Header::NOTES_BATCH_REFERENCE_ID  => $this->batchReferenceId ?? '',
        ];
    }
}
