<?php

namespace RZP\Models\Merchant\InternationalIntegration\Emerchantpay;

use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Exception\RecoverableException;
use RZP\Mail\Base\Constants;
use RZP\Models\FileStore;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Merchant\InternationalIntegration\Constant;
use RZP\Models\Payment\Gateway;
use RZP\Models\Settlement\Processor\Base;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Trace\TraceCode;

class EmerchantpayApmRequestFile extends Base\BaseGifuFile
{
    protected $fileToWriteName;
    protected $mailAddress = Constants::MAIL_ADDRESSES[Constants::CROSS_BORDER_TECH];
    protected $jobNameStage = BeamConstants::EMERCHANTPAY_ONBOARDING_STAGE_JOB_NAME;
    protected $jobNameProd = BeamConstants::EMERCHANTPAY_ONBOARDING_PROD_JOB_NAME;
    protected $type = FileStore\Type::APM_ONBOARD_REQUEST_FILE;
    protected $bankName = Gateway::EMERCHANTPAY;
    protected $store = FileStore\Store::LOCAL;

    const MAF = "Merchant Application Form";
    const IFTM = "Information for the Merchant";

    protected static $paymentMethodName = [
      Gateway::TRUSTLY  => 'Trustly',
      Gateway::POLI     => 'Poli (Australia)',
      Gateway::GIROPAY  => 'Giropay (Germany)',
      Gateway::SOFORT   => 'Sofort',
    ];

    protected static $markets = [
        'UK', 'Poland', 'Germany',
        'Netherlands', 'Sweden',
        'Austria', 'Denmark',
        'US', 'Australia', 'Rest of The World',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->transferMode = Base\TransferMode::SFTP;
        $this->parentFolder = 'onboarding';
    }

    protected function customFormattingForFile($path,FileStore\Creator $creator = null)
    {
        // Do nothing
    }

    /**
     * @throws RecoverableException
     */
    public function getGifuData($input, $from, $to)
    {
        $data = [];
        $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($input['merchant_id']);
        $mii = $this->repo->merchant_international_integrations
            ->getByMerchantIdAndIntegrationEntity($input['merchant_id'], Gateway::EMERCHANTPAY);
        $owners = $this->repo->merchant_owner_details
            ->getByMerchantIdAndGateway($input['merchant_id'], Gateway::EMERCHANTPAY);

        $this->validateEntities($mii, $owners);

        $paymentMethods = array_filter($mii->getPaymentMethods(),
            function ($i) { return ($i['terminal_request_sent'] && !$i['file_request_sent']); });

        $paymentMethods = array_column($paymentMethods, 'instrument');

        if(count($paymentMethods) === 0)
        {
            return $data;
        }

        try {
            $this->createApplicantCompanyDetailsSection($data, $merchant, $mii->getNotes());
            $this->createPaymentMethodsSection($data, $paymentMethods);
            $this->createBusinessOverviewSection($data, $mii->getNotes(), $paymentMethods);
            $this->createWebsiteDetailsSection($data, $merchant, $merchantDetail);
            $this->createManagementAndOwnershipSection($data, $owners);
        }
        catch (\Throwable $e)
        {
            throw new RecoverableException(TraceCode::ERROR_EXCEPTION, Logger::ERROR, $e);
        }

        $this->fileToWriteName = 'MAF_Razorpay_' . $input['merchant_id'] . '_' .
            Carbon::now(Timezone::IST)->isoFormat('YYYYMMDD');

        return $data;
    }

    protected function createApplicantCompanyDetailsSection(&$data, $merchant, $miiNotes)
    {
        array_push($data,
            [self::MAF => 'Applicant Company Details', self::IFTM => '']);
        array_push($data,
            [self::MAF => 'Company Name', self::IFTM => $merchant->getName()]);
        array_push($data,
            [self::MAF => 'Date of Incorporation', self::IFTM => $miiNotes['date_of_incorporation']]);
        array_push($data,
            [self::MAF => 'Registration Number', self::IFTM => $miiNotes['registration_number']]);
        array_push($data,
            [self::MAF => 'GST Certificate', self::IFTM => $miiNotes['gst_number']]);
        array_push($data,
            [self::MAF => 'Registered Address', self::IFTM => '']);
        array_push($data,
            [self::MAF => 'Building Name or Number and Street', self::IFTM => $miiNotes['address_line1'] . ' ' . $miiNotes['address_line2']]);
        array_push($data,
            [self::MAF => 'City, Post (PIN) Code', self::IFTM => $miiNotes['city'] . ' ' . $miiNotes['state'] . ' ' . $miiNotes['zipcode']]);
        array_push($data,
            [self::MAF => 'Country', self::IFTM => $miiNotes['country']]);
        array_push($data,
            [self::MAF => '', self::IFTM => '']);
    }

    protected function createPaymentMethodsSection(&$data, $paymentMethods)
    {
        array_push($data,
            [self::MAF => 'Payment Methods', self::IFTM => '']);

        foreach ($paymentMethods as $paymentMethod)
        {
            if(isset(self::$paymentMethodName[$paymentMethod]))
            {
                array_push($data,
                    [self::MAF => self::$paymentMethodName[$paymentMethod], self::IFTM => '']);
            }
        }
        array_push($data,
            [self::MAF => '', self::IFTM => '']);
    }

