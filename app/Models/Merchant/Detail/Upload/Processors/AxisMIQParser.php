<?php


namespace RZP\Models\Merchant\Detail\Upload\Processors;

use Excel;
use Lib\Gstin;
use RZP\Exception;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity as E;
use RZP\Excel\Import as ExcelImport;
use RZP\Models\Merchant\Detail\Upload\Constants;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\Merchant\AvgOrderValue\Entity as AovEntity;
use RZP\Models\Merchant\Detail\BusinessSubCategoryMetaData;

class AxisMIQParser
{

    const HEADERS = [
        Constants::MERCHANT_LEGAL_NAME,
        Constants::MERCHANT_DBA_NAME,
        Constants::LIVE_URL,
        Constants::CONTACT_NAME,
        Constants::CONTACT_EMAIL,
        Constants::PHONE_NUMBER,
        Constants::ADDRESS,
        Constants::CITY,
        Constants::STATE,
        Constants::PIN_CODE,
        Constants::ENTITY_TYPE,
        Constants::PAN_NUMBER,
        Constants::GST_NUMBER,
        Constants::CATEGORY,
        Constants::SUBCATEGORY,
        Constants::PRODUCT_DESCRIPTION,
        Constants::MIN_TRANSACTION_VALUE,
        Constants::MAX_TRANSACTION_VALUE,
        Constants::BANK_ACC_NUMBER,
        Constants::BANK_ACC_NAME,
        Constants::IFSC_CODE,
        Constants::MCC_CODE
    ];

