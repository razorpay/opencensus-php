<?php


namespace RZP\Models\Merchant\Website;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\BusinessType;
use MVanDuijker\TransactionalModelEvents as TransactionalModelEvents;

/**
 * Class Entity
 *
 * @property Merchant\Entity $merchant
 * @property Detail\Entity $merchantDetail
 *
 * @package RZP\Models\Merchant\Website
 */
class Entity extends Base\PublicEntity
{
    use TransactionalModelEvents\TransactionalAwareEvents;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const DELIVERABLE_TYPE      = 'deliverable_type';
    const SHIPPING_PERIOD       = 'shipping_period';
    const REFUND_REQUEST_PERIOD = 'refund_request_period';
    const REFUND_PROCESS_PERIOD = 'refund_process_period';
    const WARRANTY_PERIOD       = 'warranty_period';

    const MERCHANT_WEBSITE_DETAILS = 'merchant_website_details';
    const ADMIN_WEBSITE_DETAILS    = 'admin_website_details';
    const ADDITIONAL_DATA          = 'additional_data';
    const STATUS                   = 'status';
    const GRACE_PERIOD             = 'grace_period';
    const SEND_COMMUNICATION       = 'send_communication';
    const AUDIT_ID                 = 'audit_id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $entity             = 'merchant_website';

    protected $generateIdOnCreate = true;

    protected $fillable           = [
        self::MERCHANT_ID,
        self::DELIVERABLE_TYPE,
        self::SHIPPING_PERIOD,
        self::REFUND_REQUEST_PERIOD,
        self::REFUND_PROCESS_PERIOD,
        self::WARRANTY_PERIOD,
        self::MERCHANT_WEBSITE_DETAILS,
        self::ADMIN_WEBSITE_DETAILS,
        self::ADDITIONAL_DATA,
        self::STATUS,
        self::GRACE_PERIOD,
        self::SEND_COMMUNICATION,
        self::AUDIT_ID
    ];

    protected $public             = [
        self::ID,
        self::DELIVERABLE_TYPE,
        self::SHIPPING_PERIOD,
        self::REFUND_REQUEST_PERIOD,
        self::REFUND_PROCESS_PERIOD,
        self::WARRANTY_PERIOD,
        self::MERCHANT_WEBSITE_DETAILS,
        self::ADDITIONAL_DATA,
        self::STATUS,
    ];

    protected $adminRestricted    = [
        self::ID,
        self::DELIVERABLE_TYPE,
        self::SHIPPING_PERIOD,
        self::REFUND_REQUEST_PERIOD,
        self::REFUND_PROCESS_PERIOD,
        self::WARRANTY_PERIOD,
        self::MERCHANT_WEBSITE_DETAILS,
        self::ADMIN_WEBSITE_DETAILS,
        self::ADDITIONAL_DATA,
        self::STATUS,
        self::GRACE_PERIOD,
        self::SEND_COMMUNICATION,
    ];

    protected $casts              = [
        self::ADDITIONAL_DATA          => 'array',
        self::MERCHANT_WEBSITE_DETAILS => 'array',
        self::ADMIN_WEBSITE_DETAILS    => 'array',
    ];

    protected $defaults           = [
        self::ADDITIONAL_DATA          => null,
        self::MERCHANT_WEBSITE_DETAILS => null,
        self::ADMIN_WEBSITE_DETAILS    => null,
        self::DELIVERABLE_TYPE         => null,
        self::SHIPPING_PERIOD          => null,
        self::REFUND_REQUEST_PERIOD    => null,
        self::REFUND_PROCESS_PERIOD    => null,
        self::WARRANTY_PERIOD          => null,
        self::GRACE_PERIOD             => false,
        self::SEND_COMMUNICATION       => true,

    ];

