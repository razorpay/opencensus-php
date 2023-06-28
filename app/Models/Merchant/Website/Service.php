<?php

namespace RZP\Models\Merchant\Website;

use DB;
use Mail;
use Cache;
use Config;
use Request;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Utility;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\ServerErrorException;
use RZP\Notifications\Onboarding\Events;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Document\Entity as DocEntity;
use RZP\Models\Merchant\Document\Constants as DocConstant;
use RZP\Models\Merchant\Detail\Entity as DEntity;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Merchant\Detail\BusinessSubcategory as Sub;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessConstants;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;
use Illuminate\Support\Facades\File;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\Consent\Entity as ConsentEntity;
use RZP\Models\Merchant\Consent\Constants as ConsentConstants;
use RZP\Models\Merchant\Core as Merchantcore;
use RZP\Models\Merchant\Email\Service as EmailService;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\Service as DetailService;
use RZP\Models\Merchant\Consent\Core as ConsentCore;
use RZP\Models\Merchant\Document\Core as DocumentCore;

class Service extends Base\Service
{
    protected $trace;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    protected $mutex;

    protected $host;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->trace = $this->app[MerchantConstants::TRACE];

        $this->mutex = $this->app[MerchantConstants::API_MUTEX];

        $this->entityRepo = $this->repo->merchant_website;