    const HEADER_TO_ENTITY_NAME_MAPPING = [
        Constants::MERCHANT_LEGAL_NAME       => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_NAME],
        ],
        Constants::MERCHANT_DBA_NAME         => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_DBA],
        ],
        Constants::LIVE_URL                  => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_WEBSITE],
            "formatter"     => "getFormattedWebsite"
        ],
        Constants::CONTACT_NAME              => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::CONTACT_NAME, DetailEntity::PROMOTER_PAN_NAME],
        ],
        Constants::CONTACT_EMAIL  => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::CONTACT_EMAIL],
        ],
        Constants::PHONE_NUMBER              => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::CONTACT_MOBILE],
        ],
        Constants::ADDRESS                   => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_REGISTERED_ADDRESS, DetailEntity::BUSINESS_OPERATION_ADDRESS],
        ],
        Constants::CITY                      => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_REGISTERED_CITY, DetailEntity::BUSINESS_OPERATION_CITY],
        ],
        Constants::STATE                     => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_REGISTERED_STATE, DetailEntity::BUSINESS_OPERATION_STATE],
        ],
        Constants::PIN_CODE                  => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_REGISTERED_PIN, DetailEntity::BUSINESS_OPERATION_PIN],
        ],
        Constants::ENTITY_TYPE               => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_TYPE],
        ],
        Constants::PAN_NUMBER                => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::COMPANY_PAN]
        ],
        Constants::GST_NUMBER                => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::GSTIN]
        ],
        Constants::CATEGORY        => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_CATEGORY]
        ],
        Constants::SUBCATEGORY               => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_SUBCATEGORY]
        ],
        Constants::PRODUCT_DESCRIPTION       => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BUSINESS_MODEL]
        ],
        Constants::MIN_TRANSACTION_VALUE => [
            "entity"        => E::MERCHANT_AVG_ORDER_VALUE,
            "fields"        => [AovEntity::MIN_AOV]
        ],
        Constants::MAX_TRANSACTION_VALUE => [
            "entity"        => E::MERCHANT_AVG_ORDER_VALUE,
            "fields"        => [AovEntity::MAX_AOV]
        ],

        Constants::BANK_ACC_NUMBER       => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BANK_ACCOUNT_NUMBER]
        ],
        Constants::BANK_ACC_NAME         => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BANK_ACCOUNT_NAME]
        ],
        Constants::IFSC_CODE                 => [
            "entity"        => E::MERCHANT_DETAIL,
            "fields"        => [DetailEntity::BANK_BRANCH_IFSC]
        ],
    ];

    const DEFAULTS = [
        DetailEntity::PROMOTER_PAN              => 'AAAPL1234C',
        DetailEntity::COMPANY_PAN               => 'AAACL1234C',
        DetailEntity::COMPANY_CIN               => 'U67190TN2014PTC096978',
        DetailEntity::BUSINESS_CATEGORY         => 'others',
        DetailEntity::BUSINESS_OPERATION_STATE  => Gstin::MH,
        DetailEntity::BUSINESS_REGISTERED_STATE => Gstin::MH
    ];

    private function getReaderType(UploadedFile $file)
    {
        $extension = $file->guessExtension();

        if($extension === 'xls')
        {
            return 'Xls';
        }

        return 'Xlsx';
    }

    protected function extractData(UploadedFile $file)
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(300);
        RuntimeManager::setMaxExecTime(600);

        $filePath = $file->getPath().'/'.$file->getFilename();

        $excelImport = new ExcelImport(2);
        $excelImport->setHeadingType('none');
        $excelReader = ($excelImport)->toArray($filePath, null, $this->getReaderType($file));

        if(empty($excelReader) === true)
        {
            throw new Exception\LogicException("empty data");
        }

        $rawRows = $excelReader[0];
        $rows = [];

        /*
         * dont ask why this was done...
         * but the MIQ format is weird and converting to standard format is pain
         */
        $firstRowIterated = false;
        foreach ($rawRows as $row)
        {
            $rows[] = array_values($row);

            if($firstRowIterated === false)
            {
                $rows[] = array_keys($row);
                $firstRowIterated = true;
            }
        }

        $headers = [];
        $data = [];
        foreach($rows as $row)
        {
            if(count($row) >= 2)
            {
                $headers[] = $row[0];
                $data[] = $row[1];
            }
        }

        return [
            'headers'   => $headers,
            'row'       => $data
        ];
    }

    public function parse(UploadedFile $file): array
    {
        $data = $this->extractData($file);

        $mappedData = $this->getMappedData($data['headers'], $data['row']);

        $mappedData = $this->setDefaultsIfApplicable($mappedData);

        return $mappedData;
    }

    protected function getMappedData(array $headers, array $row)
    {
        $headersLength = count($headers);
        $mappedData = [];

        for($i=0; $i< $headersLength; $i++)
        {
            $header = $headers[$i];
            $value = $row[$i];

            if(isset(self::HEADER_TO_ENTITY_NAME_MAPPING[$header]) === true)
            {
                $mappedEntity = self::HEADER_TO_ENTITY_NAME_MAPPING[$header]['entity'];
                $mappedFields = self::HEADER_TO_ENTITY_NAME_MAPPING[$header]['fields'];

                if(isset(self::HEADER_TO_ENTITY_NAME_MAPPING[$header]['formatter']) === true)
                {
                    $converter = self::HEADER_TO_ENTITY_NAME_MAPPING[$header]['formatter'];
                    $value = $this->$converter($value);
                }

                foreach($mappedFields as $field)
                {
                    if($mappedEntity === E::MERCHANT_DETAIL)
                    {
                        $mappedData[$field] = $value;
                    }
                    else
                    {
                        $mappedData[$mappedEntity][$field] = $value;
                    }
                }
            }
            else {
                if($header === Constants::MCC_CODE)
                {
                    $this->setBusinessCategorySubCategoryFromMcc($value, $mappedData);
                }
            }
        }

        return $mappedData;
    }

    protected function setDefaultForField(string $field, array $data)
    {
        if(isset(self::DEFAULTS[$field]) === true)
        {
            return self::DEFAULTS[$field];
        }

        switch ($field)
        {
            case DetailEntity::BUSINESS_SUBCATEGORY:
                return $this->getDefaultForSubCategory($data);
        }

        return null;
    }

    protected function setDefaultsIfApplicable(array $data)
    {
        foreach (self::HEADER_TO_ENTITY_NAME_MAPPING as $header => $value)
        {
            $mappedEntity = self::HEADER_TO_ENTITY_NAME_MAPPING[$header]['entity'];
            $mappedFields = self::HEADER_TO_ENTITY_NAME_MAPPING[$header]['fields'];

            foreach ($mappedFields as $field)
            {
                if($mappedEntity === E::MERCHANT_DETAIL)
                {
                    if(isset($data[$field]) === false)
                    {
                        $data[$field] = $this->setDefaultForField($field, $data);
                    }
                }
            }
        }

        foreach (self::DEFAULTS as $field => $value)
        {
            if(isset($data[$field]) === false)
            {
                $data[$field] = $value;
            }
        }
        return $data;
    }

    protected function getDefaultForSubCategory(array $data)
    {
        $category = $data[DetailEntity::BUSINESS_CATEGORY] ?? self::DEFAULTS[DetailEntity::BUSINESS_CATEGORY];

        return BusinessCategory::getWhitelistedSubCategory($category);
    }

    protected function setBusinessCategorySubCategoryFromMcc(?string $mccCode, array &$mappedData)
    {
        if(empty($mccCode) === true)
        {
            return;
        }

        foreach (BusinessSubCategoryMetaData::SUB_CATEGORY_METADATA as $subCategory => $metaData)
        {
            if($mccCode === $metaData[MerchantEntity::CATEGORY])
            {
                $mappedData[DetailEntity::BUSINESS_CATEGORY] = $metaData[MerchantEntity::CATEGORY2];
                $mappedData[DetailEntity::BUSINESS_SUBCATEGORY] = $subCategory;
                return;
            }
        }
    }

    protected function getFormattedWebsite($value)
    {
        if(empty($value) === true)
        {
            return $value;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        if(empty($scheme) === true)
        {
            return 'http://' . ltrim($value, '/');
        }
        
        return $value;
    }

}
