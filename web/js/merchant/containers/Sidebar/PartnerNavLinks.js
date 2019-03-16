import MainNavLink from 'merchant/components/MainNavLink';

export default function PartnerNavLinks() {
  return (
    <>
      <MainNavLink
        label="Affiliated Accounts"
        icon="i i-done-all text-success"
        to="/submerchants"
        exact
      />

      <MainNavLink
        label="Settings"
        icon="i i-settings text-warning"
        to={'/submerchants/settings'}
        exact
      />
    </>
  );
}
