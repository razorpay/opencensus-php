import React, { useEffect } from 'react';
import BBPSImage from 'assets/bbps.png';
import MagicKonnect from 'assets/magicKonnectLogo.png';
import { connect } from 'react-redux';

import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { analyticsTrack } from 'common/utils/analytics';
import * as LocalStorageService from 'common/utils/localStorage';
import { getCommonAnalyticsProperties, isMobileResolution } from 'common/utils/rzp-utils';
import {
  setRecommendedProduct,
  getRecommendedProductDetails,
} from 'merchant/components/Activation/ActivationUtils';
import ShowWhen from 'merchant/components/ShowWhen';
import MagicCheckoutNavLink from 'merchant/components/Sidebar/MagicCheckoutNavLink';
import { isBillMeMerchant } from 'merchant/utils/omniUtils';
import {
  canViewCashAdvanceProduct,
  canViewLOCEMIProduct,
  canViewLoans,
} from 'merchant/views/Capital/utils';
import { isPosExperimentEnabled } from 'merchant/views/POS/helpers';
import { checkReconSaasEnabled } from 'merchant/views/Reconciliations/utils';
import MainNavLink from 'merchant_common/components/MainNavLink';

import { trackViewedBankingNavBar } from './ga';
import {
  getIsBankingEnabled,
  getIsPayrollWidgetEnabled,
  getIsShowAffordabilityWidget,
  getIsCheckoutPaymentMetricsEnabled,
  isJKOfflineMerchant,
} from './helpers';

