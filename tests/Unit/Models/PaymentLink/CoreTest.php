<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Constants\Mode;
use RZP\Models\PaymentLink;
use Illuminate\Support\Facades\Config;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class CoreTest extends BaseTest
{
    use DbEntityFetchTrait;
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';

    /**
     * @group nocode_pp_risk_check_url
     */
    public function testGetRiskCheckUrlPageWithId()
    {
        $core = new Paymentlink\Core;

        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE => PaymentLink\ViewType::PAGE,
        ]);

        $url = $core->getRiskCheckUrl($pl->getId(), $pl->getMerchantId());

        $expectedUrl = Config::get('app.payment_link_hosted_base_url')
            . "/"
            .  $pl->getPublicId()
            . "/view";

        $this->assertEquals($expectedUrl, $url);
    }

    /**
     * @group nocode_pp_risk_check_url
     */
    public function testGetRiskCheckUrlPageWithPagesDomainAndSlug()
    {
        Config::set('app.nocode.cache.custom_url_ttl', 0);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $core = new Paymentlink\Core;

        $slugUrl = Config::get('app.payment_link_hosted_base_url') . "/myslug";

        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE   => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL   => $slugUrl
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $domain = PaymentLink\NocodeCustomUrl\Entity::determineDomainFromUrl(Config::get('app.payment_link_hosted_base_url'));

        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN  => $domain,
            PaymentLink\NocodeCustomUrl\Entity::SLUG    => "myslug",
        ], $pl);

        $url = $core->getRiskCheckUrl($pl->getId(), $pl->getMerchantId());

        $this->assertEquals($slugUrl, $url);
    }

    /**
     * @group nocode_pp_risk_check_url
     */
    public function testGetRiskCheckUrlPageWithCustomDomainAndSlug()
    {
        Config::set('app.nocode.cache.custom_url_ttl', 0);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $core = new Paymentlink\Core;

        $domain = "mydomain.com";
        $slug   = "myslug";

        $slugUrl = "https://" . $domain . "/" . $slug;

        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE   => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL   => $slugUrl
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN  => $domain,
            PaymentLink\NocodeCustomUrl\Entity::SLUG    => $slug,
        ], $pl);

        $settings[PaymentLink\Entity::CUSTOM_DOMAIN]    = $domain;
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $url = $core->getRiskCheckUrl($pl->getId(), $pl->getMerchantId());

        $this->assertEquals($slugUrl, $url);
    }

    /**
     * @group nocode_pp_risk_check_url
     */
    public function testGetRiskCheckUrlPageWithCustomDomainAndEmptySlug()
    {
        Config::set('app.nocode.cache.custom_url_ttl', 0);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $core = new Paymentlink\Core;

        $domain = "mydomain.com";
        $slug   = "";

        $slugUrl = "https://" . $domain . "/" . $slug;

        $this->createPaymentLink(self::TEST_PL_ID, [
            PaymentLink\Entity::VIEW_TYPE   => PaymentLink\ViewType::PAGE,
            PaymentLink\Entity::SHORT_URL   => $slugUrl
        ]);

        $pl = $this->getDbLastEntity("payment_link", Mode::LIVE);

        $this->createNocodeCustomUrl([
            PaymentLink\NocodeCustomUrl\Entity::DOMAIN  => $domain,
            PaymentLink\NocodeCustomUrl\Entity::SLUG    => $slug,
        ], $pl);

        $settings[PaymentLink\Entity::CUSTOM_DOMAIN]    = $domain;
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $url = $core->getRiskCheckUrl($pl->getId(), $pl->getMerchantId());

        $this->assertEquals($slugUrl, $url);
    }
}
