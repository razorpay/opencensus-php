<?php


namespace RZP\Models\BankingAccount\Activation\MIS;

use Carbon\Carbon;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Status;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\BankingAccount\BankLms\BranchMaster;
use RZP\Models\BankingAccount\BankLms\RmMaster;

class Leads extends Base
{
    //Headers
    const MERCHANT_ID = 'Merchant ID';
    const MERCHANT_NAME = 'Customer Name';
    const MERCHANT_POC_NAME = 'POC Name';
    const MERCHANT_POC_EMAIL = 'Customer email';
    const MERCHANT_POC_DESIGNATION = 'POC Designation';
    const MERCHANT_POC_PHONE = 'Customer phone number';
    const MERCHANT_CITY = 'Merchant City';
    const MERCHANT_REGION = 'Merchant Region';
    const YEARS_WITH_RAZORPAY = 'Years of Merchant Relationship with Razorpay';
    const PINCODE = 'Pincode Where CA is to be Opened';

    const MERCHANT_ICV = 'ICV';
    const BUSINESS_MODEL = 'Business Model';
    const ACCOUNT_TYPE = 'Account Type';
    const AVERAGE_MONTHLY_BALANCE = 'Average Monthly Balance';
    const CONSTITUTION_TYPE = 'Constitution Type';
    const ZERO_AVERAGE_MONTHLY_BALANCE = 'Zero AMB?';
    const EXPECTED_MONTHLY_GMV = 'GMV';

    const RZP_REF_NO = 'Customer Reference Number';
    const COMMENT = 'Comments';
    const SALES_TEAM = 'Sales Team';
    const SALES_POC_EMAIL = 'Razorpay Sales POC Email';
    const SALES_POC = 'Razorpay POC Name';
    const SALES_POC_PHONE_NUMBER = 'Razorpay POC Number';

    const GREEN_CHANNEL = 'Green Channel';
    const FOS = 'FOS';
    const REVIVED_LEAD = 'Revived Lead';
    const OPS_POC_NAME = "Razorpay Ops POC Name";
    const OPS_POC_EMAIL = "Razorpay Ops POC Email";
    const DOCKET_DELIVERY_DATE = "Docket delivery date";
    const STATUS = "Status";
    const SUB_STATUS = "Sub - Status";
    const ASSIGNEE = "Assignee";
    const MID_OFFICE_POC = "Mid-Office POC";
    const LEAD_REFERRED_BY_RBL_STAFF = "Lead referred by RBL staff";
    const OFFICE_AT_DIFFERENT_LOCATIONS = "Directors/Partners/Registered Office at Different Location";
    const CUSTOMER_APPOINTMENT_DATE = "Customer appointment date";
    const APPOINTMENT_TAT = "Appointment date TAT";
    const LEAD_IR_NO= "Lead IR No";
    const RM_NAME = "RM";
    const RM_MOBILE_NO = "RM Mobile No";
    const BRANCH_CODE = "Branch Code";
    const BRANCH_NAME = "Branch Name";
    const BM = "BM";
    const BM_MOBILE_NO = "BM Mob No.";
    const TL = "TL (PCARM AH)";
    const CLUSTER = "Cluster";
    const REGION = "Region";
    const DOC_COLLECTION_DATE = "Docs Collection Date";
    const DOC_COLLECTION_TAT = "Doc collection TAT";
    const IP_CHEQUE_VALUE = "IP cheque value";
    const API_DOCS_RECEIVED_WITH_CA_DOCS = "API documents received at the time of CA documents";
    const API_DOC_DELAY_REASON = "If (N) Reason";
    const REVISED_DECLARATION = "Revised declaration towards BR collected";
    const ACCOUNT_IR_NO = "Account IR No.";
    const ACCT_LOGIN_DATE = "Acct Login Date";
    const IR_LOGIN_TAT = "IR login TAT";
    const PROMO_CODE = "Promo Code";
    const CASE_LOGIN = "Case login by PCARM in different location";
    const SR_NO = "SR No.";
    const ACCOUNT_OPEN_DATE = "Account Open Date";
    const ACCOUNT_IR_CLOSED_DATE = "Account IR closed Date";
    const AO_FTNR = "AO FTNR";
    const AO_FTNR_REASONS = "AO FTNR Reasons";
    const AO_TAT_EXCEPTION = "AO TAT Exception";
    const AO_TAT_EXCEPTION_REASON = "AO TAT Exception Reason";
    const API_IR_NO = "API IR NO";
    const API_IR_LOGIN_DATE = "API IR Login Date";
    const LDAP_ID_MAIL_DATE = "LDAP ID Mail Date";
    const API_REQUEST_TAT = "API Request TAT";
    const API_IR_CLOSED_DATE = "API IR closed date";
    const API_REQUEST_PROCESSING_TAT = "API request processing TAT";
    const API_FTNR = "API FTNR";
    const API_FTNR_REASONS = "API FTNR Reasons";
    const API_TAT_EXCEPTION = "API TAT Exception";
    const API_TAT_EXCEPTION_REASON = "API TAT Exception Reason";
    const CORP_ID_MAIL_DATE = "Corp ID Mail Date";
    const RZP_CA_ACTIVATED_DATE = "RZP CA Activated Date";
    const UPI_CREDENTIALS_DATE = "UPI Credentials Date";
    const UPI_CREDENTIALS_NOT_DONE_REMARKS = "UPI Credentials not done Remarks";
    const DROP_OFF_DATE = "Drop - Off Date";