    public function merchantDetail()
    {
        return $this->belongsTo('RZP\Models\Merchant\Detail\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getMerchantWebsiteDetails()
    {
        return $this->getAttribute(self::MERCHANT_WEBSITE_DETAILS);
    }

    public function getAdminWebsiteDetails()
    {
        return $this->getAttribute(self::ADMIN_WEBSITE_DETAILS);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }


    /*
     "merchant_website_details": {
           "contact_us": {
               "playstore_url": {
                   "https://play.google.com/store/apps/details?id=com.Slack&hl=en_IN&gl=US": {
                       "document_id": "JyJ2aph3msZl9r"
                    }
               },
               "updated_at": 1658906510
           }
       }
     */
    public function getMerchantDocumentId($sectionName, $urlType, $url)
    {
        $merchantWebsiteDetail = $this->getAttribute(self::MERCHANT_WEBSITE_DETAILS);

        return $merchantWebsiteDetail[$sectionName][$urlType][$url][Constants::DOCUMENT_ID] ?? null;
    }

    /* "admin_website_details": {
           "appstore_url": {
               "https://apps.apple.com/lol12345.com": {
                   "contact_us": {
                       "document_id": "JtDI0hskLtkqRr"
                   }
               }
           }
       }
    */
    public function getAdminDocumentId($urlType, $url, $sectionName)
    {
        $adminWebsiteDetail = $this->getAttribute(self::ADMIN_WEBSITE_DETAILS);

        return $adminWebsiteDetail[$urlType][$url][$sectionName][Constants::DOCUMENT_ID] ?? null;
    }

    /* "admin_website_details": {
       "appstore_url": {
           "https://apps.apple.com/lol12345.com": {
               "contact_us": {
                   "url": "https://apps.apple.com/lol12345.com/contact"
               }
           }
       }
   }
*/
    public function getAdminUrl($urlType, $url, $sectionName)
    {
        $adminWebsiteDetail = $this->getAttribute(self::ADMIN_WEBSITE_DETAILS);

        return $adminWebsiteDetail[$urlType][$url][$sectionName][Constants::URL] ?? null;
    }

    /* "merchant_website_details": {
       "refund": {
           "section_status": "2",
           "status":"submitted",
           "website": {
               "http://hello.com": {
                   "url": "http://hello.co.in/refund"
               }
           }
       }
   } */
    public function getSectionSubmissionStatus($sectionName)
    {
        $merchantWebsiteDetail = $this->getAttribute(self::MERCHANT_WEBSITE_DETAILS);

        return isset($merchantWebsiteDetail[$sectionName][Constants::STATUS]) === true ?
            $merchantWebsiteDetail[$sectionName][Constants::STATUS] :
            null;
    }

    /* "merchant_website_details": {
           "refund": {
               "section_status": "2",
               "website": {
                   "http://hello.com": {
                       "url": "http://hello.co.in/refund"
                   }
               }
           }
       }*/
    public function getSectionStatus($sectionName)
    {
        $merchantWebsiteDetail = $this->getAttribute(self::MERCHANT_WEBSITE_DETAILS);

        return isset($merchantWebsiteDetail[$sectionName][Constants::SECTION_STATUS]) === true ?
            $merchantWebsiteDetail[$sectionName][Constants::SECTION_STATUS] :
            null;
    }

    /*
      "merchant_website_details": {
               "contact_us": {
                   "playstore_url": {
                       "https://play.google.com/store/apps/details?id=com.Slack&hl=en_IN&gl=US": {
                           "document_id": "JyJ2aph3msZl9r"
                        }
                   },
                   "updated_at": 1658906510
               }
           }
    */
    public function getSectionUpdatedAt($sectionName)
    {
        $merchantWebsiteDetail = $this->getAttribute(self::MERCHANT_WEBSITE_DETAILS);

        return $merchantWebsiteDetail[$sectionName][Constants::UPDATED_AT] ?? null;
    }


    /*
     "merchant_website_details": {
           "privacy": {
               "updated_at": 1657783502,
               "section_status": "3",
               "published_url": "https://merchant.razorpay.com/privacy?id={id}",
               "status": "submitted"
           },
       }
     */
    public function getPublishedUrl($sectionName)
    {
        $merchantWebsiteDetail = $this->getAttribute(self::MERCHANT_WEBSITE_DETAILS);

        return $merchantWebsiteDetail[$sectionName][Constants::PUBLISHED_URL] ?? null;
    }
}