function MerchantNavLinks(props) {
  const { isConfigTagEnabled } = useI18Service();
  // prettier-ignore
  const { routes, isReportsPending, isChargeAtWillEnabled, isSettlementEnabled, user, payment, org } =
    props;
  const showMyAccountCutomBadge = !LocalStorageService.getItem('rtb_page_visited');
  const { recommendedProduct, hasRecommendedProduct } = getRecommendedProductDetails();
  const isRecommendProduct =
    hasRecommendedProduct && payment === 0 && user.isProductRecommendationEnabled;

  const { abExperiments } = useSplitzService();
  const showMagicKonnectTab = abExperiments?.magic_konnect?.variables?.result === 'on';
  const showMyDevicesTab = abExperiments?.my_devices?.variables?.result === 'on';
  const isOmniJkFlow = isJKOfflineMerchant(org, user);
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
        label="Assisted Financing"
        type="general"
        icon="i i-at-sign"
        to="/assisted-financing"
        additionalCondition={(currentUser) =>
          isExperimentEnabled(abExperiments?.assisted_financing) &&
          currentUser.isAllowedView('payment_links') &&
          !isConfigTagEnabled('payment_links.payment_link') &&
          currentUser.isOrgRZP
        }
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
        label="My Devices"
        icon="i i-pos text-success"
        to="/my-devices"
        isNew
        additionalCondition={() => isOmniJkFlow && showMyDevicesTab}
      />

      <ShowWhen additionalCondition={() => !isOmniJkFlow}>
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
          label="Reconciliation"
          icon="i i-check-circle-outline text-primary"
          type="general"
          to="/reconciliations/dashboard"
          additionalCondition={() => checkReconSaasEnabled({ abExperiments })}
        />
        <MainNavLink
          label="International Payments"
          icon="i i-external-link text-primary"
          type="general"
          to="/payment-methods/international-payments"
          additionalCondition={(currentUser) => currentUser.isShowInternationalPaymentBtnExpEnabled}
        />

        <MainNavLink
          label="Risk and Fraud"
          icon="i i-external-link text-primary"
          type="general"
          to="/risk-and-fraud"
          additionalCondition={(currentUser) => currentUser.isRiskAndFraudEnabled}
        />

        <div className="divider" />

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
            currentUser.isAllowedView('invoices') && !isConfigTagEnabled('invoices.invoice')
          }
        />

        <MainNavLink
          label="Konnect"
          type="product"
          image={MagicKonnect}
          to={routes.magicKonnect}
          isNew={true}
          additionalCondition={(currentUser) =>
            currentUser.isMagicKonnectEnabled && showMagicKonnectTab
          }
        />

        <MainNavLink
          label="BillMe"
          type="product"
          icon="i i-bill-me text-info"
          additionalCondition={(currentUser) => {
            // TODO: to add 'mode' condition check before Go-Live
            return isBillMeMerchant({ abExperiments });
          }}
          to={routes.billme}
          isNew={true}
        />

        <MainNavLink
          label="Payment Links"
          type="product"
          icon="i i-link text-primary"
          to={routes.paymentlinks}
          additionalCondition={(currentUser) =>
            currentUser.isAllowedView('payment_links') &&
            !isConfigTagEnabled('payment_links.payment_link')
          }
          customBadge={getProductBadge(['payment_link'])}
        />
        <MainNavLink
          label="POS"
          icon="i i-pos text-primary"
          type="product"
          to={routes.posSelfServe}
          isNew
          additionalCondition={(user, _) => {
            const isPosOnboardingEnabled = isPosExperimentEnabled({ user, abExperiments });
            return isPosOnboardingEnabled;
          }}
        />
        <MainNavLink
          label="Payment Pages"
          type="product"
          icon="i i-payment-pages text-warm temp-icon-style"
          to={routes.paymentpages}
          additionalCondition={(currentUser) =>
            currentUser.isAllowedView('payment_pages') &&
            !isConfigTagEnabled('payment_pages.payment_pages')
          }
          customBadge={getProductBadge(['payment_page'])}
        />
        <MainNavLink
          label="Razorpay.me Link"
          type="product"
          icon="i i-payment-handle text-success"
          to={routes.paymentHandle}
          additionalCondition={(currentUser) =>
            currentUser.isAllowedView('payment_handle') &&
            currentUser.isPaymentHandleSplitzEnabled &&
            !isConfigTagEnabled('payments.payment_handle')
          }
        />
        <MainNavLink
          label="Stores"
          icon="i i-store-product text-danger"
          type="product"
          to={routes.stores}
          additionalCondition={(currentUser) =>
            currentUser.isAllowedView('stores') &&
            isExperimentEnabled(abExperiments?.stores) &&
            !isConfigTagEnabled('stores.stores')
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
            user.isPaymentButtonEnabledByRazorX
              ? routes.paymentbuttons
              : routes.subscription_buttons
          }
          additionalCondition={(currentUser) =>
            currentUser.isAllowedMultiple('payment_buttons subscription_buttons') &&
            (currentUser.isPaymentButtonEnabledByRazorX ||
              currentUser.isSubscriptionButtonEnabled) &&
            !isConfigTagEnabled('payment_buttons.payment_buttons')
          }
          customBadge={getProductBadge(['payment_button', 'payment_gateway'])}
        />
        <MainNavLink
          label="Route"
          type="product"
          to={user.isPartnerRole ? routes.transfers : routes.marketplace}
          icon="i i-route text-success"
          additionalCondition={(currentUser) =>
            currentUser.isAllowedView('marketplace') && !isConfigTagEnabled('route.marketplace')
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
            !isConfigTagEnabled('subscriptions.subscription')
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
            currentUser.isAllowedView('qr_codes') && !isConfigTagEnabled('qr_code.qr_code')
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
            !isConfigTagEnabled('smart_collect.virtual_accounts')
          }
          customBadge={getProductBadge(['smart_collect'])}
        />

        <MagicCheckoutNavLink>
          {(onClick) => (
            <MainNavLink
              label={'Magic Checkout'}
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
          isNew={!user?.isOptimizerEnabled}
        />

        <MainNavLink
          label="Customers"
          type="general"
          icon="i i-people text-warning"
          to="/customers"
          additionalCondition={(currentUser) =>
            currentUser.isAllowedView('customers') && !isConfigTagEnabled('customers.customers')
          }
        />

        <MainNavLink
          label="Offers"
          icon="i i-offer text-success"
          type="general"
          to="/offers"
          additionalCondition={(currentUser) =>
            currentUser.isAllowedView('offers') && !isConfigTagEnabled('offers.offers')
          }
        />

        <MainNavLink
          label="Checkout Rewards"
          icon="i i-rewards text-danger"
          to="/checkout-rewards"
          additionalCondition={(currentUser) =>
            currentUser.isAllowedView('checkoutrewards') &&
            !isConfigTagEnabled('checkout_rewards.checkout_rewards')
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
          to="/capital/loans"
          isNew={!isRecommendProduct}
          additionalCondition={canViewLoans}
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
      </ShowWhen>

      <div className="divider" />

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
        label="InsightX"
        type="general"
        icon="i i-sparkles text-info"
        to="/insight-x"
        additionalCondition={() =>
          isExperimentEnabled(abExperiments?.insight_x_experiment) &&
          user.isOrgRZP &&
          user.isCountryIndia &&
          !isMobileResolution()
        }
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
      <ShowWhen additionalCondition={() => !isOmniJkFlow}>
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
      </ShowWhen>
    </>
  );
}

const mapStateToProps = (state) => ({
  payment: state.transactionAmount.amount,
  config: state.config,
  org: state.session.org,
});

export default connect(mapStateToProps)(MerchantNavLinks);
