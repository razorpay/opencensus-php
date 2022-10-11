<?php

namespace RZP\Models\Admin\Report;

use Illuminate\Database\Eloquent\SoftDeletes;
use App;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Constants\Table;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Org;
use RZP\Constants\Environment;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\Entity
{
    protected $fillable = [
    ];

    protected $visible = [
    ];

    protected $public = [
    ];

    protected $guarded = [
    ];

    protected $casts = [
    ];

    protected $defaults = [
    ];

    protected $publicSetters = [
    ];

    public static $filters = [
        Constant::REPORT_TYPE_SUMMARY_MERCHANT  => [
        ],
        Constant::REPORT_TYPE_SUMMARY_PAYMENT   => [
        ],

        Constant::REPORT_TYPE_DETAILED_MERCHANT  => [
            Constant::FIELD_DATE,
//            Constant::FIELD_RANK,
            Constant::FIELD_PAYMENT_METHOD,
            Constant::FIELD_PARTNER_ID,
//            Constant::FIELD_MERCHANT_STATUS,

        ],
        Constant::REPORT_TYPE_DETAILED_TRANSACTION  => [
            Constant::FIELD_DATE,
            Constant::FIELD_PARTNER_ID,
        ],
        Constant::REPORT_TYPE_DETAILED_FAILURE  => [
            Constant::FIELD_DATE,
            Constant::FIELD_PARTNER_ID,
        ],
        Constant::REPORT_TYPE_DETAILED_FAILURE_DETAIL   => [
            Constant::FIELD_PAYMENT_METHOD, // to be checked
        ],
        Constant::DOWNLOAD => [],
        Constant::DEFAULT => [
            Constant::TO,
            Constant::FROM,
            Constant::SKIP,
            Constant::COUNT,
        ],
    ];

    public static $druidRepoMap = [
        // for devstack/stage envs
        '100000razorpay' => [
            Constant::MERCHANT_FACT_NAME => 'pinot.hdfc_banking_merchants_fact',
            Constant::PAYMENT_FACT_NAME  => 'pinot.hdfc_banking_payments_fact',
        ],

        // Prod HDFC Org
        Org\Entity::HDFC_ORG_ID => [
            Constant::MERCHANT_FACT_NAME => 'pinot.hdfc_banking_merchants_fact',
            Constant::PAYMENT_FACT_NAME  => 'pinot.hdfc_banking_payments_fact',
        ],

        // UAT Axis env -> axis org
        'FxrZxmcysGkcuU' => [
            Constant::MERCHANT_FACT_NAME => 'pinot.hdfc_banking_merchants_fact',
            Constant::PAYMENT_FACT_NAME  => 'pinot.hdfc_banking_payments_fact',
        ],
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * @return array[]
     * List of all filters available with the labels and data types
     */
    public static function getAllFilterFields(): array
    {
        $methodList  = Payment\Method::getMethodsNamesMap();

        return [
            Constant::FIELD_MERCHANT_ID => [
                Constant::LABEL     => 'Merchant Id',
                Constant::TYPE      => Constant::TYPE_STRING
            ],
            Constant::FIELD_PARTNER_ID => [
                Constant::LABEL     => 'Partner Id',
                Constant::TYPE      => Constant::TYPE_STRING
            ],
            Constant::FIELD_PAYMENT_METHOD => [
                Constant::LABEL     => 'Payment Method',
                Constant::TYPE      => Constant::TYPE_OBJECT,
                Constant::VALUES    => $methodList,
            ],
            Constant::FIELD_RANK => [
                Constant::LABEL     => 'Rank',
                Constant::TYPE      => Constant::TYPE_NUMBER,
            ],
            Constant::FIELD_MERCHANT_STATUS => [
                Constant::LABEL     => 'Merchant Status',
                Constant::TYPE      => Constant::TYPE_STRING
            ],
            Constant::FIELD_DATE => [
                Constant::LABEL     => 'Date Range',
                Constant::TYPE      => Constant::TYPE_DATE
            ],
            Constant::TO => [],
            Constant::FROM => [],
            Constant::COUNT => [],
            Constant::SKIP => [],
        ];
    }

    public static function getFiltersForReportType(string $type): array
    {
        $allFilters = self::getAllFilterFields();

        $requiredFilters = self::$filters[$type];

        return (array_only($allFilters, $requiredFilters));
    }

    public static function getFactNameByFactTypeForCurrentOrg($factType)
    {
        $app = App::getFacadeRoot();

        $admin = $app['basicauth']->getAdmin();

        if (is_null($admin) === true)
        {
            throw new Exception\InvalidPermissionException(
                'Unauthorized');
        }

        $orgId = $admin->getOrgId();

        $factList = self::$druidRepoMap;

        if(isset($factList[$orgId]) === false)
        {
            throw new Exception\InvalidPermissionException(
                'Unauthorized');
        }

        return $factList[$orgId][$factType];
    }
}
