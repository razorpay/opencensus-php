import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import MainNavLink from 'merchant_common/components/MainNavLink';
import MagicCheckoutNavLink from 'merchant/components/Sidebar/MagicCheckoutNavLink';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import {
  getIsBankingEnabled,
  trackCashAdvanceSidebarLinkClicked,
  trackCashAdvanceSidebarLinkRendered,
} from './helpers';
import { trackViewedBankingNavBar } from './ga';
import BBPSImage from 'assets/bbps.png';
import * as LocalStorageService from 'common/utils/localStorage';
import {
  setRecommendedProduct,
  getRecommendedProductDetails,
} from 'merchant/components/Activation/ActivationUtils';

function MerchantNavLinks(props) {
  const {
    routes,
    isReportsPending,
    isChargeAtWillEnabled,
    isSettlementEnabled,
    user,
    payment,
  } = props;
  const showMyAccountCutomBadge = !LocalStorageService.getItem('rtb_page_visited');
  const { recommendedProduct, hasRecommendedProduct } = getRecommendedProductDetails();
  const isRecommendProduct =
    hasRecommendedProduct && payment === 0 && user.isProductRecommendationEnabled;

  // checks if the splitz experiment 'cash_advance_sidebar_position' variant is 'top'
  const isCashAdvanceSidebarPosTopExp = !!user.isCashAdvanceSidebarPosTopExp;
  const handleCashAdvanceClick = () => {
    trackCashAdvanceSidebarLinkClicked({
      user,
      position: isCashAdvanceSidebarPosTopExp ? 'top' : 'bottom',
    });
  };
  const cashAdvanceMainNavLink = (
    <MainNavLink
      label="Loans (Cash Advance)"
      icon="i fa fa-star text-warning"
      to="/capital/cash-advance/"
      isLive
      additionalCondition={(currentUser) =>
        currentUser.isAllowedView('cash_advance') &&
        (currentUser.isLOCEnabled ||
          currentUser.isCashAdvanceStage2Enabled ||
          currentUser.isWithdrawFeatureEnabled ||
          currentUser.isCashOnCardEnabled)
      }
      onClick={handleCashAdvanceClick}
    />
  );

  useEffect(() => {
    //set recommend product to localstorage.
    if (user.isProductRecommendationEnabled) {
      setRecommendedProduct({ shouldSetDefault: typeof payment === 'object' });
    }

    if (getIsBankingEnabled(user)) {
      trackViewedBankingNavBar();
    }

    trackCashAdvanceSidebarLinkRendered({
      user,
      position: isCashAdvanceSidebarPosTopExp ? 'top' : 'bottom',
    });
  }, []);

  const getProductBadge = (products) => {
    if (isRecommendProduct && products.includes(recommendedProduct)) {
      return 'try';
    }
    return null;
  };

  useEffect(() => {
    if (recommendedProduct && isRecommendProduct) {
      analyticsTrack({
        objectName: 'Try Tag',
        actionName: 'displayed',
        screen: 'home page',
        properties: {
          product_name: recommendedProduct,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }, [recommendedProduct, isRecommendProduct]);

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
        label="Settlements"
        icon="i i-done-all text-success"
        type="general"
        to="/settlements"
        isSettlementEnabled={isSettlementEnabled && !isRecommendProduct}
        additionalCondition={(currentUser) => currentUser.isAllowedView('settlements')}
      />

      <div class="divider" />

      {/* Renders sidebar link for Cash Advance at this position if splitz 
          experiment 'cash_advance_sidebar_position' variant is 'top' */}
      {isCashAdvanceSidebarPosTopExp && cashAdvanceMainNavLink}

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
        customBadge={getProductBadge(['payment_link'])}
      />
      <MainNavLink
        label="Payment Pages"
        type="product"
        icon="i i-payment-pages text-warm temp-icon-style"
        to={routes.paymentpages}
        additionalCondition={(currentUser) => currentUser.isAllowedView('payment_pages')}
        customBadge={getProductBadge(['payment_page'])}
      />
      <MainNavLink
        label="Stores"
        icon="i i-store-product text-danger"
        type="product"
        to={routes.stores}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('stores') && currentUser.isStoresEnabled
        }
        isNew={true}
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
        customBadge={getProductBadge(['payment_button', 'payment_gateway'])}
      />
      <MainNavLink
        label="Route"
        type="product"
        to={routes.marketplace}
        icon="i i-route text-success"
        additionalCondition={(currentUser) => currentUser.isAllowedView('marketplace')}
        customBadge={getProductBadge(['route'])}
      />
      <MainNavLink
        label="Banking"
        type="product"
        icon="i i-razorpayx text-razorpayx-orange"
        to="/razorpayx"
        isNew={true}
        additionalCondition={getIsBankingEnabled}
      />

      <MainNavLink
        label="Subscriptions"
        type="product"
        icon="i i-refresh text-info"
        additionalCondition={(currentUser) => currentUser.isAllowedView('subscriptions')}
        to={routes[isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions']}
        customBadge={getProductBadge(['subscriptions'])}
      />

      <MainNavLink
        label="QR Codes"
        type="product"
        icon="i i-qr-code text-warm"
        additionalCondition={(currentUser) => currentUser.isAllowedView('qr_codes')}
        to={routes.qrCodes}
        isNew={!isRecommendProduct}
      />

      <MainNavLink
        label="Smart Collect"
        type="product"
        icon="i i-account-balance text-danger"
        to={routes.smartCollect}
        additionalCondition={(currentUser) => currentUser.isAllowedView('virtual_accounts')}
        customBadge={getProductBadge(['smart_collect'])}
      />

      <MagicCheckoutNavLink>
        {(onClick) => (
          <MainNavLink
            label="Magic Checkout"
            icon="i i-magic-checkout"
            type="product"
            to={routes.magicCheckout}
            isNew={true}
            onClick={onClick}
          />
        )}
      </MagicCheckoutNavLink>

      <MainNavLink
        label="BBPS"
        type="product"
        image={BBPSImage}
        to={routes.bbps}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('bbps') && currentUser.isBbpsEnabled
        }
      />

      <MainNavLink
        label="Optimizer"
        type="product"
        icon="i i-routing text-warm temp-icon-style"
        to="/optimizer"
        additionalCondition={(currentUser) =>
          currentUser.isOptimizerEnabled || currentUser.isOptimizerOnboardingEnabled
        }
        isNew={user?.isOptimizerOnboardingEnabled && !user?.isOptimizerEnabled}
      />

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

      {!isCashAdvanceSidebarPosTopExp && cashAdvanceMainNavLink}

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
        label="Developers"
        type="general"
        icon="i i-code-white text-primary"
        to={routes.developersApis}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('developers_console') && currentUser.isDeveloperConsoleEnabled
        }
        isNew
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