    const API_SERVICE_FIRST_QUERY = 'Api Service First Query';
    const API_BEYOND_TAT = 'Api Beyond Tat';
    const API_BEYOND_TAT_DEPENDENCY = 'Api Beyond Tat Dependency';
    const FIRST_CALLING_TIME = 'First Calling Time';
    const SECOND_CALLING_TIME = 'Second Calling Time';
    const WA_MESSAGE_SENT_DATE = 'Wa Message Sent Date';
    const WA_MESSAGE_RESPONSE_DATE = 'Wa Message Response Date';
    const API_DOCKET_RELATED_ISSUE = 'Api Docket Related Issue';
    const AOF_SHARED_WITH_MO = 'Aof Shared With Mo';
    const AOF_SHARED_DISCREPANCY = 'Aof Shared Discrepancy';
    const AOF_NOT_SHARED_REASON ='Aof Not Shared Reason';
    const CA_BEYOND_TAT_DEPENDENCY = 'Ca Beyond Tat Dependency';
    const CA_BEYOND_TAT = 'Ca Beyond Tat';
    const CA_SERVICE_FIRST_QUERY = 'Ca Service First Query';
    const CUSTOMER_APPOINTMENT_BOOKING_DATE = 'Customer Appointment Booking Date';
    const CUSTOMER_ONBOARDING_TAT = 'Customer Onboarding Tat';
    const LEAD_IR_STATUS = 'Lead Ir Status';


    const APPLICATION_SUBMISSION_DATE = 'Application Submission Date';
    const TIMESTAMP = 'Timestamp';