    protected function createBusinessOverviewSection(&$data, $miiNotes, $paymentMethods)
    {
        array_push($data,
            [self::MAF => 'Business Overview', self::IFTM => '']);
        array_push($data,
            [self::MAF => Constant::GOODS_TYPE_DESC, self::IFTM => $miiNotes['service_offered']]);
        array_push($data,
            [self::MAF => Constant::PHYSICAL_DELIVERY_DESC, self::IFTM => $miiNotes['physical_delivery']]);
        array_push($data,
            [self::MAF => Constant::AVG_DELIVERY_DAYS_DESC, self::IFTM => $miiNotes['average_delivery_in_days']]);
        array_push($data,
            [self::MAF => 'Estimated transaction value (in EUR)', self::IFTM => '']);
        array_push($data,
            [self::MAF => 'Minimum', self::IFTM => 10]);
        array_push($data,
            [self::MAF => 'Average', self::IFTM => 20]);
        array_push($data,
            [self::MAF => 'Maximum', self::IFTM => 1000]);
        array_push($data,
            [self::MAF => 'Percentage of sales from market', self::IFTM => '']);

        foreach (self::$markets as $market)
        {
            array_push($data,
                [self::MAF => $market, self::IFTM => '50 Lacs INR']);
        }

        $currencies = [];
        foreach ($paymentMethods as $paymentMethod){
            $currencies = array_merge($currencies, Gateway::getSupportedCurrenciesByApp($paymentMethod));
        }
        $currencies = array_unique($currencies);

        array_push($data,
            [self::MAF => 'Currencies', self::IFTM => implode(', ', $currencies)]);
        array_push($data,
            [self::MAF => 'Processing Currency', self::IFTM => implode(', ', $currencies)]);
        array_push($data,
            [self::MAF => '', self::IFTM => '']);
    }

    protected function createWebsiteDetailsSection(&$data, $merchant, $merchantDetail)
    {
        array_push($data,
            [self::MAF => 'Website Details', self::IFTM => '']);
        array_push($data,
            [self::MAF => 'Applicant website URL(s)', self::IFTM => $merchantDetail->getWebsite()]);
        array_push($data,
            [self::MAF => 'Descriptor', self::IFTM => substr($merchant->getName(), 0, 18)]);
        array_push($data,
            [self::MAF => 'CS phone number/email', self::IFTM => $merchantDetail->getContactEmail()]);
        array_push($data,
            [self::MAF => '', self::IFTM => '']);
    }

    protected function createManagementAndOwnershipSection(&$data, $owners)
    {
        array_push($data,
            [self::MAF => 'Management and Ownership', self::IFTM => '']);
        foreach ($owners as $owner)
        {
            $od = $owner->getOwnerDetails();
            array_push($data,
                [self::MAF => 'First Name', self::IFTM => $od['first_name']]);
            array_push($data,
                [self::MAF => 'Last Name', self::IFTM => $od['last_name']]);
            array_push($data,
                [self::MAF => 'Position', self::IFTM => $od['position']]);
            array_push($data,
                [self::MAF => 'Date of Birth', self::IFTM => $od['date_of_birth']]);
            array_push($data,
                [self::MAF => 'Passport Number', self::IFTM => $od['passport_number']]);
            array_push($data,
                [self::MAF => 'Aadhaar Number', self::IFTM => $od['aadhaar_number']]);
            array_push($data,
                [self::MAF => 'PAN Number', self::IFTM => $od['pan_number']]);
            array_push($data,
                [self::MAF => 'Current Home Address', self::IFTM => '']);
            array_push($data,
                [self::MAF => 'Building Name or Number and Street', self::IFTM => $od['address_line1'] . ' ' . $od['address_line2']]);
            array_push($data,
                [self::MAF => 'City, Post (PIN) Code', self::IFTM => $od['city'] . ' ' . $od['state'] . ' ' . $od['zipcode']]);
            array_push($data,
                [self::MAF => 'Country', self::IFTM => $od['country']]);
            array_push($data,
                [self::MAF => '% Ownership', self::IFTM => $od['ownership_percentage']]);
            array_push($data,
                [self::MAF => '', self::IFTM => '']);
        }
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');
        $bucketType = Bucket::getBucketConfigName($this->type, $this->env);
        return $config[$bucketType];
    }

    protected function validateEntities($mii, $owners)
    {
        // validate payment methods
        $paymentMethods = $mii->getPaymentMethods();
        if(count($paymentMethods) === 0)
        {
            throw new BadRequestException("EMPTY_PAYMENT_METHODS_DATA");
        }

        // validate merchant_info
        $merchantInfo = $mii->getNotes();
        if(count($merchantInfo) === 0)
        {
            throw new BadRequestException("EMPTY_MERCHANT_INFO_DATA");
        }

        foreach ($owners as $owner)
        {
            $od = $owner->getOwnerDetails();
            if(count($od) === 0)
            {
                throw new BadRequestException("EMPTY_OWNER_DETAIL_DATA");
            }
        }
    }
}
