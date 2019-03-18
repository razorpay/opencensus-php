import MainNavLink from 'merchant/components/MainNavLink';

export default function PartnerNavLinks() {
  return (
    <>
      <MainNavLink
        label="Affiliated Accounts"
        icon="i i-done-all text-success"
        to="/partners/submerchants"
        additionalCondition={user => user.isAllowedView('submerchants')}
        exact
      />

      <MainNavLink
        label="Earnings"
        icon="i i-done-all text-success"
        to="/partners/earnings"
        featureEnabled="show_commissions"
        additionalCondition={user => user.isAllowedView('earnings')}
        exact
      />

      <MainNavLink
        label="Settings"
        icon="i i-settings text-warning"
        to="/partners/settings"
        additionalCondition={user =>
          user.isAllowedView('partner_settings') &&
          user.isPartner('aggregator', 'fully_managed')
        }
        exact
      />

      <MainNavLink
        label="Applications"
        icon="i i-settings text-warning"
        to="/partners/applications"
        additionalCondition={user =>
          user.isAllowedView('partner_applications') &&
          user.isPartner('pure_platform')
        }
        exact
      />
    </>
  );
}
