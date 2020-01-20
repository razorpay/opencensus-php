import MainNavLink from 'merchant/components/MainNavLink';

export default function MerchantNavLinks(props) {
  const {
    routes,
    isReportsPending,
    isChargeAtWillEnabled,
    isSettlementEnabled,
    user,
  } = props;
  return (
    <>
      <MainNavLink
        label="Home"
        icon="i i-chart text-info"
        to="/dashboard"
        exact
        additionalCondition={user => user.isAllowedView('home')}
      />
      <MainNavLink
        label="Transactions"
        icon="i i-repeat text-primary"
        to={routes.transactions}
        additionalCondition={user =>
          user.isAllowedMultiple('payments orders refunds')
        }
      />
      <MainNavLink
        label="Settlements"
        icon="i i-done-all text-success"
        to="/settlements"
        isSettlementEnabled={isSettlementEnabled}
        additionalCondition={user => user.isAllowedView('settlements')}
      />

      <div class="divider" />

      <MainNavLink
        label="Invoices"
        icon="i i-notes text-warning"
        to={routes.invoices}
        additionalCondition={user => user.isAllowedView('invoices')}
      />
      <MainNavLink
        label="Payment Links"
        icon="i i-link text-primary"
        to={routes.paymentlinks}
        additionalCondition={user => user.isAllowedView('payment_links')}
      />
      <MainNavLink
        label="Payment Pages"
        icon="i i-payment-pages text-warm temp-icon-style"
        to={routes.paymentpages}
        additionalCondition={user => user.isAllowedView('payment_pages')}
      />
      <MainNavLink
        label="Route"
        icon="i i-store text-success"
        to={routes.marketplace}
        additionalCondition={user => user.isAllowedView('marketplace')}
      />
      <MainNavLink
        label="Subscriptions"
        icon="i i-refresh text-info"
        additionalCondition={user => user.isAllowedView('subscriptions')}
        to={routes[isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions']}
      />
      <MainNavLink
        label="Smart Collect"
        icon="i i-account-balance text-danger"
        to="/virtualaccounts"
        additionalCondition={user => user.isAllowedView('virtual_accounts')}
      />

      <MainNavLink
        label="Customers"
        icon="i i-people text-warning"
        to="/customers"
        additionalCondition={user => user.isAllowedView('customers')}
      />

      <MainNavLink
        label="Offers"
        icon="i i-offer text-success"
        to="/offers"
        additionalCondition={user => user.isAllowedView('settlements')}
      />

      <div class="divider" />

      <MainNavLink
        label="Reports"
        icon="i i-books text-danger"
        to="/reports"
        additionalCondition={user => user.isAllowedView('reports')}
        isPending={isReportsPending}
      />
      <MainNavLink
        label="My Account"
        icon="i i-account text-primary"
        additionalCondition={user =>
          user.isAllowedMultiple('profile credits add_funds team referrals')
        }
        to={routes.account}
      />
      <MainNavLink
        label="Settings"
        icon="i i-settings text-warning"
        to={routes.settings}
        additionalCondition={user =>
          user.isAllowedMultiple('webhooks applications configuration api_keys')
        }
      />
    </>
  );
}
