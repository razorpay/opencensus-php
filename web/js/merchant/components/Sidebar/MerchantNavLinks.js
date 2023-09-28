import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import MainNavLink from 'merchant_common/components/MainNavLink';
import MagicCheckoutNavLink from 'merchant/components/Sidebar/MagicCheckoutNavLink';
import { canViewCashAdvanceProduct, canViewLOCEMIProduct } from 'merchant/views/Capital/utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, isMobileResolution } from 'common/utils/rzp-utils';
import {
  getIsBankingEnabled,
  getIsPayrollWidgetEnabled,
  getIsShowAffordabilityWidget,
  getIsCheckoutPaymentMetricsEnabled,
  usePosOnboardingExperiment,
} from './helpers';
import { trackViewedBankingNavBar } from './ga';
import BBPSImage from 'assets/bbps.png';
import * as LocalStorageService from 'common/utils/localStorage';
import {
  setRecommendedProduct,
  getRecommendedProductDetails,
} from 'merchant/components/Activation/ActivationUtils';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

function MerchantNavLinks(props) {
  // prettier-ignore
  const { routes, isReportsPending, isChargeAtWillEnabled, isSettlementEnabled, user, payment } =
    props;
  const showMyAccountCutomBadge = !LocalStorageService.getItem('rtb_page_visited');
  const { recommendedProduct, hasRecommendedProduct } = getRecommendedProductDetails();
  const isRecommendProduct =
    hasRecommendedProduct && payment === 0 && user.isProductRecommendationEnabled;

  const { isPosOnboardingEnabled } = usePosOnboardingExperiment();

  useEffect(() => {
    //set recommend product to localstorage.
    if (user.isProductRecommendationEnabled) {
      setRecommendedProduct({ shouldSetDefault: typeof payment === 'object' });
    }

    if (getIsBankingEnabled(user)) {
      trackViewedBankingNavBar();
    }
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
        end
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
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('settlements') && currentUser.hideForNIASupportRole
        }
      />
      <MainNavLink
        label="International Payments"
        icon="i i-external-link text-primary"
        type="general"
        to="/payment-methods/international-payments"
        additionalCondition={(currentUser) => currentUser.isShowInternationalPaymentBtnExpEnabled}
      />

      <div class="divider" />

      <MainNavLink
        label="Loans (Cash Advance)"
        icon="i i-star text-warning"
        to="/capital/cash-advance"
        isLive
        additionalCondition={canViewCashAdvanceProduct}
      />
      <MainNavLink
        label="Line of Credit"
        icon="i i-star text-warning"
        to="/capital/line-of-credit"
        isLive
        additionalCondition={canViewLOCEMIProduct}
      />

      <MainNavLink
        label="Invoices"
        icon="i i-notes text-warning"
        type="product"
        to={routes.invoices}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('invoices') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Invoices)
        }
      />
      <MainNavLink
        label="Payment Links"
        type="product"
        icon="i i-link text-primary"
        to={routes.paymentlinks}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('payment_links') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentLinks)
        }
        customBadge={getProductBadge(['payment_link'])}
      />
      <MainNavLink
        label="Payment Pages"
        type="product"
        icon="i i-payment-pages text-warm temp-icon-style"
        to={routes.paymentpages}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('payment_pages') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentPages)
        }
        customBadge={getProductBadge(['payment_page'])}
      />
      <MainNavLink
        label="Stores"
        icon="i i-store-product text-danger"
        type="product"
        to={routes.stores}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('stores') &&
          currentUser.isStoresEnabled &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Stores)
        }
        isNew={true}
      />

      <MainNavLink
        label="API Keys & Plugins"
        icon="i i-api-keys-plugins text-tertiary"
        type="product"
        to={routes.apiKeys}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('api_keys') &&
          (currentUser.isProductLedOnboardingRZP || currentUser.isApiKeysRevampEnabled) &&
          currentUser.activated
        }
        isNew={true}
      />

      <MainNavLink
        type="product"
        label="Payment Button"
        icon="i i-payment-button text-glow"
        to={
          user.isPaymentButtonEnabledByRazorX ? routes.paymentbuttons : routes.subscription_buttons
        }
        additionalCondition={(currentUser) =>
          currentUser.isAllowedMultiple('payment_buttons subscription_buttons') &&
          (currentUser.isPaymentButtonEnabledByRazorX || currentUser.isSubscriptionButtonEnabled) &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentButtons)
        }
        customBadge={getProductBadge(['payment_button', 'payment_gateway'])}
      />
      <MainNavLink
        type="product"
        label="POS"
        icon="i i-pos pos-icon-styles text-primary"
        to={routes.pos}
        additionalCondition={() => isPosOnboardingEnabled}
      />

      <MainNavLink
        label="Route"
        type="product"
        to={routes.marketplace}
        icon="i i-route text-success"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('marketplace') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Marketplace)
        }
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
        label="Payroll"
        icon="i i-razorpayx text-razorpayx-orange"
        to="/payroll"
        isNew
        additionalCondition={getIsPayrollWidgetEnabled}
      />

      <MainNavLink
        label="Subscriptions"
        type="product"
        icon="i i-refresh text-info"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('subscriptions') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Subscriptions)
        }
        to={routes[isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions']}
        customBadge={getProductBadge(['subscriptions'])}
      />

      <MainNavLink
        label="Affordability"
        type="product"
        icon="i i-affordability text-primary"
        additionalCondition={getIsShowAffordabilityWidget}
        to="/affordability/widget"
        isNew={true}
      />

      <MainNavLink
        label="QR Codes"
        type="product"
        icon="i i-qr-code text-warm"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('qr_codes') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.QrCodes)
        }
        to={routes.qrCodes}
        isNew={!isRecommendProduct}
      />

      <MainNavLink
        label="Smart Collect"
        type="product"
        icon="i i-account-balance text-danger"
        to={routes.smartCollect}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('virtual_accounts') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.SmartCollect)
        }
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
          currentUser.isAllowedView('optimizer') &&
          (currentUser.isOptimizerEnabled || currentUser.isOptimizerOnboardingEnabled)
        }
        isNew={user?.isOptimizerOnboardingEnabled && !user?.isOptimizerEnabled}
      />

      <MainNavLink
        label="Customers"
        type="general"
        icon="i i-people text-warning"
        to="/customers"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('customers') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Customers)
        }
      />

      <MainNavLink
        label="Offers"
        icon="i i-offer text-success"
        type="general"
        to="/offers"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('offers') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Offers)
        }
      />

      <MainNavLink
        label="Checkout Rewards"
        icon="i i-rewards text-danger"
        to="/checkout-rewards"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('checkoutrewards') &&
          !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Checkoutrewards)
        }
      />

      <MainNavLink
        label="Working Capital Loans"
        icon="i i-rupee text-warm"
        to="/capital/non-fldg-loans/"
        additionalCondition={(currentUser) => currentUser.isNonFldgLoansEnabled}
        isNew={false}
      />

      <MainNavLink
        label="Loans"
        icon="i i-rupee text-warm"
        to="/capital/loans/apply"
        isNew={!isRecommendProduct}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedView('loans') && currentUser.isLoansEnabled
        }
      />

      <MainNavLink
        label="Corporate Cards"
        icon="i i-credit-card text-warm"
        to="/capital/corporate-cards/"
        isNew={!isRecommendProduct}
        additionalCondition={(currentUser) => currentUser.isCardsLOSEnabled}
      />

      <MainNavLink
        label="Wallet"
        icon="i i-wallet text-primary"
        type="wallet"
        to="/wallet"
        additionalCondition={(currentUser) =>
          currentUser.isIssuingDashboardEnabled ||
          currentUser.isIssuingBulkUploadEnabled ||
          user.isIssuingFundsTabEnabled
        }
      />

      <MainNavLink
        label="Payment Metrics"
        type="product"
        icon="i i-chart text-info"
        additionalCondition={getIsCheckoutPaymentMetricsEnabled}
        to={routes.paymentMetrics}
        isNew
      />

      <div class="divider" />

      <MainNavLink
        label="Reports"
        icon="i i-books text-danger"
        type="general"
        to="/reports"
        additionalCondition={(currentUser) =>
          (currentUser.isAllowedView('reports') || currentUser.isCareHealthOwner) &&
          currentUser.hideForNIASupportRole
        }
        isPending={isReportsPending}
      />
      <MainNavLink
        label="Account & Settings"
        icon="i i-settings text-warning"
        type="general"
        to="/account-settings"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedMultiple(
            'webhooks applications configuration api_keys profile credits add_funds team referrals',
          ) && currentUser.isAccountAndSettingsRevampEnabled
        }
      />
      <MainNavLink
        label="My Account"
        type="general"
        icon="i i-account text-primary"
        additionalCondition={(currentUser) =>
          currentUser.isAllowedMultiple('profile credits add_funds team referrals') &&
          !currentUser.isAccountAndSettingsRevampEnabled
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
        icon="i i-developers developers-sidebar-icon text-primary"
        to={user.isDeveloperConsoleEnabled ? routes.developersApis : routes.developersWebhooks}
        additionalCondition={(currentUser) =>
          !isMobileResolution() &&
          currentUser.isAllowedView('developers_console') &&
          (currentUser.isDeveloperConsoleEnabled ||
            currentUser.isDeveloperConsoleWebhooksTabEnabled)
        }
        isNew
      />
      <MainNavLink
        label="Settings"
        icon="i i-settings text-warning"
        type="general"
        to={routes.settings}
        additionalCondition={(currentUser) =>
          currentUser.isAllowedMultiple('webhooks applications configuration api_keys') &&
          !currentUser.isAccountAndSettingsRevampEnabled
        }
      />
    </>
  );
}

const mapStateToProps = (state) => ({
  payment: state.transactionAmount.amount,
});

export default connect(mapStateToProps)(MerchantNavLinks);