    protected static $toPublicMap = [
        self::BUSINESS_MODEL => [
            BusinessCategory::FINANCIAL_SERVICES       => 'Financial Services',
            BusinessCategory::EDUCATION                => 'Education',
            BusinessCategory::HEALTHCARE               => 'Healthcare',
            BusinessCategory::UTILITIES                => 'Utilities-General',
            BusinessCategory::GOVERNMENT               => 'Government Bodies',
            BusinessCategory::LOGISTICS                => 'Logistics',
            BusinessCategory::TOURS_AND_TRAVEL         => 'Tours and Travel',
            BusinessCategory::TRANSPORT                => 'Transport',
            BusinessCategory::ECOMMERCE                => 'Ecommerce',
            BusinessCategory::FOOD                     => 'Food and Beverage',
            BusinessCategory::IT_AND_SOFTWARE          => 'IT and Software',
            BusinessCategory::GAMING                   => 'Gaming',
            BusinessCategory::MEDIA_AND_ENTERTAINMENT  => 'Media and Entertainment',
            BusinessCategory::SERVICES                 => 'Services',
            BusinessCategory::HOUSING                  => 'Housing and Real Estate',
            BusinessCategory::NOT_FOR_PROFIT           => 'Not-For-Profit',
            BusinessCategory::SOCIAL                   => 'Social',
            BusinessCategory::OTHERS                   => 'Others',

            //These are old subcategory values that have been moved to BusinessCategory values
            BusinessCategory::COUPONS                              => 'Ad/Coupons/Deals Services',
            BusinessCategory::REPAIR_AND_CLEANING                  => 'Automotive Repair Shops',
            BusinessCategory::ACCOUNTING                           => 'Accounting Services',
            BusinessCategory::TELECOMMUNICATION_SERVICE            => 'Pre/Post Paid/Telecom Services',
            BusinessCategory::SERVICE_CENTRE                       => 'Service Centre',
            BusinessCategory::COWORKING                            => 'Real Estate Agents/Rentals',
            BusinessCategory::CAB_HAILING                          => 'Cab Service',
            BusinessCategory::GROCERY                              => 'Grocery',
            BusinessCategory::PAAS                                 => 'Platform as a Service',
            BusinessCategory::SAAS                                 => 'Software as a Service',
            BusinessCategory::WEB_DEVELOPMENT                      => 'Web designing/Development',
            BusinessCategory::CHARITY                              => 'Charity',
            BusinessCategory::FASHION_AND_LIFESTYLE                => 'Fashion and Lifestyle',
            BusinessCategory::DROP_SHIPPING                        => 'General Merchandise Stores',
            BusinessCategory::CONSULTING_AND_OUTSOURCING           => 'Consulting/PR Services',
            BusinessCategory::CATERING                             => 'Caterers',
            BusinessCategory::HEALTH_COACHING                      => 'Health and Beauty Spas',
            BusinessCategory::COMPUTER_PROGRAMMING_DATA_PROCESSING => 'Computer Programming/Data Processing',
            BusinessCategory::UTILITIES_ELECTRIC_GAS_OIL_WATER     => 'Utilities–Electric, Gas, Water, Oil',
        ],

        self::CONSTITUTION_TYPE => [
            ActivationDetail\Validator::PRIVATE_PUBLIC_LIMITED_COMPANY    => 'Private/Public Limited Company',
            ActivationDetail\Validator::SOLE_PROPRIETORSHIP               => 'Sole Proprietorship',
            ActivationDetail\Validator::LIMITED_LIABILITY_PARTNERSHIP     => 'Limited Liability Partnership',
            ActivationDetail\Validator::PARTNERSHIP                       => 'Partnership',
            ActivationDetail\Validator::ONE_PERSON_COMPANY                => 'One Person Company',
            ActivationDetail\Validator::TRUST                             => 'Trust',
            ActivationDetail\Validator::SOCIETY                           => 'Society'
        ],
        self::ACCOUNT_TYPE => [
            ActivationDetail\Validator::INSIGNIA      => 'Insignia',
            ActivationDetail\Validator::PREMIUM       => 'Premium',
            ActivationDetail\Validator::BUSINESS_PLUS => 'Business Plus',
            ActivationDetail\Validator::ZERO_BALANCE  => 'Zero Balance',
        ],
        self::SALES_TEAM => [
            ActivationDetail\Validator::GROWTH                 => 'X NIT - Enterprise / Mid-Market',
            ActivationDetail\Validator::DIRECT_SALES           => 'X Direct Sales',
            ActivationDetail\Validator::KEY_ACCOUNT            => 'X Key Account',
            ActivationDetail\Validator::SME                    => 'X SME',
            ActivationDetail\Validator::CAPITAL_SME            => 'Capital SME',
            ActivationDetail\Validator::CAPITAL_GROWTH         => 'Capital Growth',
            ActivationDetail\Validator::CAPITAL_KAM            => 'Capital KAM',
            ActivationDetail\Validator::CAPITAL_DIRECT_SALES   => 'Capital Direct Sales',
            ActivationDetail\Validator::PG_SME                 => 'PG SME',
            ActivationDetail\Validator::PG_GROWTH              => 'PG Growth',
            ActivationDetail\Validator::PG_KAM                 => 'PG KAM',
            ActivationDetail\Validator::PG_DIRECT_SALES        => 'PG Direct Sales',
            ActivationDetail\Validator::SELF_SERVE             => 'Self Serve',
            ActivationDetail\Validator::NIT_PARTNERSHIPS       => 'X NIT - Partnerships'
        ]
    ];

