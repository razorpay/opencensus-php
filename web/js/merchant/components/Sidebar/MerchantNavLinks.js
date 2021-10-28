import React, { useEffect, useState } from 'react';
import QueryString from 'query-string';
import { connect } from 'react-redux';
import MainNavLink from 'merchant_common/components/MainNavLink';
import ShowWhen from 'merchant/components/ShowWhen';
import * as LocalStorageService from 'common/utils/localStorage';
import { getSettlementStatus } from 'merchant/views/Capital/utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const RECOMMANDED_PRODUCT_LIST = [
  'payment_gateway',
  'payment_page',
  'payment_link',
  'payment_button',
  'smart_collect',
  'route',
  'subscriptions',
];

function MerchantNavLinks(props) {
  const { routes, isReportsPending, isChargeAtWillEnabled, isSettlementEnabled, user } = props;
  const showMyAccountCutomBadge = !LocalStorageService.getItem('rtb_page_visited');
  const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');
  const [settlementExists, setSettlementExists] = useState(true);
  const getLandingProduct =
    LocalStorageService.getItem('merchant_landing_page') ||
    LocalStorageService.getItem('default_product_page');

  const isRecommendProduct =
    RECOMMANDED_PRODUCT_LIST.includes(getLandingProduct) &&
    props.payment === 0 &&
    user.isProductRecommendationEnabled;

  const checkIfFirstEverSettlement = () => {
    const settlementStatus = getSettlementStatus(user.current);
    const isDisabled =
      settlementStatus === 'disableAnimation' || settlementStatus === 'disableAnimationOnReload';
    setSettlementExists(isDisabled ? false : settlementStatus);
  };
  useEffect(() => {
    checkIfFirstEverSettlement();
  }, []);

  useEffect(() => {
    //set recommend product to localstorage.
    const query = QueryString.parse(window.location.search);
    if (query?.recommended_product && user.isProductRecommendationEnabled) {
      LocalStorageService.setItem('merchant_landing_page', query.recommended_product);
    } else if (
      !getLandingProduct &&
      user.isProductRecommendationEnabled &&
      typeof props.payment === 'object'
    ) {
      //set default payment link as a recommend product.
      LocalStorageService.setItem('default_product_page', 'payment_link');
    }
  }, []);

  useEffect(() => {
    if (getLandingProduct && isRecommendProduct) {
      analyticsTrack({
        objectName: 'Try Tag',
        actionName: 'displayed',
        screen: 'home page',
        properties: {
          product_name: getLandingProduct,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }, [getLandingProduct, isRecommendProduct]);

  return (
    <>
      <MainNavLink
        label="Home"
        icon="i i-chart text-info"
        to="/dashboard"
        exact
        type="general"
        additionalCondition={(currentUser) => currentUser.isAllowedView('home')}
      />
      <MainNavLink
        label="Transactions"
        icon="i i-repeat text-primary"
        type="general"
        to={routes.transactions}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedMultiple('payments orders refunds')
        }
      />
      <MainNavLink
        isNew={!settlementExists && esOndemandSettlementEnabled && !isRecommendProduct}
        label="Settlements"
        icon="i i-done-all text-success"
        type="general"
        to="/settlements"
        isSettlementEnabled={isSettlementEnabled && !isRecommendProduct}
        additionalCondition={(currentUser) => currentUser.isAllowedView('settlements')}
      />

      <div class="divider" />

      <MainNavLink
        label="Invoices"
        icon="i i-notes text-warning"
        type="product"
        to={routes.invoices}
        additionalCondition={(currentUser) => currentUser.isAllowedView('invoices')}
      />
      <MainNavLink
        label="Payment Links"
        type="product"
        icon="i i-link text-primary"
        to={routes.paymentlinks}
        additionalCondition={(currentUser) => currentUser.isAllowedView('payment_links')}
        customBadge={
          getLandingProduct === 'payment_link' &&
          props.payment === 0 &&
          user.isProductRecommendationEnabled
            ? 'try'
            : ''
        }
      />
      <MainNavLink
        label="Payment Pages"
        type="product"
        icon="i i-payment-pages text-warm temp-icon-style"
        to={routes.paymentpages}
        additionalCondition={(currentUser) => currentUser.isAllowedView('payment_pages')}
        customBadge={
          getLandingProduct === 'payment_page' &&
          props.payment === 0 &&
          user.isProductRecommendationEnabled
            ? 'try'
            : ''
        }
      />
      <MainNavLink
        label="Stores"
        icon="i i-store-product text-danger"
        type="product"
        to={routes.stores}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('stores') && currentUser.isStoresEnabled
        }
      />
      <MainNavLink
        type="product"
        label="Payment Button"
        icon="i i-payment-button"
        to={
          user.isPaymentButtonEnabledByRazorX ? routes.paymentbuttons : routes.subscription_buttons
        }
        additionalCondition={(currentUser) =>
          currentUser.isAllowedMultiple('payment_buttons subscription_buttons') &&
          (currentUser.isPaymentButtonEnabledByRazorX || currentUser.isSubscriptionButtonEnabled)
        }
        customBadge={
          ['payment_button', 'payment_gateway'].includes(getLandingProduct) &&
          props.payment === 0 &&
          user.isProductRecommendationEnabled
            ? 'try'
            : ''
        }
      />
      <MainNavLink
        label="Route"
        type="product"
        to={routes.marketplace}
        icon="i i-route text-success"
        additionalCondition={(currentUser) => currentUser.isAllowedView('marketplace')}
        customBadge={
          getLandingProduct === 'route' &&
          props.payment === 0 &&
          user.isProductRecommendationEnabled
            ? 'try'
            : ''
        }
      />
      <MainNavLink
        label="Subscriptions"
        type="product"
        icon="i i-refresh text-info"
        additionalCondition={(currentUser) => currentUser.isAllowedView('subscriptions')}
        to={routes[isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions']}
        customBadge={
          getLandingProduct === 'subscriptions' &&
          props.payment === 0 &&
          user.isProductRecommendationEnabled
            ? 'try'
            : ''
        }
      />

      <MainNavLink
        label="QR Codes"
        type="product"
        icon="i i-qr-code text-warm"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('qr_codes') && currentUser.isQRCodesEnabled
        }
        to={routes.qrCodes}
        isNew={user.isQRCodeProductEnabled && !isRecommendProduct}
      />

      <MainNavLink
        label="Smart Collect"
        type="product"
        icon="i i-account-balance text-danger"
        to={routes.smartCollect}
        additionalCondition={(currentUser) => currentUser.isAllowedView('virtual_accounts')}
        customBadge={
          getLandingProduct === 'smart_collect' &&
          props.payment === 0 &&
          user.isProductRecommendationEnabled
            ? 'try'
            : ''
        }
      />

      <MainNavLink
        label="BBPS"
        type="product"
        image="/dist/css/assets/bbps.png"
        to={routes.bbps}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('bbps') && currentUser.isBbpsEnabled
        }
      />

      <ShowWhen featureEnabled="raas">
        <MainNavLink
          label="Optimizer"
          type="product"
          icon="i i-routing text-warm temp-icon-style"
          to="/optimizer/rules"
        />
      </ShowWhen>

      <MainNavLink
        label="Customers"
        type="general"
        icon="i i-people text-warning"
        to="/customers"
        additionalCondition={(currentUser) => currentUser.isAllowedView('customers')}
      />

      <MainNavLink
        label="Offers"
        icon="i i-offer text-success"
        type="general"
        to="/offers"
        additionalCondition={(currentUser) => currentUser.isAllowedView('offers')}
      />

      <MainNavLink
        label="Checkout Rewards"
        icon="i i-rewards text-danger"
        to="/checkout-rewards"
        additionalCondition={(currentUser) => currentUser.isAllowedView('checkoutrewards')}
        isNew={!isRecommendProduct}
      />

      <MainNavLink
        label="Working Capital Loans"
        icon="i fa fa-inr text-warm"
        to="/capital/non-fldg-loans/"
        additionalCondition={(currentUser) => currentUser.isNonFldgLoansEnabled}
        isNew={false}
      />

      <MainNavLink
        label="Loans"
        icon="i fa fa-inr text-warm"
        to="/capital/loans/apply"
        isNew={!isRecommendProduct}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('loans') && currentUser.isLoansEnabled
        }
      />

      <MainNavLink
        label="Cash Advance"
        icon="i fa fa-star text-warning"
        to="/capital/cash-advance/"
        isNew={!isRecommendProduct}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('cash_advance') &&
          (currentUser.isLOCEnabled ||
            currentUser.isCashAdvanceStage2Enabled ||
            currentUser.isWithdrawFeatureEnabled)
        }
      />

      <MainNavLink
        label="Corporate Cards"
        icon="i fa fa-credit-card text-warm"
        to="/capital/corporate-cards/"
        isNew={!isRecommendProduct}
        additionalCondition={(currentUser) => currentUser.isCardsLOSEnabled}
      />

      <div class="divider" />

      <MainNavLink
        label="Reports"
        icon="i i-books text-danger"
        type="general"
        to="/reports"
        additionalCondition={(currentUser) => currentUser.isAllowedView('reports')}
        isPending={isReportsPending}
      />
      <MainNavLink
        label="My Account"
        type="general"
        icon="i i-account text-primary"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedMultiple('profile credits add_funds team referrals')
        }
        to={routes.account}
        customBadge={
          showMyAccountCutomBadge && !isRecommendProduct ? (
            <>
              NEW <i className="i i-rtb_new" />
            </>
          ) : (
            ''
          )
        }
      />
      <MainNavLink
        label="Settings"
        icon="i i-settings text-warning"
        type="general"
        to={routes.settings}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedMultiple('webhooks applications configuration api_keys')
        }
      />
    </>
  );
}

const mapStateToProps = (state) => ({
  payment: state.transactionAmount.amount,
});

export default connect(mapStateToProps)(MerchantNavLinks);