        $this->host = env(Constants::MERCHANT_POLICIES_SUBDOMAIN);
    }

    public function isMerchantTncApplicable(MerchantEntity $merchant)
    {
        $org = $merchant->getOrgId() ?: $this->app['basicauth']->getOrgId();

        if (array_key_exists($org, DetailConstants::TNC_ORG_ID_EXP_MAP) === false)
        {
            return false;
        }

        if (empty($merchant->merchantDetail->getAttribute(
                DEntity::BUSINESS_WEBSITE)) === false)
        {
            return false;
        }

        if ($merchant->isBusinessBankingEnabled() === true)
        {
            return false;
        }

        $experimentName = DetailConstants::TNC_ORG_ID_EXP_MAP[$org];

        if ((new Merchantcore())->isRazorxExperimentEnable(
                $merchant->getId(),
                $experimentName) === false)
        {
            return false;
        }

        return true;
    }

    public function saveMerchantTnc(array $input)
    {
        if ($this->isMerchantTncApplicable($this->merchant) === true)
        {
            $emailInput = [
                'email' => $input['support_email']
            ];

            unset($input['support_email']);

            (new EmailService)->proxyEditSupportDetails($this->merchant, $emailInput);

            $oldWebsiteDetail = $this->merchant->merchantDetail->merchantWebsite;

            $websiteDetail = (new Core)->createOrEditWebsiteDetails($this->merchant->merchantDetail, $input);

            if (empty($oldWebsiteDetail) === true)
            {

                (new Merchantcore)->appendTag($this->merchant, 'tnc_generated');

                $this->merchant->merchantDetail->setRelation(DEntity::MERCHANT_WEBSITE, $websiteDetail);
            }

            $workflowActions = (new Action\Core)->fetchApprovedActionOnEntityOperation(
                $this->merchant->getId(), 'merchant_detail', Permission\Name::EDIT_ACTIVATE_MERCHANT);

            if ($workflowActions->isNotEmpty() === true)
            {
                $input = [
                    DEntity::ACTIVATION_STATUS => Status::ACTIVATED,
                ];

                (new DetailCore)->updateActivationStatus($this->merchant, $input, $this->merchant);
            }

            return $this->core->getWebsiteDetails($websiteDetail);
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_TNC_NOT_APPLICABLE);
        }
    }

    public function getMerchantTncById($id): array
    {
        $this->trace->info(TraceCode::MERCHANT_TNC_GET_REQUEST, [
            "tnc_id" => $id
        ]);

        $websiteDetail = $this->repo->merchant_website->findOrFailPublic($id);

        $merchantDetail = $websiteDetail->merchantDetail;

        $merchant = $merchantDetail->merchant;

        //it is being used in the foreach loop below
        $merchantEmail = (new EmailService())->proxyGetSupportDetails($websiteDetail->merchantDetail->merchant);

        $publicTncDetails = [
            'link' => (new Core)->getMerchantTncLink($merchant, $id)
        ];

        foreach (DetailConstants::PUBLIC_TNC_DETAILS as $var => $entities)
        {
            foreach ($entities as $entity)
            {
                if (isset(${$var}[$entity]) === true)
                {
                    $publicTncDetails[$entity] = ${$var}[$entity];
                }
                else
                {
                    $publicTncDetails[$entity] = null;
                }
            }
        }

        $businessCategory = $merchantDetail[DEntity::BUSINESS_CATEGORY];

        $businessSubcategory = $merchantDetail[DEntity::BUSINESS_SUBCATEGORY];

        $publicTncDetails[DEntity::BUSINESS_CATEGORY] = BusinessCategory::DESCRIPTIONS[$businessCategory];

        $publicTncDetails[DEntity::BUSINESS_SUBCATEGORY] = Sub::DESCRIPTIONS[$businessSubcategory];

        $this->trace->info(TraceCode::MERCHANT_TNC_GET_REQUEST_SUCCESS, [
            "publicTncDetails" => $publicTncDetails
        ]);

        return $publicTncDetails;
    }

    public function getMerchantTncByMerchantId($merchantId): array
    {
        $this->trace->info(TraceCode::MERCHANT_TNC_GET_REQUEST, [
            "merchant_id" => $merchantId
        ]);

        $merchantDetail = $this->repo->merchant_detail->findByPublicId($merchantId);

        $websiteDetail = $merchantDetail->merchantWebsite;

        if ($websiteDetail === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $merchant = $merchantDetail->merchant;

        //it is being used in the foreach loop below
        $merchantEmail = (new EmailService())->proxyGetSupportDetails($merchant);

        $publicTncDetails = [
            'link' => (new Core)->getMerchantTncLink($merchant, $websiteDetail->getId())
        ];

        foreach (DetailConstants::PUBLIC_TNC_DETAILS as $var => $entities)
        {
            foreach ($entities as $entity)
            {
                if (empty(${$var}[$entity]) === false)
                {
                    $publicTncDetails[$entity] = ${$var}[$entity];
                }
                else
                {
                    $publicTncDetails[$entity] = null;
                }
            }
        }

        $businessCategory = $merchantDetail[DEntity::BUSINESS_CATEGORY];

        $businessSubcategory = $merchantDetail[DEntity::BUSINESS_SUBCATEGORY];

        $publicTncDetails[DEntity::BUSINESS_CATEGORY] = BusinessCategory::DESCRIPTIONS[$businessCategory];

        $publicTncDetails[DEntity::BUSINESS_SUBCATEGORY] = Sub::DESCRIPTIONS[$businessSubcategory];

        $this->trace->info(TraceCode::MERCHANT_TNC_GET_REQUEST_SUCCESS, [
            "publicTncDetails" => $publicTncDetails
        ]);

        return $publicTncDetails;
    }

    public function isWebsiteSectionsApplicable(MerchantEntity $merchant, $admin = false)
    {

        try
        {
            $result = true;

            $businessDetail = optional($merchant->merchantDetail->businessDetail);

            //run experiment from every flow except for admin dashboard
            if ($admin === false)
            {
                if ((new Merchantcore)->isRegularMerchant($merchant) === false)
                {
                    return false;
                }

                if ($merchant->isRazorpayOrgId() === false)
                {

                    return false;
                }

                try

                {
                    $response = $this->app['splitzService']->evaluateRequest([
                                                                                 'id'            => $merchant->getId(),
                                                                                 'experiment_id' => $this->app['config']->get('app.merchant_policies_exp_id'),
                                                                             ]);
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, ['id' => $properties['id'] ?? null]);

                    return false;
                }

                $variant = $response['response']['variant']['name'] ?? null;

                $result = false;

                if (empty($response['response']['variant']['variables']) === false)
                {
                    foreach ($response['response']['variant']['variables'] as $variables)
                    {

                        if ($variables['key'] === 'result')
                        {
                            $result = $variables['value'] === 'on';
                        }

                    }
                }

//                $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, ["banking"            => $merchant->isBusinessBankingEnabled(),
//                                                                       "business_Website"   => $merchant->merchantDetail->getAttribute(DEntity::BUSINESS_WEBSITE),
//                                                                       "additional_Website" => $merchant->merchantDetail->getAttribute(DEntity::ADDITIONAL_WEBSITES),
//                                                                       "playstore"          => $businessDetail->getAppstoreUrl(),
//                                                                       "appstore"           => $businessDetail->getPlaystoreUrl(),
//                                                                       "variant"            => $variant,
//                                                                       "result"             => $result,
//                                                                       "response"           => $response
//                ]);

                $result = ($result or ($variant === 'enable'));
            }

            if ($result === true)
            {
                if (empty($merchant->merchantDetail->getAttribute(
                        DEntity::BUSINESS_WEBSITE)) === false)
                {
                    return true;
                }

                //check additional website only for admin dashboard
                if (empty($merchant->merchantDetail->getAttribute(
                        DEntity::ADDITIONAL_WEBSITES)) === false and
                    $admin === true)
                {
                    return true;
                }

                if (empty($businessDetail->getAppstoreUrl()) === false)
                {
                    return true;
                }
                if (empty($businessDetail->getPlaystoreUrl()) === false)
                {
                    return true;
                }
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::WEBSITE_SECTION_ERROR, ['id' => $merchant->getId()]);

        }

        return false;
    }

    public function getMerchantWebsiteSection()
    {
        if ($this->isWebsiteSectionsApplicable($this->merchant) === false)
        {
            return ["isWebsiteSectionsApplicable" => false,
                    "isGracePeriodApplicable"     => false
            ];
        }
        $merchantDetail = $this->merchant->merchantDetail;

        $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchantDetail->getMerchantId());

        if (empty($websiteDetail) === true)
        {
            return ["isWebsiteSectionsApplicable" => true,
                    "isGracePeriodApplicable"     => false
            ];
        }

        return $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchantDetail);

    }

    /**
     * @param array $input
     *
     * @return mixed
     * @throws BadRequestException
     * @throws ServerErrorException
     */
    public function saveMerchantWebsiteSection(array $input)
    {
        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, [
            "input"       => $input,
            "merchant_id" => $this->merchant->getId()
        ]);

        if ($this->isWebsiteSectionsApplicable($this->merchant) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        (new Validator())->validateInput('save_merchant_section', $input);

        $merchantDetail = $this->merchant->merchantDetail;

        $input = $this->validateSaveMerchantSectionData($input, $this->merchant->getId());

        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, [
            "input"       => $input,
            "merchant_id" => $this->merchant->getId()
        ]);

        if (array_key_exists(Entity::ADDITIONAL_DATA, $input) === true)
        {
            $emailInput = [];

            if (isset($input[Entity::ADDITIONAL_DATA][Constants::SUPPORT_EMAIL]) === true)
            {
                $emailInput['email'] = $input[Entity::ADDITIONAL_DATA][Constants::SUPPORT_EMAIL];
            }

            if (isset($input[Entity::ADDITIONAL_DATA][Constants::SUPPORT_PHONE]) === true)
            {
                $emailInput['phone'] = $input[Entity::ADDITIONAL_DATA][Constants::SUPPORT_PHONE];
            }

            unset($input[Entity::ADDITIONAL_DATA]);

            (new EmailService)->proxyEditSupportDetails($this->merchant, $emailInput);
        }

        //website input : {shipping_period" : "0-7 days", "refund_period : "1-2 days"}

        $isResponseUpdatedInPgos = false;

        try
        {
            $commonKeysValues = array_intersect_key($input, Constants::COMMON_QUESTIONS_IN_WEBSITE_POLICY_AND_BMC);

            $this->trace->info(TraceCode::COMMON_QUESTIONS_IN_WEBSITE_POLICY_AND_BMC, [
                "commonKeyValues"  => $commonKeysValues,
                "merchant_id"      => $this->merchant->getId()
            ]);

            if(empty($commonKeysValues) === false)
            {
                $this->saveMerchantBMCResponse($commonKeysValues);

                $isResponseUpdatedInPgos = true;
            }

            $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetail, $input);
        }
        catch (\Throwable $e)
        {
            $this->raiseAlertAndThrowError($isResponseUpdatedInPgos, $e);
        }

        return $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchantDetail);

    }

    private function getAllMerchantWebsites($merchantDetails)
    {
        $urls = [];

        $businessDetails = optional($merchantDetails->businessDetail);

        if (empty($businessDetails->getPlaystoreUrl()) === false)
        {
            $urls[trim(strtolower($businessDetails->getPlaystoreUrl()), '/')] = BusinessConstants::PLAYSTORE_URL;
        }
        if (empty($businessDetails->getAppstoreUrl()) === false)
        {
            $urls[trim(strtolower($businessDetails->getAppstoreUrl()), '/')] = BusinessConstants::APPSTORE_URL;
        }

        if (empty($merchantDetails->getWebsite()) === false)
        {
            $urls[trim(strtolower($merchantDetails->getWebsite()), '/')] = Constants::WEBSITE;
        }

        if (empty($merchantDetails->getAdditionalWebsites()) === false)
        {
            foreach ($merchantDetails->getAdditionalWebsites() as $url)
            {
                $urls[trim(strtolower($url), '/')] = $this->core->getUrlType($url);
            }
        }

        return $urls;
    }

    public function getUrls($merchantDetails)
    {
        $urls = [];

        $businessDetails = optional($merchantDetails->businessDetail);

        if (empty($merchantDetails->getAdditionalWebsites()) === false)
        {
            foreach ($merchantDetails->getAdditionalWebsites() as $url)
            {
                $additionalWebsiteUrl = trim(strtolower($url), '/');

                array_push($urls, $additionalWebsiteUrl);
            }
        }

        $businessWebsiteUrl = trim(strtolower($merchantDetails->getWebsite()), '/');

        $appStoreUrl = trim(strtolower($businessDetails->getAppstoreUrl()), '/');

        $playStoreUrl = trim(strtolower($businessDetails->getPlaystoreUrl()), '/');

        array_push($urls, $businessWebsiteUrl);

        array_push($urls, $appStoreUrl);

        array_push($urls, $playStoreUrl);

        return $urls;
    }

    public function createResponse($response, $websiteDetail, $merchantDetails)
    {

        try
        {
            $response[Constants::STATUS] = $this->getWebsiteStatus($merchantDetails, $websiteDetail);
            $response["isWebsiteSectionsApplicable"] = true;
            $response["isGracePeriodApplicable"]     = false;

            $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, ["response"    => $response,
                                                                   "merchant_id" => $merchantDetails->getMerchantId()]);

            //generate signed_url for admin documents
            $this->generateMerchantWebsiteResponse($merchantDetails, $response);

            //generate signed_url for admin documents
            $this->generateAdminWebsiteResponse($merchantDetails, $response);

            //generate support details
            $this->generateSupportDetails($merchantDetails, $response);

        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                "error"       => $e->getMessage(),
                "merchant_id" => $merchantDetails->getId()
            ]);
        }

        $this->trace->info(TraceCode::WEBSITE_SECTION_RESPONSE, [
            "response"    => $response,
            "merchant_id" => $merchantDetails->getId()
        ]);

        return $response;
    }

    private function generateSupportDetails($merchantDetails, array &$response)
    {
        try
        {

            $merchant = $this->repo->merchant->findByPublicId($merchantDetails->getMerchantId());

            $merchantEmail = (new EmailService())->proxyGetSupportDetails($merchant);

            $response[Entity::ADDITIONAL_DATA] = [
                Constants::SUPPORT_PHONE => $merchantEmail['phone'] ?? '',
                Constants::SUPPORT_EMAIL => $merchantEmail['email'] ?? ''
            ];

        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                "error"       => $e->getMessage(),
                "merchant_id" => $merchantDetails->getId()
            ]);
        }
    }

    private function generateMerchantWebsiteResponse($merchantDetails, array &$response)
    {
        try
        {
            //"merchant_website_details": {
            //           "contact_us": {
            //               "playstore_url": {
            //                   "https://play.google.com/store/apps/details?id=com.Slack&hl=en_IN&gl=US": {
            //                       "document_id": "JyJ2aph3msZl9r"
            //                     }
            //               },
            //               "updated_at": 1658906510
            //           }
            //     }

            ////generate signed_url for merchant documents
            if (empty($response[Entity::MERCHANT_WEBSITE_DETAILS]) === false and
                is_array($response[Entity::MERCHANT_WEBSITE_DETAILS]) === true)
            {
                foreach ($response[Entity::MERCHANT_WEBSITE_DETAILS] as $sectionName => $sectionData)
                {
                    //$sectionName : "contact_us"
                    //$sectionData : {
                    //               "playstore_url": {
                    //                   "https://play.google.com/store/apps/details?id=com.Slack&hl=en_IN&gl=US": {
                    //                       "document_id": "JyJ2aph3msZl9r"
                    //                     }
                    //               },
                    //               "updated_at": 1658906510
                    //           }
                    if (empty($sectionData) === false and
                        is_array($sectionData) === true)
                    {

                        //merchant to have at-least one published url to be eligible for grace period
                        if (isset($sectionData[Constants::PUBLISHED_URL]) and
                            $sectionData[Constants::STATUS] === Constants::SUBMITTED and
                            $sectionData[Constants::SECTION_STATUS] === 3
                        )
                        {
                            $response["isGracePeriodApplicable"] = true;
                        }

                        foreach ($sectionData as $urlType => $websitesData)
                        {

                            // $sectionName : "contact_us"
                            // $urlType : playstore_url
                            // $websitesData : {
                            //                   "https://play.google.com/store/apps/details?id=com.Slack&hl=en_IN&gl=US": {
                            //                       "document_id": "JyJ2aph3msZl9r"
                            //                     }
                            //               }
                            if (empty($websitesData) === false and
                                is_array($websitesData) === true)
                            {
                                foreach ($websitesData as $websiteUrl => $websiteData)
                                {

                                    // $sectionName : "contact_us"
                                    // $urlType : playstore_url
                                    // $websiteUrl : "https://play.google.com/store/apps/details?id=com.Slack&hl=en_IN&gl=US"
                                    // $websiteData : "document_id": "JyJ2aph3msZl9r"
                                    if (empty($websiteData) === false)
                                    {

                                        if (($urlType === Constants::APPSTORE_URL or
                                             $urlType === Constants::PLAYSTORE_URL) and
                                            is_array($websitesData) === true)
                                        {
                                            foreach ($websiteData as $key => $value)
                                            {

                                                // $sectionName : "contact_us"
                                                // $urlType : playstore_url
                                                // $websiteUrl : "https://play.google.com/store/apps/details?id=com.Slack&hl=en_IN&gl=US"
                                                // $key : "document_id"
                                                // $value: "JyJ2aph3msZl9r"
                                                if ($key === Constants::DOCUMENT_ID and
                                                    empty($value) === false)
                                                {
                                                    $document = $this->repo->merchant_document->findDocumentById($value);

                                                    if (empty($document) === false and
                                                        empty($document->getAttribute(DocEntity::DELETED_AT)) === true)
                                                    {
                                                        try
                                                        {
                                                            $signedUrl = (new DetailService())->getSignedUrl($document->getFileStoreId(), $merchantDetails->getMerchantId());

                                                            $response[Entity::MERCHANT_WEBSITE_DETAILS][$sectionName][$urlType][$websiteUrl]["signed_url"] = $signedUrl;
                                                        }
                                                        catch (\Exception $e)
                                                        {
                                                            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                                                                "error"       => $e->getMessage(),
                                                                "merchant_id" => $merchantDetails->getId()
                                                            ]);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                "error"       => $e->getMessage(),
                "merchant_id" => $merchantDetails->getId()
            ]);
        }
    }

    private function generateAdminWebsiteResponse($merchantDetails, array &$response)
    {
        try
        {
            if (array_key_exists(Entity::ADMIN_WEBSITE_DETAILS, $response) === true)
            {

                if (empty($response[Entity::ADMIN_WEBSITE_DETAILS]) === false and
                    is_array($response[Entity::ADMIN_WEBSITE_DETAILS]) === true)
                {
                    //"admin_website_details": {
                    //           "appstore_url": {
                    //               "https://apps.apple.com/lol12345.com": {
                    //                   "contact_us": {
                    //                       "document_id": "JtDI0hskLtkqRr"
                    //                   }
                    //               }
                    //           }
                    //       }

                    foreach ($response[Entity::ADMIN_WEBSITE_DETAILS] as $urlType => $data)
                    {
                        // $urlType: "appstore_url"
                        // $data: {
                        //               "https://apps.apple.com/lol12345.com": {
                        //                   "contact_us": {
                        //                       "document_id": "JtDI0hskLtkqRr"
                        //                   }
                        //               }
                        //           }

                        if (empty($data) === false and
                            is_array($data) === true)
                        {
                            foreach ($data as $url => $websiteData)
                            {
                                // $urlType: "appstore_url"
                                // $url: "https://apps.apple.com/lol12345.com":
                                // $websiteData : {
                                //                   "contact_us": {
                                //                       "document_id": "JtDI0hskLtkqRr"
                                //                   }
                                //               }

                                if (empty($websiteData) === false)
                                {

                                    if (($urlType === Constants::APPSTORE_URL or
                                         $urlType === Constants::PLAYSTORE_URL) and
                                        is_array($websiteData))
                                    {
                                        foreach ($websiteData as $sectionName => $sectionData)
                                        {
                                            // $urlType: "appstore_url"
                                            // $url: "https://apps.apple.com/lol12345.com":
                                            // $sectionName : "contact_us":
                                            // $sectionData : {
                                            //                     "document_id": "JtDI0hskLtkqRr"
                                            //                 }

                                            if ($sectionName === Constants::COMMENTS)
                                            {
                                                continue;
                                            }

                                            if (empty($sectionData) === false and
                                                is_array($sectionData))
                                            {
                                                foreach ($sectionData as $key => $value)
                                                {
                                                    // $urlType : "appstore_url"
                                                    // $url : "https://apps.apple.com/lol12345.com":
                                                    // $sectionName : "contact_us":
                                                    // $key : "document_id"
                                                    // $value : "JtDI0hskLtkqRr"

                                                    if ($key === Constants::DOCUMENT_ID and empty($value) === false)
                                                    {
                                                        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, ["key" => $key, "value" => $value]);

                                                        $document = $this->repo->merchant_document->findDocumentById($value);

                                                        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, ["isset(document)" => isset($document),
                                                                                                               "value"           => $value,
                                                                                                               "deleted"         => empty($document->getAttribute(DocEntity::DELETED_AT))]);

                                                        if (isset($document) === true and empty($document->getAttribute(DocEntity::DELETED_AT)) === true)
                                                        {
                                                            try
                                                            {
                                                                $signedUrl = (new DetailService())->getSignedUrl($document->getFileStoreId(), $merchantDetails->getMerchantId());

                                                                $response[Entity::ADMIN_WEBSITE_DETAILS][$urlType][$url][$sectionName]["signed_url"] = $signedUrl;
                                                            }
                                                            catch (\Exception $e)
                                                            {
                                                                $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                                                                    "error"       => $e->getMessage(),
                                                                    "merchant_id" => $merchantDetails->getId()
                                                                ]);

                                                            }
                                                        }
                                                    }
                                                }

                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                "error"       => $e->getMessage(),
                "merchant_id" => $merchantDetails->getId()
            ]);
        }
    }

    public function validateMerchantActivation($merchantDetails, $websiteDetail)
    {
        try
        {
            // Merchants who provide website can later on opt for KLA - and hence their url will be present and
            // has key access will be false , but on admin dashboard the Keyless Auth - will be green tick i.e true

            $urls = $this->getAllMerchantWebsites($merchantDetails);

            $hasKeyAccess = $merchantDetails->merchant->getHasKeyAccess();

            $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, [
                'URLS'           => $urls,
                'WebsiteDetails' => $websiteDetail,
                'KeyAccess'      => $hasKeyAccess
            ]);

            if (empty($urls) === true)
            {
                return false;
            }

            if ($hasKeyAccess === false)
            {
                return false;
            }

            $gracePeriodCheck = false;

            if(empty($websiteDetail) === true and ($this->auth->isAdminAuth() === true))
            {
                if ((new DetailCore())->isProductionEnvironment() === true)
                {
                    throw new BadRequestValidationFailureException(
                        'Please fill in the website details');
                }
            }

            foreach (Constants::MANDATORY_ADMIN_SECTIONS as $sectionName)
            {
                foreach ($urls as $url => $url_type)
                {

                    $sectionUrl = $websiteDetail->getAdminUrl($url_type, $url, $sectionName) ??
                                  $websiteDetail->getPublishedUrl($sectionName);

                    $sectionStatus = $websiteDetail->getSectionStatus($sectionName);

                    $sectionSubmissionStatus = $websiteDetail->getSectionSubmissionStatus($sectionName);

                    $this->trace->info(TraceCode::WEBSITE_SECTION_RESPONSE, [
                        'Section Status' => $sectionStatus,
                        'Section Url'    => $sectionUrl,
                        'Section Submission Status' => $sectionSubmissionStatus
                    ]);

                    if ($sectionStatus === 3 and $sectionSubmissionStatus===Constants::SUBMITTED)
                    {
                        $gracePeriodCheck = true;
                    }

                    if ($sectionName === Constants::CANCELLATION)
                    {
                        $refundSectionName = Constants::REFUND;

                        $refundSectionStatus = $websiteDetail->getSectionStatus($refundSectionName);

                        $refundSectionSubmissionStatus = $websiteDetail->getSectionSubmissionStatus($refundSectionName);

                        if ($refundSectionStatus === 3 and $refundSectionSubmissionStatus===Constants::SUBMITTED)
                        {
                            $gracePeriodCheck = true;
                        }
                        if (empty($sectionUrl) === true and $refundSectionStatus === 3 and $refundSectionSubmissionStatus===Constants::SUBMITTED)
                        {
                            continue;
                        }
                    }

                    if (empty($sectionUrl) === true)
                    {
                        throw new BadRequestValidationFailureException(
                            'Please set value of ' . $sectionName . ' for '. $url_type . ' to create activation workflow');
                    }
                }
            }
            if ($gracePeriodCheck === true)
            {
                $gracePeriod = $websiteDetail->getGracePeriodStatus();

                $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                    'Grace Period' => $gracePeriod
                ]);

                if($gracePeriod === 0)
                {
                    return true;
                }
                elseif ($gracePeriod === 1)
                {
                    return true;
                }
                else
                {
                    throw new BadRequestValidationFailureException(
                        'Please set the value for GracePeriod');
                }
            }
        }
        catch (BadRequestValidationFailureException $e)
        {
            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                "error" => $e->getMessage(),
                "merchant_id" => $merchantDetails->getMerchantId()
            ]);

            throw $e;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                "error"       => $e->getMessage(),
                "merchant_id" => $merchantDetails->getMerchantId()
            ]);
            return true;
        }

        return true;

    }

    //null - which means details are pending from merchant
    //submitted - merchant has submitted all details
    //under_review - details are under verification
    //needs_clarification - action required
    //activated - verified
    private function getWebsiteStatus($merchantDetails, $merchantWebsite)
    {
        $status = optional($merchantWebsite)->getStatus();

        try
        {
            if (empty($status) === false)
            {
                switch ($merchantDetails->getActivationStatus())
                {
                    case Status::NEEDS_CLARIFICATION:

                        if ($this->isNeedsClarificationOnWebsite($merchantDetails))
                        {
                            $status = Status::NEEDS_CLARIFICATION;
                        }
                        else
                        {
                            $status = Status::UNDER_REVIEW;
                        }
                        break;

                    case Status::ACTIVATED:
                        $status = 'approved';
                        break;

                    case Status::REJECTED:
                        $status = Status::UNDER_REVIEW;
                        break;

                    case Status::UNDER_REVIEW:
                    case Status::ACTIVATED_MCC_PENDING:
                    case Status::ACTIVATED_KYC_PENDING:
                        if ($merchantWebsite->getStatus() === Constants::SUBMITTED)
                        {
                            $status = Status::UNDER_REVIEW;
                        }
                        else
                        {
                            $status = $merchantWebsite->getStatus();
                        }
                        break;

                    default:
                        $status = $merchantWebsite->getStatus();
                        break;
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                "error"       => $e->getMessage(),
                "merchant_id" => $merchantDetails->getId()
            ]);
        }

        return $status;
    }

    private function isNeedsClarificationOnWebsite($merchantDetails)
    {
        try
        {
            if ($merchantDetails->getActivationStatus() !== Status::NEEDS_CLARIFICATION)
            {
                return false;
            }

            $kycClarificationReasons = $merchantDetails->getKycClarificationReasons();

            $ncCount = $kycClarificationReasons["nc_count"];

            $kycClarificationReasons = $kycClarificationReasons["clarification_reasons"];

            foreach ($kycClarificationReasons as $key => $values)
            {
                if (in_array($key, COnstants::NEEDS_CLARIFICATION_KEYS))
                {
                    foreach ($values as $val)
                    {
                        if (isset($val[MerchantConstants::NC_COUNT]) and $val[MerchantConstants::NC_COUNT] === $ncCount)
                        {
                            return true;
                        }
                    }

                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                "error"       => $e->getMessage(),
                "merchant_id" => $merchantDetails->getId()
            ]);
        }

        return false;
    }

    private function validateAllSectionsComplete($websiteDetails)
    {
        if (empty($websiteDetails) === true or
            empty($websiteDetails->getMerchantWebsiteDetails()) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        foreach (explode(',', Constants::VALID_MERCHANT_SECTIONS) as $key)
        {
            if ($websiteDetails->getSectionSubmissionStatus($key) !== Constants::SUBMITTED)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);

            }
        }
    }

    private function validateSaveMerchantSectionData($input, $merchantId)
    {
        $websiteDetails = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchantId);

        $businessDetails = optional($this->merchant->merchantDetail->businessDetail);

        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, [
            "input"       => $input,
            "merchant_id" => $merchantId
        ]);

        //verify if all sections are completed or not when the input is submit for verification
        if (array_key_exists(Constants::STATUS, $input) and
            $input[Constants::STATUS] === Constants::SUBMITTED)
        {
            $this->validateAllSectionsComplete($websiteDetails);

            return $input;
        }

        //validate if links are same as in merchant filled details
        //"merchant_website_details": {
        //       "contact_us": {
        //           "section_status": 1,
        //           "website": {
        //               "http://hello.com": {
        //                   "url": "http://hello.co.in/contact_us"
        //               }
        //           }
        //       }
        //   }
        if (isset($input[Entity::MERCHANT_WEBSITE_DETAILS]) === true)
        {
            foreach ($input[Entity::MERCHANT_WEBSITE_DETAILS] as $sectionName => $sectionData)
            {
                // $sectionName : "contact_us"
                // $sectionData : {
                //           "section_status": 1,
                //           "website": {
                //               "http://hello.com": {
                //                   "url": "http://hello.co.in/contact_us"
                //               }
                //           }
                //       }
                if (in_array($sectionName, explode(',', Constants::VALID_MERCHANT_SECTIONS)) === false)
                {
                    $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                        "error" => "invalid section " . $sectionName
                    ]);

                    throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
                }

                if (isset($sectionData) === true)
                {
                    foreach (array_keys($sectionData) as $key)
                    {
                        // $sectionName : "contact_us"
                        // $keys "section_status","website","appstore_url", "playstore_url"
                        // $sectionData : {
                        //           "section_status": 1,
                        //           "website": {
                        //               "http://hello.com": {
                        //                   "url": "http://hello.co.in/contact_us"
                        //               }
                        //           }
                        //       }

                        $input[Entity::MERCHANT_WEBSITE_DETAILS][$sectionName][Constants::UPDATED_AT] = Carbon::now()->getTimestamp();

                        switch ($key)
                        {

                            case Constants::SECTION_STATUS:
                                switch ($sectionData[$key])
                                {
                                    // 1- I have live page with required details
                                    // 2- I have live page but some details are missing
                                    // 3- I don't have this page and need help in creating it
                                    case 1:
                                        $input[Entity::MERCHANT_WEBSITE_DETAILS][$sectionName][Constants::STATUS]        = null;
                                        $input[Entity::MERCHANT_WEBSITE_DETAILS][$sectionName][Constants::PUBLISHED_URL] = null;
                                        break;

                                    case 3:
                                        // section will be marked as completed only when merchant publishes
                                        $input[Entity::MERCHANT_WEBSITE_DETAILS][$sectionName][Constants::STATUS]  = null;
                                        $input[Entity::MERCHANT_WEBSITE_DETAILS][$sectionName][Constants::WEBSITE] = null;

                                        break;
                                    case 2:

                                        // section will be marked as completed only when merchant downloads content
                                        $input[Entity::MERCHANT_WEBSITE_DETAILS][$sectionName][Constants::PUBLISHED_URL] = null;
                                        $input[Entity::MERCHANT_WEBSITE_DETAILS][$sectionName][Constants::STATUS]        = null;

                                        break;
                                }
                                break;
                            case Constants::WEBSITE:

                                $merchantDetail = $this->merchant->merchantDetail;

                                $allWebsites = array_keys($sectionData[$key]);

                                // verify if the input website link is already provided by merchant in business_Website
                                foreach ($allWebsites as $websiteLink)
                                {
                                    if (trim(strtolower($websiteLink), '/') !==
                                        trim(strtolower($merchantDetail->getWebsite()), '/'))
                                    {
                                        $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                                            "error"       => "invalid website",
                                            "websiteLink" => $websiteLink,
                                            "data"        => $merchantDetail->getWebsite()
                                        ]);

                                        throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
                                    }
                                }

                                break;
                            case Constants::PLAYSTORE_URL:

                                $allWebsites = array_keys($sectionData[$key]);

                                foreach ($allWebsites as $websiteLink)
                                {
                                    if (trim(strtolower($websiteLink), '/') !==
                                        trim(strtolower($businessDetails->getPlaystoreUrl()), '/'))
                                    {
                                        $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                                            "error"       => "invalid playstore",
                                            "websiteLink" => $websiteLink,
                                            "data"        => $businessDetails->getPlaystoreUrl()
                                        ]);

                                        throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
                                    }

                                }

                                break;
                            case Constants::APPSTORE_URL:

                                $allWebsites = array_keys($sectionData[$key]);

                                foreach ($allWebsites as $websiteLink)
                                {
                                    if (trim(strtolower($websiteLink), '/') !==
                                        trim(strtolower($businessDetails->getAppstoreUrl()), '/'))
                                    {
                                        $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                                            "error"       => "invalid appstore",
                                            "websiteLink" => $websiteLink,
                                            "data"        => $businessDetails->getAppstoreUrl()
                                        ]);

                                        throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
                                    }

                                }

                                break;

                            default:

                                $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, [
                                    "error" => "default : invalid key",
                                ]);

                                throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);

                        }
                    }
                }
            }
        }

        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, [
            "input"       => $input,
            "merchant_id" => $merchantId
        ]);

        return $input;
    }

    public function postWebsiteSectionAction(array $input)
    {
        $merchant = $this->merchant ?? $this->repo->merchant->findByPublicId($input[Entity::MERCHANT_ID]);

        if ($this->isWebsiteSectionsApplicable($merchant) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        $this->trace->info(TraceCode::MERCHANT_WEBSITE_SECTION_ACTION, [
            "input"       => $input,
            "merchant_id" => $merchant->getId()
        ]);

        (new Validator())->validateInput('merchant_action_section', $input);

        $merchantDetails = $merchant->merchantDetail;

        $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchantDetails->getMerchantId());

        if (empty($websiteDetail) === true)
        {
            $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, []);
        }

        $this->trace->info(TraceCode::MERCHANT_WEBSITE_DETAILS, [
            "merchant_id"       => $merchant->getId(),
            "entity_id"         => optional($websiteDetail)->getId(),
            "website_detail"    => $websiteDetail
        ]);

        switch ($input[Constants::ACTION])
        {
            case Constants::SUBMIT:
                //{
                //  "section_name": "contact_us",
                //  "action": "submit"
                //}
                return $this->submitSection($merchantDetails, $input, $websiteDetail);

            case Constants::PUBLISH:
                //{
                //  "section_name": "contact_us",
                //  "action": "publish",
                //  "merchant_consent":true
                //}
                return $this->publishPage($merchantDetails, $input, $websiteDetail);

            case Constants::DOWNLOAD:
                //{
                //   "section_name": "contact_us",
                //  "action": "download"
                //  "merchant_consent":true
                // }
                return $this->downloadPage($merchant, $input, $websiteDetail);

            case Constants::UPLOAD:
                //{
                //   "section_name": "contact_us",
                //   "url_type": "appstore_url",
                //  "action": "upload",
                //   "file": {file}
                // }
                return $this->uploadMerchantScreenShot($merchant, $input, $websiteDetail);

            case Constants::DELETE:
                //{
                //   "section_name": "contact_us",
                //   "url_type": "appstore_url",
                //  "action": "delete"
                // }
                return $this->deleteMerchantScreenShot($merchant, $input, $websiteDetail);
        }
    }

    //{
    //  "section_name": "contact_us",
    //  "action": "submit"
    //}
    // mark section complete
    // "merchant_website_details": {
    //           "terms": {
    //               "status": "submitted",
    //               "updated_at": 1658934264,
    //               "section_status": 1
    //           },
    // }
    private function submitSection($merchantDetails, $input, $websiteDetail)
    {
        $sectionName = $input[Constants::SECTION_NAME];

        if (empty($websiteDetail) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        $updateInput = [
            Entity::MERCHANT_WEBSITE_DETAILS =>
                [
                    $sectionName => [
                        Constants::STATUS         => Constants::SUBMITTED,
                        Constants::SECTION_STATUS => 1,
                        Constants::UPDATED_AT     => Carbon::now()->getTimestamp()
                    ]
                ]
        ];

        $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, $updateInput);

        return $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchantDetails);

    }

    //{
    //  "section_name": "contact_us",
    //  "action": "publish",
    //  "merchant_consent":true
    //}
    // Send communication + store consent + generate publish url + mark section complete
    // "merchant_website_details": {
    //           "terms": {
    //               "status": "submitted",
    //               "updated_at": 1658934264,
    //               "published_url": "https://sme.np.razorpay.in/policy/K0obrWayUIqw40/terms",
    //               "section_status": 3
    //           },
    // }
    private function publishPage($merchantDetails, $input, $websiteDetail)
    {
        $sectionName = $input[Constants::SECTION_NAME];

        $sendCommunication = false;

        if (empty($input[Constants::MERCHANT_CONSENT]) === false and $input[Constants::MERCHANT_CONSENT] === true)
        {
            $consentInput = [
                ConsentEntity::CONSENT_FOR => Constants::WEBSITE . '_' . $sectionName
            ];

            (new ConsentCore())->createMerchantConsents($consentInput);

            $published_url = $this->host . '/policy/' . $websiteDetail->getId() . '/' . $sectionName;

            $publishInput = [
                Entity::MERCHANT_WEBSITE_DETAILS =>
                    [
                        $sectionName => [
                            Constants::PUBLISHED_URL  => $published_url,
                            Constants::STATUS         => Constants::SUBMITTED,
                            Constants::SECTION_STATUS => 3,
                            Constants::UPDATED_AT     => Carbon::now()->getTimestamp()
                        ]
                    ]
            ];

            $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, $publishInput);

            $sendCommunication = true;
        }

        $updatedAt = optional($websiteDetail)->getSectionUpdatedAt($sectionName) ?? Carbon::now()->getTimestamp();

        $published_url = optional($websiteDetail)->getPublishedUrl($sectionName);

        if ($sendCommunication === true)
        {
            $merchant = $this->repo->merchant->findOrFailPublic($merchantDetails->getMerchantId());

            $communicationArgs = [
                'merchant' => $merchant,
                'params'   => [
                    'section_name'  => Constants::SECTION_DISPLAY_NAME_MAPPING[$sectionName],
                    'date'          => Carbon::createFromTimestamp($updatedAt, Timezone::IST)->addDays(Constants::PUBLISH_TIME_LIMIT)->format('Y-m-d'),
                    'published_url' => $published_url
                ]
            ];

            $communicationArgs['params']['email_content'] = '';

            if (empty($merchant->getEmail()) === false)
            {
                $communicationArgs['params']['email_content'] = " We have also sent you an email with these details.";
            }

            (new OnboardingNotificationHandler($communicationArgs, null))->sendForEvent(Events::WEBSITE_SECTION_PUBLISHED);
        }

        return $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchantDetails);

    }

    //{
    //  "section_name": "contact_us",
    //  "action": "download",
    //  "merchant_consent":true
    //}
    // Send communication + store consent + mark section complete if its a merchant auth
    // download zip file if its a public auth
    // "merchant_website_details": {
    //           "contact_us": {
    //               "status": "submitted",
    //               "updated_at": 1658934264,
    //               "section_status": 2
    //           },
    // }
    private function downloadPage($merchant, $input, $websiteDetail)
    {
        $sectionName = $input[Constants::SECTION_NAME];

        $merchantDetails = $merchant->merchantDetail;

        $sendCommunication = false;

        if ($this->app['basicauth']->isProxyAuth() === false)
        {
            if (optional($websiteDetail)->getSectionStatus($sectionName) !== 2)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
            }
        }

        if (empty($input[Constants::MERCHANT_CONSENT]) === false and $input[Constants::MERCHANT_CONSENT] === true)
        {
            $consentInput = [ConsentEntity::CONSENT_FOR => Constants::WEBSITE . '_' . $sectionName];

            (new ConsentCore())->createMerchantConsents($consentInput);

            $updateInput = [
                Entity::MERCHANT_WEBSITE_DETAILS =>
                    [
                        $sectionName => [
                            Constants::STATUS         => Constants::SUBMITTED,
                            Constants::SECTION_STATUS => 2,
                            Constants::UPDATED_AT     => Carbon::now()->getTimestamp()
                        ]
                    ]
            ];

            $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, $updateInput);

            $sendCommunication = true;

        }

        //generate files then send email and download
        $updatedAt = optional($websiteDetail)->getSectionUpdatedAt($sectionName) ?? Carbon::now()->getTimestamp();

        $htmlContent = view('merchant.website.policy',
                            [
                                "data" => [
                                    'merchant_legal_entity_name' => $merchant->getMerchantLegalEntityName(),
                                    'updated_at'                 => Carbon::createFromTimestamp($updatedAt, Timezone::IST)->isoFormat('MMM Do YYYY'),
                                    'sectionName'                => $sectionName,
                                    'logo_url'                   => $merchant->getFullLogoUrlWithSize(),
                                    'public'                     => false,
                                    'address'                    => 'address',
                                    'merchant'                   => $merchant->toArray(),
                                    'merchant_details'           => $merchant->merchantDetail->toArray(),
                                    'website_detail'             => $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchant->merchantDetail),
                                ]
                            ])->render();

        $htmlFile = $this->getFileName($merchant, $sectionName, 'html');

        $textFile = $this->getFileName($merchant, $sectionName, 'txt');

        $zipFile = $this->getFileName($merchant, $sectionName, 'zip');

        //$readmeFile = public_path() . '/files/policies/readme.txt';

        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, ["htmlFile" => $htmlFile,
                                                               "textFile" => $textFile,
                                                               "zipFile"  => $zipFile
        ]);

        file_put_contents($htmlFile, $htmlContent);

        if (File::exists($textFile))
        {
            $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, ["htmlFile" => $htmlFile
            ]);
        }

        file_put_contents($textFile, Utility::htmlToText($htmlContent));

        if (File::exists($textFile))
        {
            $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, ["textFile" => $textFile,
            ]);
        }

        if ($sendCommunication === true)
        {
            $files = [$htmlFile, $textFile];

            $communicationArgs = [
                'merchant' => $this->repo->merchant->findOrFailPublic($merchantDetails->getMerchantId()),
                'params'   => [
                    'section_name' => Constants::SECTION_DISPLAY_NAME_MAPPING[$sectionName],
                    'date'         => Carbon::createFromTimestamp($updatedAt, Timezone::IST)->addDays(Constants::DOWNLOAD_TIME_LIMIT)->format('Y-m-d')
                ]
            ];

            $communicationArgs['params']['email_content'] = '';

            if (empty($merchant->getEmail()) === false)
            {
                $communicationArgs['params']['email_content'] = " We have also sent you an email with these details.";
            }

            (new OnboardingNotificationHandler($communicationArgs, $files))->sendForEvent(Events::DOWNLOAD_MERCHANT_WEBSITE_SECTION);
        }

        //if its public auth then download file otherwise just mark the section as complete
        if ($this->app['basicauth']->isProxyAuth() === false)
        {
            $files = [$htmlFile => true, $textFile => true];

            return Utility::downloadZip($files, $zipFile);
        }

        return $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchantDetails);

    }

    public function getFileName($merchant, $sectionName, $extension)
    {
        $merchantDisplayName = current(
            array_filter([
                             $merchant->getBillingLabelNotName(),
                             $merchant->merchantDetail->getBusinessName(),
                             $merchant->getName(),
                         ])
        ) ?: '';

        $merchantDisplayName = str_replace(" ", "-", $merchantDisplayName);

        return public_path() . '/files/policies/' . $merchantDisplayName . '_' . $sectionName . '.' . $extension;
    }

    //{
    //  "section_name": "contact_us",
    //  "action": "upload",
    //  "file":{file},
    //  "url_type:"appstore_url"
    //}
    // validate if appstore_url/playstore url exists for the merchant
    // Upload the document to s3 bucket save the details in merchant documents
    // save document id as below
    // merchant_website {
    //  "contact_us": {
    //    "appstore_url":{
    //      "https://apps.apple.com/lol12345.com": {
    //      "document_id": "DGwFIqo2nHqyqn"
    //    }
    //  }
    //}
    private function uploadMerchantScreenShot($merchant, $input, $websiteDetail)
    {
        $sectionName = $input[Constants::SECTION_NAME];

        $urlType = $input[Constants::URL_TYPE];

        $businessDetail = optional($merchant->merchantDetail->businessDetail);

        if ($urlType === Constants::PLAYSTORE_URL)
        {
            $url = trim(strtolower($businessDetail->getPlaystoreUrl()), '/');
        }
        else
        {
            $url = trim(strtolower($businessDetail->getAppstoreUrl()), '/');
        }

        if (empty($url) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        $documentId = optional($websiteDetail)->getMerchantDocumentId($sectionName, $urlType, $url);

        if (empty($documentId) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        $documentInput = [
            DocConstant::DOCUMENT_TYPE => 'website_screenshot',
            DocConstant::FILE          => $input[DocConstant::FILE]
        ];

        $document = (new DocumentCore())->internalUploadFile($merchant, $documentInput);

        $merchantDetails = $merchant->merchantDetail;

        $updateInput = [
            Entity::MERCHANT_WEBSITE_DETAILS => [
                $sectionName => [
                    $urlType              => [
                        $url => [
                            Constants::DOCUMENT_ID => $document->getId()
                        ]
                    ],
                    Constants::UPDATED_AT => Carbon::now()->getTimestamp()
                ]
            ]
        ];

        $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, $updateInput);

        return $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchantDetails);

    }

    //{
    //  "section_name": "contact_us",
    //  "action": "delete",
    //  "url_type:"appstore_url"
    //}
    // validate if appstore_url/playstore url exists for the merchant
    // soft delete the document in merchant documents
    // set document id as null as below
    // merchant_website {
    //  "contact_us": {
    //    "appstore_url":{
    //      "https://apps.apple.com/lol12345.com": {
    //      "document_id": null
    //    }
    //  }
    //}
    private function deleteMerchantScreenShot($merchant, $input, $websiteDetail)
    {
        $sectionName = $input[Constants::SECTION_NAME];

        $urlType = $input[Constants::URL_TYPE];

        $businessDetail = optional($merchant->merchantDetail->businessDetail);

        if ($urlType === Constants::PLAYSTORE_URL)
        {
            $url = trim(strtolower($businessDetail->getPlaystoreUrl()), '/');
        }
        else
        {
            $url = trim(strtolower($businessDetail->getAppstoreUrl()), '/');
        }

        if (empty($websiteDetail) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }
        $documentId = $websiteDetail->getMerchantDocumentId($sectionName, $urlType, $url);

        if (empty($documentId) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        (new DocumentCore())->deleteDocumentsbyId([$documentId]);

        $merchantDetails = $merchant->merchantDetail;

        $updateInput = [
            Entity::MERCHANT_WEBSITE_DETAILS => [
                $sectionName => [
                    Constants::STATUS     => null,
                    $urlType              => [
                        $url => [
                            Constants::DOCUMENT_ID => null
                        ]
                    ],
                    Constants::UPDATED_AT => Carbon::now()->getTimestamp()
                ]
            ]
        ];

        $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, $updateInput);

        return $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchantDetails);

    }

    //validate input url is same as business_Website,additional_websites, playstore_url, appstore_url
    private function validateAdminSaveSectionAction($merchant, $input)
    {

        $urlType = $input[Constants::URL_TYPE];

        $url = trim(strtolower($input[Constants::URL]), '/');

        $merchantUrls = $this->getAllMerchantWebsites($merchant->merchantDetail);

        if (array_key_exists($url, $merchantUrls) === true and $merchantUrls[$url] === $urlType)
        {
            return;
        }

        $this->trace->info(TraceCode::WEBSITE_SECTION_ERROR, ["input"        => $input,
                                                              "merchantUrls" => $merchantUrls,
                                                              "merchant_id"  => $merchant->getId()]);

        throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
    }

    //validate input url is same as business_Website,additional_websites, playstore_url, appstore_url
    private function validateAdminSaveSection($merchant, $input)
    {

        if (isset($input[Constants::URL_TYPE]) === false)
        {
            return $input;
        }

        $this->validateAdminSaveSectionAction($merchant, $input);

        $urlType = $input[Constants::URL_TYPE];

        if ($input[Constants::SECTION_NAME] === Constants::COMMENTS)
        {
            // request : {
            //   "section_name": "comments",
            //   "url_type": "website",
            //   "url": "http://hello.com",
            //   "comments": "hello"
            //}

            // generated body :
            // { "admin_website_details": {
            //           "website": {
            //               "http://hello.com": {
            //                   "comments": "hello"
            //                  }
            //              }
            //          }
            //      }

            return
                [
                    Entity::ADMIN_WEBSITE_DETAILS => [
                        $urlType => [
                            $input[Constants::URL] => [
                                $input[Constants::SECTION_NAME] => $input[Constants::COMMENTS]
                            ]
                        ]
                    ]
                ];
        }
        else
        {
            // request : {
            //   "section_name": "refund",
            //   "url_type": "website/playstore_url/appstore_url",
            //   "url": "http://hello.com",
            //   "section_url": "http://hello.com/refund"
            //}

            // generated body :
            // { "admin_website_details": {
            //           "website": {
            //               "http://hello.com": {
            //                   "refund": {
            //                       "url": "http://hello.com/refund"
            //                   }
            //                }
            //      }

            return
                [
                    Entity::ADMIN_WEBSITE_DETAILS => [
                        $urlType => [
                            $input[Constants::URL] => [
                                $input[Constants::SECTION_NAME] => [
                                    Constants::URL => $input[Constants::SECTION_URL]
                                ]
                            ]
                        ]
                    ]
                ];
        }
    }

    public function getAdminWebsiteSection($merchantId, array $input)
    {
        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        if ($this->isWebsiteSectionsApplicable($merchant, true) === false)
        {
            return ["isWebsiteSectionsApplicable" => false,
                    "isGracePeriodApplicable"     => false
            ];
        }

        $merchantDetails = $merchant->merchantDetail;

        $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchantDetails->getMerchantId());

        if (empty($websiteDetail) === true)
        {
            return ["isWebsiteSectionsApplicable" => true,
                    "isGracePeriodApplicable"     => false
            ];
        }

        return $this->createResponse($websiteDetail->toArray(), $websiteDetail, $merchantDetails);
    }

    public function saveAdminWebsiteSection($merchantId, array $input)
    {
        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, $input);

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        if ($this->isWebsiteSectionsApplicable($merchant, true) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        (new Validator())->validateInput('save_admin_section', $input);

        $input = $this->validateAdminSaveSection($merchant, $input);

        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, $input);

        $merchantDetails = $merchant->merchantDetail;

        $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, $input);

        return $this->createResponse($websiteDetail->toArray(), $websiteDetail, $merchantDetails);

    }

    public function postAdminSectionAction($merchantId, array $input)
    {
        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        if ($this->isWebsiteSectionsApplicable($merchant, true) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }
        (new Validator())->validateInput('admin_action_section', $input);

        $merchantDetails = $merchant->merchantDetail;

        $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchantDetails->getMerchantId());

        $this->trace->info(TraceCode::ADMIN_WEBSITE_SECTION_ACTION, [
            "action"        => $input[Constants::ACTION],
            "websiteDetail" => $websiteDetail->getAdminWebsiteDetails(),
            "merchant_id"   => $merchantDetails->getMerchantId()
        ]);

        switch ($input[Constants::ACTION])
        {
            case Constants::UPLOAD:

                //{
                //    "section_name": "contact_us",
                //    "url_type": "appstore_url",
                //    "action": "upload",
                //    "file": {file}
                // }

                return $this->uploadAdminWebsiteSectionDocuments($merchant, $input, $websiteDetail);

            case Constants::DELETE:

                //{
                //    "section_name": "contact_us",
                //    "url_type": "appstore_url",
                //    "action": "delete"
                // }

                return $this->deleteAdminWebsiteSectionDocuments($merchant, $input, $websiteDetail);
        }
    }

    //{
    //  "section_name": "contact_us",
    //  "action": "upload",
    //  "file":{file},
    //  "url_type:"appstore_url"
    //}
    // Upload the document to s3 bucket save the details in merchant documents
    // save document id as below
    // admin_website_Details : {
    //  {
    //  "contact_us": {
    //    "appstore_url":{
    //      "document_id": "DGwFIqo2nHqyqn"
    //    }
    //  }
    //}
    public function uploadAdminWebsiteSectionDocuments($merchant, array $input, $websiteDetail)
    {

        $this->validateAdminSaveSectionAction($merchant, $input);

        $sectionName = $input[Constants::SECTION_NAME];

        $documentInput = [
            DocConstant::DOCUMENT_TYPE => 'website_screenshot',
            DocConstant::FILE          => $input[DocConstant::FILE]
        ];

        $urlType = $input[Constants::URL_TYPE];

        $businessDetail = optional($merchant->merchantDetail->businessDetail);

        if ($urlType === Constants::PLAYSTORE_URL)
        {
            $url = trim(strtolower($businessDetail->getPlaystoreUrl()), '/');
        }
        else
        {
            $url = trim(strtolower($businessDetail->getAppstoreUrl()), '/');
        }

        if (empty($url) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }
        $documentId = optional($websiteDetail)->getAdminDocumentId($urlType, $url, $sectionName);

        $this->trace->info(TraceCode::ADMIN_WEBSITE_SECTION_ACTION_UPDATE, [
            "documentId" => $documentId,
        ]);

        if (empty($documentId) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        $this->app['basicauth']->setMerchant($merchant);

        $document = (new DocumentCore())->internalUploadFile($merchant, $documentInput);

        $merchantDetails = $merchant->merchantDetail;

        $updateInput = [
            Entity::ADMIN_WEBSITE_DETAILS => [
                $urlType => [
                    $url => [
                        $sectionName => [
                            Constants::DOCUMENT_ID => $document->getId()
                        ]
                    ]
                ]
            ]
        ];

        $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, $updateInput);

        return $this->createResponse($websiteDetail->toArray(), $websiteDetail, $merchantDetails);

    }

    // {
    //  "section_name": "contact_us",
    //  "action": "upload",
    //  "url_type:"appstore_url"
    // }
    // soft delete the document in merchant documents
    // save document id as below
    // admin_website_Details : {
    //  {
    //  "contact_us": {
    //    "appstore_url":{
    //      "document_id": null
    //    }
    //  }
    //}
    public function deleteAdminWebsiteSectionDocuments($merchant, array $input, $websiteDetail)
    {
        $sectionName = $input[Constants::SECTION_NAME];

        $urlType = $input[Constants::URL_TYPE];

        $businessDetail = optional($merchant->merchantDetail->businessDetail);

        if ($urlType === Constants::PLAYSTORE_URL)
        {
            $url = trim(strtolower($businessDetail->getPlaystoreUrl()), '/');
        }
        else
        {
            $url = trim(strtolower($businessDetail->getAppstoreUrl()), '/');
        }

        if (empty($url) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }
        if (empty($websiteDetail) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }
        $this->app['basicauth']->setMerchant($merchant);

        $documentId = $websiteDetail->getAdminDocumentId($urlType, $url, $sectionName);

        if (empty($documentId) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }
        $document = (new DocumentCore())->deleteDocumentsbyId([$documentId]);

        $merchantDetails = $merchant->merchantDetail;

        $updateInput = [
            Entity::ADMIN_WEBSITE_DETAILS => [
                $urlType => [
                    $url => [
                        $sectionName => [
                            Constants::DOCUMENT_ID => null
                        ]
                    ]
                ]
            ]
        ];

        $websiteDetail = $this->core->createOrEditWebsiteDetails($merchantDetails, $updateInput);

        return $this->createResponse($websiteDetail->toArray(), $websiteDetail, $merchantDetails);


    }


    // send html preview of the content for merchant in case of download
    public function getMerchantWebsiteSectionPage(array $input)
    {
        if ($this->isWebsiteSectionsApplicable($this->merchant) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        (new Validator())->validateInput('merchant_section_page', $input);

        $sectionName = $input[Constants::SECTION_NAME];

        $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($this->merchant->getId());

        $websiteDetailArray = (empty($websiteDetail) === false) ? $websiteDetail->toArrayPublic() : [];

        $updatedAt = optional($websiteDetail)->getSectionUpdatedAt($sectionName) ?? Carbon::now()->getTimestamp();

        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, [
            'merchant'         => $this->merchant->toArray(),
            'merchant_details' => $this->merchant->merchantDetail->toArray(),
            'website_detail'   => $websiteDetailArray]);

        return [
            "html" => view('merchant.website.policy',
                           [
                               "data" => [
                                   'merchant_legal_entity_name' => $this->merchant->getMerchantLegalEntityName(),
                                   'updated_at'                 => Carbon::createFromTimestamp($updatedAt, Timezone::IST)->isoFormat('MMM Do YYYY'),
                                   'sectionName'                => $sectionName,
                                   'logo_url'                   => $this->merchant->getFullLogoUrlWithSize(),
                                   'merchant'                   => $this->merchant->toArray(),
                                   'merchant_details'           => $this->merchant->merchantDetail->toArray(),
                                   'website_detail'             => $this->createResponse($websiteDetailArray, $websiteDetail, $this->merchant->merchantDetail),
                                   'public'                     => false
                               ]
                           ])->render()
        ];
    }

    // send html page of the published page while viewing the page after checking if the section is published or not
    public function getPublicWebsiteSectionPage(array $input)
    {
        (new Validator())->validateInput('merchant_section_page', $input);

        $websiteDetail = $this->repo->merchant_website->findOrFail($input['id']);

        $merchant = $this->repo->merchant->findOrFail($websiteDetail->getMerchantId());

        if (empty($websiteDetail) === true or $this->isWebsiteSectionsApplicable($merchant) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        $sectionName = $input[Constants::SECTION_NAME];

        $sectionStatus = $websiteDetail->getSectionStatus($sectionName);

        $publishedWebsite = $websiteDetail->getPublishedUrl($sectionName);

        $updatedAt = $websiteDetail->getSectionUpdatedAt($sectionName);

        if ($sectionStatus === 3 and empty($publishedWebsite) === false)
        {
            return ["html" => view('merchant.website.policy',
                                   [
                                       "data" => [
                                           'merchant_legal_entity_name' => $merchant->getMerchantLegalEntityName(),
                                           'updated_at'                 => Carbon::createFromTimestamp($updatedAt, Timezone::IST)->isoFormat('MMM Do YYYY'),
                                           'sectionName'                => $sectionName,
                                           'logo_url'                   => $merchant->getFullLogoUrlWithSize(),
                                           'merchant'                   => $merchant->toArray(),
                                           'merchant_details'           => $merchant->merchantDetail->toArray(),
                                           'website_detail'             => $this->createResponse($websiteDetail->toArrayPublic(), $websiteDetail, $merchant->merchantDetail),
                                           'public'                     => true
                                       ]
                                   ])->render()];
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }
    }


    // get all published section pages for public view
    public function getPublicWebsiteSectionPageLinks($id)
    {
        $websiteDetail = $this->repo->merchant_website->findOrFail($id);

        $merchant = $this->repo->merchant->findOrFail($websiteDetail->getMerchantId());

        $links = [];

        if ($this->isWebsiteSectionsApplicable($merchant) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchant->getId());

        if (empty($websiteDetail) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_SECTION_NOT_APPLICABLE);
        }

        foreach (explode(',', Constants::VALID_MERCHANT_SECTIONS) as $sectionName)
        {
            $sectionStatus = $websiteDetail->getSectionStatus($sectionName);

            $publishedWebsite = $websiteDetail->getPublishedUrl($sectionName);

            if ($sectionStatus === 3 and empty($publishedWebsite) === false)
            {
                array_push($links, [
                                     "display_name" => Constants::SECTION_DISPLAY_NAME_MAPPING[$sectionName],
                                     "section_name" => $sectionName
                                 ]
                );

            }

        }

        return [
            "name"     => $merchant->getName(),
            "logo"     => $merchant->getFullLogoUrlWithSize(),
            "sections" => $links
        ];
    }

    public function getMerchantPolicyDetails(): object
    {
        $policyData = $this->checkAndFillMerchantPolicyPage($this->merchant);

        $data = [];

        if (!empty($policyData)) {
            $data['url'] = $policyData['url'];
            $data['display_name'] = $policyData['display_name'];
        }

        // Type-casting to object to ensure empty JSON object `{}` is sent
        // instead-of `[]` when $data is empty.
        return (object) $data;
    }

    // if merchant has any published pages send that information to checkout preferences
    // if merchant is activated save to redis and fetch from redis
    public function checkAndFillMerchantPolicyPage(MerchantEntity $merchant)
    {
        try
        {
            if ($this->isWebsiteSectionsApplicable($merchant) === false)
            {
                return null;
            }

            $merchantDetail = $merchant->merchantDetail;

            $data = null;

            if ($merchantDetail->getActivationStatus() === Status::ACTIVATED)
            {

                $data = (new StoreCore)->fetchValuesFromStore($merchant->getId(),
                                                              ConfigKey::ONBOARDING_NAMESPACE,
                                                              [ConfigKey::POLICY_DATA],
                                                              StoreConstants::INTERNAL);

                $data = $data[ConfigKey::POLICY_DATA];

            }

            if (empty($data) === true)
            {
                $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchant->getId());

                if (empty($websiteDetail) === true or
                    empty(optional($websiteDetail)->getStatus()) === true)
                {
                    return null;
                }

                foreach (explode(',', Constants::VALID_MERCHANT_SECTIONS) as $sectionName)
                {

                    $sectionStatus = $websiteDetail->getSectionStatus($sectionName);

                    $publishedWebsite = $websiteDetail->getPublishedUrl($sectionName);

                    if ($sectionStatus === 3 and empty($publishedWebsite) === false)
                    {
                        $data["url"] = $published_url = $this->host . '/policy/' . $websiteDetail->getId();

                        $data["display_name"] = "About " . (strlen($merchant->getName()) > 15 ? 'Merchant' : $merchant->getName());

                        if ($merchantDetail->getActivationStatus() === Status::ACTIVATED)
                        {
                            $storeData = [
                                StoreConstants::NAMESPACE => ConfigKey::ONBOARDING_NAMESPACE,
                                ConfigKey::POLICY_DATA    => $data
                            ];

                            $storeData = (new StoreCore())->updateMerchantStore($merchant->getId(),
                                                                                $storeData,
                                                                                StoreConstants::INTERNAL);
                        }

                    }
                }
            }

            return $data;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::WEBSITE_SECTION_ERROR,
                ['merchant_id' => $merchant->getId()]);
        }

        return null;
    }

    public function changeWebsiteIfApplicable($merchantDetail, $businessDetail, $input)
    {
        $this->trace->info(TraceCode::WEBSITE_ADHERENCE_INFO, [
            "input"       => $input,
            "step"        => "changeWebsite",
            "merchant_id" => $merchantDetail->getMerchantId()
        ]);

        try
        {
            $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($merchantDetail->getMerchantId());

            if (empty($websiteDetail) === false and
                $this->hasWebsitesChanged($merchantDetail, $businessDetail, $input) === true)
            {
                $merchantWebsiteDetails = $websiteDetail->getMerchantWebsiteDetails();

                foreach (explode(',', Constants::VALID_MERCHANT_SECTIONS) as $sectionName)
                {
                    $merchantWebsiteDetails[$sectionName][Constants::STATUS] = null;

                }

                $statusChangeInput = [
                    Entity::STATUS                   => null,
                    Entity::MERCHANT_WEBSITE_DETAILS => $merchantWebsiteDetails
                ];

                $this->core->createOrEditWebsiteDetails($merchantDetail, $statusChangeInput);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::WEBSITE_SECTION_ERROR,
                                         ['id' => $merchantDetail->getMerchantId()]);
        }
    }

    protected function hasWebsitesChanged($merchantDetail, $businessDetail, $input)
    {
        try
        {

            if (empty($merchantDetail->getWebsite()) === false and
                isset($input[MerchantDetailEntity::BUSINESS_WEBSITE]) and
                $merchantDetail->getWebsite() !== $input[MerchantDetailEntity::BUSINESS_WEBSITE])
            {
                return true;
            }
            if (empty($businessDetail->getAppstoreUrl()) === false and
                isset($input[BusinessConstants::APPSTORE_URL]) and
                $businessDetail->getAppstoreUrl() !== $input[BusinessConstants::APPSTORE_URL])
            {
                return true;
            }
            if (empty($businessDetail->getPlaystoreUrl()) === false and
                isset($input[BusinessConstants::PLAYSTORE_URL]) and
                $businessDetail->getPlaystoreUrl() !== $input[BusinessConstants::PLAYSTORE_URL])
            {
                return true;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::WEBSITE_SECTION_ERROR,
                                         ['id' => $merchantDetail->getMerchantId()]);

        }

        return false;
    }

    private function saveMerchantBMCResponse($commonKeysValues)
    {
        $data = [];

        //common key values format: {shipping_period" : "0-7 days"}

        foreach ($commonKeysValues as $key => $values)
        {
            if (is_array($values) === true)
            {
                foreach ($values as &$value)
                {
                    $value = Constants::WEBSITE_POLICY_QUESTION_MAPPING[$key][$value];
                }
            }

            $data[] = [
                Constants::QUESTION_ID => Constants::WEBSITE_POLICY_QUESTION_MAPPING[$key][Constants::QUESTION_ID],
                Constants::ANSWER      => (is_array($values) === true) ?
                    $values : [Constants::WEBSITE_POLICY_QUESTION_MAPPING[$key][$values]]
            ];

            $this->trace->info(TraceCode::SAVE_MERCHANT_BMC_RESPONSE, [
                "bmc_response" => $data,
            ]);
        }

        //$data =
        //[
        //  {
        //      "question_id": "question_2",
        //      "answer": ["option_2_3"]
        //  }
        //]

        $pgosPayload = [
            "data" => $data
        ];

        $pgosProxyController = new MerchantOnboardingProxyController();

        $response = $pgosProxyController->handlePGOSProxyRequests('save_merchant_bmc_response',
                                                                  $pgosPayload, $this->merchant);

        $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
            'response' => $response
        ]);
    }

    /**
     * @param array $bmcInput
     * @param bool  $isBMCResponseUpdated
     *
     * @throws ServerErrorException
     */
    public function updateCommonWebsiteQuestions(array $bmcInput, bool $isBMCResponseUpdated)
    {
        /* expected data in $bmcInput
        {
        "data":
            [
            {
                "question_id":"question_2",
                "answer":["option_2_2"]
            },
            {
                "question_id":"question_3",
                "answer":["option_3_2"]
            }
            ]
        }
        */

        try
        {
            $this->trace->info(TraceCode::UPDATE_COMMON_WEBSITE_QUESTIONS, [
                "input"                 => $bmcInput,
                "isBMCResponseUpdated"  => $isBMCResponseUpdated
            ]);

            $flattenedBMCInput = [];

            foreach ($bmcInput['data'] as $item)
            {
                $flattenedBMCInput[$item[Constants::QUESTION_ID]] = $item[Constants::ANSWER ];
            }

            //$flattenedBMCInput = {"question_2":["option_2_2"], "question_3":["option_3_2"]}

            $commonKeysValues = array_intersect_key($flattenedBMCInput, array_flip(Constants::COMMON_QUESTIONS_IN_WEBSITE_POLICY_AND_BMC));

            //$commonKeysValues = {"question_2":["option_2_2"]}

            if(empty($commonKeysValues) === false)
            {
                $transformedPayloadForWebsitePolicy = [];

                foreach (Constants::WEBSITE_POLICY_QUESTION_MAPPING as $key => $value)
                {
                    $questionId = $value[Constants::QUESTION_ID];

                    if (isset($commonKeysValues[$questionId]) === true)
                    {
                        if (Constants::FIELD_VALUE_TYPE_MAPPING[$key] === 'string')
                        {
                            $transformedPayloadForWebsitePolicy[$key] = array_keys($value, $commonKeysValues[$questionId][0])[0] ?? '';
                        }
                        else
                        {
                            $answers = [];

                            foreach ($commonKeysValues[$questionId] as $option)
                            {
                                $answers[] = array_keys($value, $option)[0] ?? [];
                            }

                            $transformedPayloadForWebsitePolicy[$key] = $answers;
                        }
                    }
                }

                $this->trace->info(TraceCode::UPDATE_MERCHANT_WEBSITE_DETAILS, [
                    "website_input" => $transformedPayloadForWebsitePolicy
                ]);

                //{"$transformedPayloadForWebsitePolicy":{"shipping_period":"8-14 days"}}

                $this->core->createOrEditWebsiteDetails($this->merchant->merchantDetail, $transformedPayloadForWebsitePolicy);
            }
        }
        catch (\Throwable $e)
        {
            $this->raiseAlertAndThrowError($isBMCResponseUpdated, $e);
        }

    }

    /**
     * @param bool                  $isResponseUpdatedInPgos
     * @param \Exception|\Throwable $e
     *
     * @throws ServerErrorException
     */
    private function raiseAlertAndThrowError(bool $isResponseUpdatedInPgos, \Exception|\Throwable $e): void
    {
        if ($isResponseUpdatedInPgos === true)
        {
            $this->trace->count(Metric::DATA_MISMATCH_FOR_WEBSITE_POLICY_AND_BMC_RESPONSE);
        }

        $this->trace->traceException($e);

        throw new ServerErrorException(PublicErrorDescription::SERVER_ERROR, ErrorCode::SERVER_ERROR);
    }
}
