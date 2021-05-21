import { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import MainNavLink from 'merchant_common/components/MainNavLink';
import ShowWhen from 'merchant/components/ShowWhen';
import LocalStorageService from 'common/utils/localStorage';
import { getSettlementStatus } from 'merchant/views/Capital/utils';

export default function MerchantNavLinks(props) {
  const { routes, isReportsPending, isChargeAtWillEnabled, isSettlementEnabled, user } = props;
  const showMyAccountCutomBadge = !LocalStorageService.getItem('rtb_page_visited');
  const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');
  const [settlementExists, setSettlementExists] = useState(true);

  useEffect(() => {
    checkIfFirstEverSettlement();
  }, []);

  const checkIfFirstEverSettlement = () => {
    const settlementStatus = getSettlementStatus(user.current);
    setSettlementExists(settlementStatus);
  };

  return (
    <>
      <MainNavLink
        label="Home"
        icon="i i-chart text-info"
        to="/dashboard"
        exact
        type="general"
        additionalCondition={(user) => user.isAllowedView('home')}
      />
      <MainNavLink
        label="Transactions"
        icon="i i-repeat text-primary"
        type="general"
        to={routes.transactions}
        additionalCondition={(user) => user.isAllowedMultiple('payments orders refunds')}
      />
      <MainNavLink
        isNew={!settlementExists && esOndemandSettlementEnabled}
        label="Settlements"
        icon="i i-done-all text-success"
        type="general"
        to="/settlements"
        isSettlementEnabled={isSettlementEnabled}
        additionalCondition={(user) => user.isAllowedView('settlements')}
      />

      <div class="divider" />

      <MainNavLink
        label="Invoices"
        icon="i i-notes text-warning"
        type="product"
        to={routes.invoices}
        additionalCondition={(user) => user.isAllowedView('invoices')}
      />
      <MainNavLink
        label="Payment Links"
        type="product"
        icon="i i-link text-primary"
        to={routes.paymentlinks}
        additionalCondition={(user) => user.isAllowedView('payment_links')}
      />
      <MainNavLink
        label="Payment Pages"
        type="product"
        icon="i i-payment-pages text-warm temp-icon-style"
        to={routes.paymentpages}
        additionalCondition={(user) => user.isAllowedView('payment_pages')}
      />
      <MainNavLink
        type="product"
        label="Payment Button"
        icon="i i-payment-button"
        to={
          user.isPaymentButtonEnabledByRazorX ? routes.paymentbuttons : routes.subscription_buttons
        }
        additionalCondition={(user) =>
          user.isAllowedMultiple('payment_buttons subscription_buttons') &&
          (user.isPaymentButtonEnabledByRazorX || user.isSubscriptionButtonEnabled)
        }
      />
      <MainNavLink
        label="Route"
        type="product"
        to={routes.marketplace}
        icon="i i-route text-success"
        additionalCondition={(user) => user.isAllowedView('marketplace')}
      />
      <MainNavLink
        label="Subscriptions"
        type="product"
        icon="i i-refresh text-info"
        additionalCondition={(user) => user.isAllowedView('subscriptions')}
        to={routes[isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions']}
      />

      <MainNavLink
        label="QR codes"
        type="product"
        icon="i i-qr-code text-warm"
        additionalCondition={(user) => user.isAllowedView('qr_codes') && user.isQRCodesEnabled}
        to={routes.qrCodes}
        isComingSoon={user.isQRCodeComingSoonEnabled}
      />

      <MainNavLink
        label="Smart Collect"
        type="product"
        icon="i i-account-balance text-danger"
        to={routes.smartCollect}
        additionalCondition={(user) => user.isAllowedView('virtual_accounts')}
      />

      <ShowWhen featureEnabled="raas">
        <MainNavLink
          label="Optimizer"
          type="product"
          icon="i i-routing text-warm temp-icon-style"
          to="/navigator/rules"
        />
      </ShowWhen>

      <MainNavLink
        label="Customers"
        type="general"
        icon="i i-people text-warning"
        to="/customers"
        additionalCondition={(user) => user.isAllowedView('customers')}
      />

      <MainNavLink
        label="Offers"
        icon="i i-offer text-success"
        type="general"
        to="/offers"
        additionalCondition={(user) => user.isAllowedView('offers')}
      />

      <MainNavLink
        label="Checkout Rewards"
        icon="i i-rewards text-danger"
        to="/checkout-rewards"
        additionalCondition={(user) => user.isAllowedView('checkoutrewards')}
        isNew
      />

      <MainNavLink
        label="Loans"
        icon="i fa fa-inr text-warm"
        to="/capital/loans/apply"
        isNew={true}
        additionalCondition={(user) => user.isAllowedView('loans') && user.isLoansEnabled}
      />

      <MainNavLink
        label="Cash Advance"
        icon="i fa fa-star text-warning"
        to="/capital/cash-advance/"
        isNew={true}
        additionalCondition={(user) =>
          user.isAllowedView('cash_advance') &&
          (user.isLOCEnabled || user.isCashAdvanceStage2Enabled || user.isWithdrawFeatureEnabled)
        }
      />

      <MainNavLink
        label="Corporate Cards"
        icon="i fa fa-credit-card text-warm"
        to="/capital/corporate-cards/"
        isNew={true}
        additionalCondition={(user) => user.isCardsLOSEnabled}
      />

      <div class="divider" />

      <MainNavLink
        label="Reports"
        icon="i i-books text-danger"
        type="general"
        to="/reports"
        additionalCondition={(user) => user.isAllowedView('reports')}
        isPending={isReportsPending}
      />
      <MainNavLink
        label="My Account"
        type="general"
        icon="i i-account text-primary"
        additionalCondition={(user) =>
          user.isAllowedMultiple('profile credits add_funds team referrals')
        }
        to={routes.account}
        customBadge={
          showMyAccountCutomBadge ? (
            <>
              NEW <i className="i i-rtb_new" />
            </>
          ) : (
            false
          )
        }
      />
      <MainNavLink
        label="Settings"
        icon="i i-settings text-warning"
        type="general"
        to={routes.settings}
        additionalCondition={(user) =>
          user.isAllowedMultiple('webhooks applications configuration api_keys')
        }
      />
    </>
  );
}
