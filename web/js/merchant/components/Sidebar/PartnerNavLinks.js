import MainNavLink from 'merchant_common/components/MainNavLink';

export default function PartnerNavLinks() {
  return (
    <>
      <MainNavLink
        label="Home"
        icon="i i-chart text-info"
        to="/partners"
        exact
        type="general"
        additionalCondition={(user) => user.isAllowedView('submerchants') && user.isPartnershipFUX}
        isNew={true}
      />

      <MainNavLink
        label="Affiliate Accounts"
        icon="i i-account-balance text-success"
        to="/partners/submerchants"
        additionalCondition={(user) => user.isAllowedView('submerchants')}
        exact
      />

      <MainNavLink
        label="Earnings"
        icon="i i-earnings text-primary"
        to="/partners/earnings/daily"
        additionalCondition={(user) =>
          user.isAllowedView('earnings') && user.isHavingPartnerConfigs
        }
        exact
      />

      <MainNavLink
        label="Subventions"
        icon="i i-earnings text-warning"
        to="/partners/subventions/daily"
        additionalCondition={(user) =>
          user.isAllowedView('earnings') && user.isHavingSubventionConfigs
        }
        exact
      />

      <MainNavLink
        label="Settings"
        icon="i i-settings text-warning"
        to="/partners/settings"
        additionalCondition={(user) =>
          user.isAllowedView('partner_settings') && user.isPartner('aggregator', 'fully_managed')
        }
        exact
      />

      <MainNavLink
        label="Applications"
        icon="i i-settings text-warning"
        to="/partners/applications"
        additionalCondition={(user) =>
          user.isAllowedView('partner_applications') && user.isPartner('pure_platform')
        }
        exact
      />

      <MainNavLink
        label="Reports"
        icon="i i-books text-danger"
        to="/partners/reports"
        isPending={false}
        // disabling for reseller partner not having partner configs
        additionalCondition={(user) => !user.isPartner('reseller') || user.isHavingPartnerConfigs}
      />
    </>
  );
}