    protected function toPublic(string $header, string $value = null)
    {
        if ($value === null)
        {
            return $value;
        }

        if (empty($value))
        {
            return null;
        }

        $values = self::$toPublicMap[$header];

        if (array_key_exists($value, $values))
        {
            return self::$toPublicMap[$header][$value];
        }

        return $value;
    }


    public function __construct(array $input, string $entity = 'banking_account')
    {
        $timestamp = Carbon::createFromTimestamp(time(), Timezone::IST)->format('Y-m-d--H-i');

        $this->fileName = "CA-Leads-MIS-" . $timestamp;

        $this->fileType = 'banking_account_leads';

        $this->entity = $entity;

        parent::__construct($input);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_LEADS_MIS_REQUEST,
            [
                'file_name' => $this->fileName,
                'type'      => $this->fileType,
                'input'     => $input
            ]);
    }

    /**
     * Convert a value to yes/no based on exact match condition
     */
    protected function convertToYesNo($value, $truthy = true, $falsy = false)
    {
        if ($value === $truthy)
        {
            return 'Yes';
        }

        if ($value === $falsy)
        {
            return 'No';
        }

        return '';
    }

    protected function convertEpochToDateFormat($value)
    {
        if (empty($value) === true)
        {
            return '';
        }

        if (gettype($value) === 'string')
        {
            $value = (int)$value;
        }

        return Carbon::createFromTimestamp($value, Timezone::IST)->format('Y-m-d') ?? '';
    }

    protected function calculateTATInDays($t1, $t2)
    {
        $hours = ActivationDetail\Entity::hourDifferenceBetweenTimestamps($t1, $t2, true);

        if (is_double($hours) === true)
        {
            return round($hours / 24);
        }

        return null;
    }

    protected function updateCommentMap(array $bankingAccountIds, &$commentsMap)
    {
        
        $commentRepo = (new BankingAccount\Activation\Comment\Repository());

        /** @var  BankingAccount\Activation\Comment\Entity[] $lastExternalComments */
        $lastExternalComments = $commentRepo->getCommentForMultipleBankingAccounts(
                $bankingAccountIds,
                'last',
                [
                    BankingAccount\Activation\Comment\Entity::TYPE => 'external',
                    BankingAccount\Activation\Comment\Entity::SOURCE_TEAM => 'bank'
                ]
            );

        foreach($lastExternalComments as $lastExternalComment)
        {
            $commentsMap[$lastExternalComment->getBankingAccountId()] = $lastExternalComment->getComment();
        }
    }

    protected function updateStateMap(array $bankingAccountIds, &$sentToBankTimestampMap)
    {
        $stateRepo = (new BankingAccount\State\Repository());

        /** @var  BankingAccount\State\Entity[] $lastSentToBankLogs */
        $lastSentToBankLogs = $stateRepo->getStateChangeLogForMultipleBankingAccounts(
                $bankingAccountIds,
                'last',
                [
                    BankingAccount\State\Entity::STATUS => Status::INITIATED
                ]
            );

        foreach($lastSentToBankLogs as $lastSentToBankLog)
        {
            $sentToBankTimestampMap[$lastSentToBankLog[BankingAccount\State\Entity::BANKING_ACCOUNT_ID]] = $lastSentToBankLog[BankingAccount\State\Entity::CREATED_AT];
        }
    }

    protected function getData(): array
    {
        $entity = $this->entity;
        /** ============== PREPARE DATA ================ */

        // /** @var  BankingAccount\Entity[] $bankingAccounts */
        $bankingAccounts = $this->repo->$entity->fetch($this->input);

        $bankingAccountIds = array_map(function ($bankingAccount) {
            return $bankingAccount['id'];
        }, $bankingAccounts->toArray());

        $commentsMap = [];
        $this->updateCommentMap($bankingAccountIds, $commentsMap);

        $sentToBankTimestampMap = [];
        $this->updateStateMap($bankingAccountIds, $sentToBankTimestampMap);

        return [$bankingAccounts, $commentsMap, $sentToBankTimestampMap];
    }

    public function getFileInput()
    {
        [$bankingAccounts, $commentsMap, $sentToBankTimestampMap] = $this->getData();
    
        /** ============== PREPARE FILE INPUT ================ */ 

        $fileInput = [];

        foreach ($bankingAccounts as $bankingAccount)
        {
            $comment = $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::COMMENT] ?? '';

            if (isset($commentsMap[$bankingAccount->getId()]))
            {
                $comment = $commentsMap[$bankingAccount->getId()];
            }

            // Replace HTML tags in comment field, if any
            $pattern = '/<(.|\n\t)*?>/';
            $comment = preg_replace($pattern, '', $comment);
            $comment = preg_replace('/\s+/', ' ', $comment);
            $comment = str_replace(array("\r", "\n", "\t"), '', $comment);

            $sentToBankTimestamp = null;

            if (isset($sentToBankTimestampMap[$bankingAccount->getId()]))
            {
                $sentToBankTimestamp = $sentToBankTimestampMap[$bankingAccount->getId()];
            }

            $sentToBankDate = '';
            $sentToBankTime = '';

            if (
                $sentToBankTimestamp !== null)
            {
                $sentToBankDate = Carbon::createFromTimestamp($sentToBankTimestamp, Timezone::IST)->format('Y-m-d') ?? '';
                $sentToBankTime = Carbon::createFromTimestamp($sentToBankTimestamp, Timezone::IST)->format('h:i A') ?? '';;
            }

            $bankingAccountActivationDetails = $bankingAccount->bankingAccountActivationDetails;

            $additionalDetails = $bankingAccountActivationDetails[ActivationDetail\Entity::ADDITIONAL_DETAILS];

            if (empty($additionalDetails) === false)
            {
                $additionalDetails = json_decode($additionalDetails);
            }

            $rblActivationDetails = $bankingAccountActivationDetails[ActivationDetail\Entity::RBL_ACTIVATION_DETAILS];

            if (empty($rblActivationDetails) === false)
            {
                $rblActivationDetails = json_decode($rblActivationDetails);
            }

            $branchName = '';
            $branchCluster = '';
            $branchRegion = '';
            $branchManagerName = '';
            $branchManagerPhone = '';
            $branchCode = $bankingAccountActivationDetails[ActivationDetail\Entity::BRANCH_CODE];

            if (empty($branchCode) === false)
            {
                $branch = (new BranchMaster)->getBranchByBranchCode($branchCode);

                if (empty($branch) === false)
                {
                    $branchName = $branch['name'];
                    $branchCluster = $branch['cluster'];
                    $branchRegion = $branch['region'];
                    $branchManagerName = $branch['branch_manager_name'];
                    $branchManagerPhone = $branch['branch_manager_phone'];
                }
            }

            $pcarmAH = '';
            $rmEmployeeCode = $bankingAccountActivationDetails[ActivationDetail\Entity::RM_EMPLOYEE_CODE];

            if (empty($rmEmployeeCode) === false)
            {
                $rm = (new RmMaster)->getRMByEmployeeCode($rmEmployeeCode);

                if (empty($rm) === false)
                {
                    $pcarmAH = $rm['manager_name'];
                }
            }

            $bankPocUserName = '';

            if (empty($bankingAccount->bankingAccountActivationDetails) === false)
            {
                $bankPocUser = $bankingAccount->bankingAccountActivationDetails->getBankPOCUser();

                if(empty($bankPocUser) === false)
                {
                    $bankPocUserName = $bankPocUser->getName();
                }
            }

            $opsPOC = $bankingAccount->reviewers->first();
            $opsPOCName = '';
            $opsPOCEmail = '';

            if ($opsPOC != null)
            {
                $opsPOCName = $opsPOC['name'];
                $opsPOCEmail = $opsPOC['email'];
            }

            $customerAppointmentDate = $bankingAccountActivationDetails[ActivationDetail\Entity::CUSTOMER_APPOINTMENT_DATE];

            $appointmentTAT = $this->calculateTATInDays($sentToBankTimestamp, $customerAppointmentDate);

            $docCollectionDate = $bankingAccountActivationDetails[ActivationDetail\Entity::DOC_COLLECTION_DATE];

            $docCollectionTAT = $this->calculateTATInDays($customerAppointmentDate, $docCollectionDate);

            $accountLoginDate = $bankingAccountActivationDetails[ActivationDetail\Entity::ACCOUNT_LOGIN_DATE];

            $irLoginTAT = $this->calculateTATInDays($docCollectionDate, $accountLoginDate);

            $accountOpeningDate = $bankingAccount[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE];

            $apiOnboardingLoginDate = ActivationDetail\Entity::extractFieldFromJSONField($additionalDetails, ActivationDetail\Entity::API_ONBOARDING_LOGIN_DATE);

            $apiRequestTAT = $this->calculateTATInDays($accountOpeningDate, $apiOnboardingLoginDate);

            $apiIRClosedDate = $bankingAccountActivationDetails[ActivationDetail\Entity::API_IR_CLOSED_DATE];

            $customerOnboardingTat = $this->calculateTATInDays($docCollectionDate, $apiIRClosedDate);

            $apiRequestProcessingTAT = $this->calculateTATInDays($apiOnboardingLoginDate, $apiIRClosedDate);

            $fileInput[] = [
                self::RZP_REF_NO                       => $bankingAccount[BankingAccount\Entity::BANK_REFERENCE_NUMBER],
                self::MERCHANT_NAME                    => $bankingAccount->merchant[Merchant\Entity::NAME],
                self::MERCHANT_POC_NAME                => $bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_NAME],
                self::MERCHANT_POC_DESIGNATION         => $bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_DESIGNATION],
                self::MERCHANT_POC_EMAIL               => $bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_EMAIL],
                self::MERCHANT_POC_PHONE               => $bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER],
                self::PINCODE                          => $bankingAccount[BankingAccount\Entity::PINCODE],
                self::MERCHANT_CITY                    => $bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_CITY],
                self::CONSTITUTION_TYPE                => $this->toPublic(self::CONSTITUTION_TYPE, $bankingAccountActivationDetails[ActivationDetail\Entity::BUSINESS_CATEGORY]),
                self::MERCHANT_ICV                     => $bankingAccountActivationDetails[ActivationDetail\Entity::INITIAL_CHEQUE_VALUE],
                self::APPLICATION_SUBMISSION_DATE      => $sentToBankDate,
                self::TIMESTAMP                        => $sentToBankTime,
                self::BUSINESS_MODEL                   => $this->toPublic(self::BUSINESS_MODEL, $bankingAccount->merchant->merchantDetail[Merchant\Detail\Entity::BUSINESS_CATEGORY]),
                self::ACCOUNT_TYPE                     => $this->toPublic(self::ACCOUNT_TYPE, $bankingAccountActivationDetails[ActivationDetail\Entity::ACCOUNT_TYPE]),
                self::COMMENT                          => $comment,
                self::EXPECTED_MONTHLY_GMV             => $bankingAccountActivationDetails[ActivationDetail\Entity::EXPECTED_MONTHLY_GMV],
                self::SALES_POC                        => $bankingAccount->spocs->first() ? $bankingAccount->spocs->first()['name'] : '',
                self::SALES_POC_PHONE_NUMBER           => $bankingAccountActivationDetails[ActivationDetail\Entity::SALES_POC_PHONE_NUMBER],
                self::GREEN_CHANNEL                    => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($additionalDetails, ActivationDetail\Entity::GREEN_CHANNEL)),
                self::FOS                              => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($additionalDetails, ActivationDetail\Entity::FEET_ON_STREET)),
                self::REVIVED_LEAD                     => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($additionalDetails, ActivationDetail\Entity::REVIVED_LEAD)),
                self::OPS_POC_NAME                     => $opsPOCName,
                self::OPS_POC_EMAIL                    => $opsPOCEmail,
                self::DOCKET_DELIVERY_DATE             => $this->convertEpochToDateFormat(ActivationDetail\Entity::extractFieldFromJSONField($additionalDetails, ActivationDetail\Entity::DOCKET_DELIVERED_DATE)),
                self::STATUS                           => Status::transformFromInternalToExternal($bankingAccount[BankingAccount\Entity::STATUS]),
                self::SUB_STATUS                       => $bankingAccount[BankingAccount\Entity::SUB_STATUS] ? Status::sanitizeStatus($bankingAccount[BankingAccount\Entity::SUB_STATUS]) : '',
                self::ASSIGNEE                         => $bankingAccountActivationDetails[BankingAccount\Entity::ASSIGNEE_TEAM] === ActivationDetail\Entity::BANK ? 'Bank' : 'RZP',
                self::MID_OFFICE_POC                   => $bankPocUserName,
                self::LEAD_REFERRED_BY_RBL_STAFF       => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::LEAD_REFERRED_BY_RBL_STAFF)),
                self::OFFICE_AT_DIFFERENT_LOCATIONS    => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::OFFICE_DIFFERENT_LOCATIONS)),
                self::CUSTOMER_APPOINTMENT_DATE        => $this->convertEpochToDateFormat($customerAppointmentDate),
                self::APPOINTMENT_TAT                  => $appointmentTAT,
                self::LEAD_IR_NO                       => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::LEAD_IR_NUMBER),
                self::RM_NAME                          => $bankingAccountActivationDetails[ActivationDetail\Entity::RM_NAME],
                self::RM_MOBILE_NO                     => $bankingAccountActivationDetails[ActivationDetail\Entity::RM_PHONE_NUMBER],
                self::BRANCH_CODE                      => $branchCode,
                self::BRANCH_NAME                      => $branchName,
                self::BM                               => $branchManagerName,
                self::BM_MOBILE_NO                     => $branchManagerPhone,
                self::TL                               => $pcarmAH,
                self::CLUSTER                          => $branchCluster,
                self::REGION                           => $branchRegion,
                self::DOC_COLLECTION_DATE              => $this->convertEpochToDateFormat($docCollectionDate),
                self::DOC_COLLECTION_TAT               => $docCollectionTAT,
                self::IP_CHEQUE_VALUE                  => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::IP_CHEQUE_VALUE),
                self::API_DOCS_RECEIVED_WITH_CA_DOCS   => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_DOCS_RECEIVED_WITH_CA_DOCS)),
                self::API_DOC_DELAY_REASON             => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_DOCS_DELAY_REASON),
                self::REVISED_DECLARATION              => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::REVISED_DECLARATION)),
                self::ACCOUNT_IR_NO                    => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::ACCOUNT_OPENING_IR_NUMBER),
                self::ACCT_LOGIN_DATE                  => $this->convertEpochToDateFormat($accountLoginDate),
                self::IR_LOGIN_TAT                     => $irLoginTAT,
                self::PROMO_CODE                       => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::PROMO_CODE),
                self::CASE_LOGIN                       => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::CASE_LOGIN_DIFFERENT_LOCATIONS)),
                self::SR_NO                            => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::SR_NUMBER),
                self::ACCOUNT_OPEN_DATE                => $this->convertEpochToDateFormat($accountOpeningDate),
                self::ACCOUNT_IR_CLOSED_DATE           => $this->convertEpochToDateFormat($bankingAccountActivationDetails[ActivationDetail\Entity::ACCOUNT_OPEN_DATE]),
                self::AO_FTNR                          => $this->convertToYesNo($bankingAccountActivationDetails[ActivationDetail\Entity::ACCOUNT_OPENING_FTNR], 1, 0),
                self::AO_FTNR_REASONS                  => $bankingAccountActivationDetails[ActivationDetail\Entity::ACCOUNT_OPENING_FTNR_REASONS],
                self::AO_TAT_EXCEPTION                 => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::ACCOUNT_OPENING_TAT_EXCEPTION)),
                self::AO_TAT_EXCEPTION_REASON          => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::ACCOUNT_OPENING_TAT_EXCEPTION_REASON),
                self::API_IR_NO                        => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_IR_NUMBER),
                self::API_IR_LOGIN_DATE                => $this->convertEpochToDateFormat($apiOnboardingLoginDate),
                self::LDAP_ID_MAIL_DATE                => $this->convertEpochToDateFormat($bankingAccountActivationDetails[ActivationDetail\Entity::LDAP_ID_MAIL_DATE]),
                self::API_REQUEST_TAT                  => $apiRequestTAT,
                self::API_IR_CLOSED_DATE               => $this->convertEpochToDateFormat($apiIRClosedDate),
                self::API_REQUEST_PROCESSING_TAT       => $apiRequestProcessingTAT,
                self::API_FTNR                         => $this->convertToYesNo($bankingAccountActivationDetails[ActivationDetail\Entity::API_ONBOARDING_FTNR], 1, 0),
                self::API_FTNR_REASONS                 => $bankingAccountActivationDetails[ActivationDetail\Entity::API_ONBOARDING_FTNR_REASONS],
                self::API_TAT_EXCEPTION                => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_ONBOARDING_TAT_EXCEPTION)),
                self::API_TAT_EXCEPTION_REASON         => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_ONBOARDING_TAT_EXCEPTION_REASON),
                self::CORP_ID_MAIL_DATE                => $this->convertEpochToDateFormat(ActivationDetail\Entity::extractFieldFromJSONField($additionalDetails, ActivationDetail\Entity::API_ONBOARDED_DATE)),
                self::RZP_CA_ACTIVATED_DATE            => $this->convertEpochToDateFormat($bankingAccountActivationDetails[ActivationDetail\Entity::RZP_CA_ACTIVATED_DATE]),
                self::UPI_CREDENTIALS_DATE             => $this->convertEpochToDateFormat($bankingAccountActivationDetails[ActivationDetail\Entity::UPI_CREDENTIAL_RECEIVED_DATE]),
                self::UPI_CREDENTIALS_NOT_DONE_REMARKS => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::UPI_CREDENTIAL_NOT_DONE_REMARKS),
                self::DROP_OFF_DATE                    => $this->convertEpochToDateFormat($bankingAccountActivationDetails[ActivationDetail\Entity::DROP_OFF_DATE]),

                self::API_SERVICE_FIRST_QUERY          => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_SERVICE_FIRST_QUERY),
                self::API_BEYOND_TAT                   => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_BEYOND_TAT)),
                self::API_BEYOND_TAT_DEPENDENCY        => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_BEYOND_TAT_DEPENDENCY),
                self::FIRST_CALLING_TIME               => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::FIRST_CALLING_TIME),
                self::SECOND_CALLING_TIME              => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::SECOND_CALLING_TIME),
                self::WA_MESSAGE_SENT_DATE             => $this->convertEpochToDateFormat(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::WA_MESSAGE_SENT_DATE)),
                self::WA_MESSAGE_RESPONSE_DATE         => $this->convertEpochToDateFormat(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::WA_MESSAGE_RESPONSE_DATE)),
                self::API_DOCKET_RELATED_ISSUE         => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::API_DOCKET_RELATED_ISSUE),
                self::AOF_SHARED_WITH_MO               => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::AOF_SHARED_WITH_MO)),
                self::AOF_SHARED_DISCREPANCY           => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::AOF_SHARED_DISCREPANCY)),
                self::AOF_NOT_SHARED_REASON            => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::AOF_NOT_SHARED_REASON),
                self::CA_BEYOND_TAT_DEPENDENCY         => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::CA_BEYOND_TAT_DEPENDENCY),
                self::CA_BEYOND_TAT                    => $this->convertToYesNo(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::CA_BEYOND_TAT)),
                self::CA_SERVICE_FIRST_QUERY           => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::CA_SERVICE_FIRST_QUERY),
                self::CUSTOMER_APPOINTMENT_BOOKING_DATE=> $this->convertEpochToDateFormat(ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::CUSTOMER_APPOINTMENT_BOOKING_DATE)),
                self::CUSTOMER_ONBOARDING_TAT          => $customerOnboardingTat,
                self::LEAD_IR_STATUS                   => ActivationDetail\Entity::extractFieldFromJSONField($rblActivationDetails, ActivationDetail\Entity::LEAD_IR_STATUS),
            ];
        }

        return $fileInput;
    }
}
