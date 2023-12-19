import isEmpty from 'lodash/isEmpty';
import QueryString from 'query-string';

import { getXCAStatus } from 'common/ui/NotificationsDropdown/Neostone/common/utils';
import { getItem } from 'common/utils/localStorage';
import { filterBy, getURLQueryParams } from 'common/utils/rzp-utils';
import { getOnBoardingDataFromLocalState } from 'merchant/components/OnBoarding';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  antiOrgsFeatures,
  antiOrgsModules,
  roleEditPermissions,
  roleViewPermissions,
} from 'merchant/helpers/permissions';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { fetchFeaturesAjax } from 'merchant/reducers/config';
import { getMode, getOrg } from 'merchant/store';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';
import ajax from 'merchant/utils/ajax';
import { AffordabilityFeaturesFlag } from 'merchant/views/Affordability/AffordabilityWidget/Onboarding/data';
import { filterByArray as filterByAffordabilityFlags } from 'merchant/views/Affordability/AffordabilityWidget/Onboarding/helper';

export const ORG_CUSTOM_CODE_MAP = {
  RAZORPAY: 'rzp',
  AXIS_BANK: 'axis',
  ICICI_BANK: 'icic',
  HDFC_SMART_HUB: 'hdfc',
  HDFC_COLLECT_NOW: 'HDFC',
  KOTAK_MAHINDRA_BANK: 'KKBK',
  CURLEC: 'curlec',
};

const PRODUCT_KEY_MAPS = [
  'invoices',
  'payment_links',
  'payment_pages',
  'payment_buttons',
  'subscription_buttons',
  'marketplace',
  'subscriptions',
  'qr_codes',
  'stores',
  'virtual_accounts',
  'offers',
  'checkoutrewards',
];

const FEATURE_FLAG_MAPS = {
  invoices: 'invoices',
  payment_links: 'pl',
  payment_pages: 'pp',
  payment_buttons: 'pb',
  subscription_buttons: 'sb',
  marketplace: 'mp',
  subscriptions: 'subs',
  qr_codes: 'qrcodes',
  stores: 'stores',
  virtual_accounts: 'va',
  offers: 'offers',
  checkoutrewards: 'chk_reward',
};

// TODO: Rename fn. name
export function setFeatures(features) {
  const enabledFeatures = filterBy(features, 'value', true);
  // Since we are only adding enabled enabled features to rzp_user
  // we need to perform some function on affordability features therefore adding those seperatly
  const affordabilityFeatures = filterByAffordabilityFlags(
    features,
    'feature',
    AffordabilityFeaturesFlag,
  );

  // in other places rzp_user is getting used to update the session so updating with features
  window.rzp_user = {
    ...window.rzp_user,
    features: enabledFeatures,
    aff_features: affordabilityFeatures,
  };

  return enabledFeatures;
}

export default class User {
  merchants = {};

  constructor(props) {
    // Setting default merchant currency is INR if currency is not available.
    // For few roles merchant is not available, this will add a merchant for that particular role
    if (!props?.merchant) {
      props = {
        ...props,
        merchant: {
          currency: 'INR',
        },
      };
    }

    if (!props?.merchant?.currency) {
      props.merchant.currency = 'INR';
    }

    if (!props?.merchant?.country_code) {
      props.merchant.country_code = 'IN';
    }

    Object.assign(this, props);
    if (!this.tags) {
      this.tags = [];
    }

    if (!this.configTags) {
      this.configTags = {};
    }
  }

  isFeatureEnabled(feature) {
    return (this.enabledFeatures || []).indexOf(feature.toLowerCase()) !== -1;
  }

  fetch() {
    const promise = new Promise((resolve, reject) => {
      ajax({
        url: '/user',
        appendModeInURL: false,
      })
        .then((response) => {
          // Risky. fetchFeaturesAjax can make the request always in 'test'mode.
          // But hopefully, it will happen after cycle of App.js fetch User where it updatesSession with correct mode
          fetchFeaturesAjax(response.data.current)
            .catch((_) => _)
            .then((data) => {
              const newUser = new User(response.data);
              newUser.features = setFeatures(data.success ? data.data.features : []);
              response.data = newUser;
              resolve(response);
            })
            .catch((err) => {
              reject(err);
            });
        })
        .catch((err) => reject(err));
    });

    return promise;
  }

  get userRole() {
    if (this.current && Object.keys(this.merchants).length) {
      const currentMerchant = this.merchants[this.current];
      // check if its loaded from X dashboard
      // when X loads the dashboard for activation in an iframe, we pass merchant=x in queryParams
      const { merchant: product } = getURLQueryParams(window.location.search);

      if (product === 'x') {
        return currentMerchant.banking_role;
      }
      return currentMerchant.role;
    }
    return null;
  }

  get isAuthenticated() {
    return !!this.user;
  }

  get isVerified() {
    return this.user.confirmed;
  }

  get orgCustomCode() {
    return getOrg()?.custom_code;
  }

  get isWhiteLabelledOrg() {
    const custom_code = this.orgCustomCode?.toLowerCase();
    return (
      custom_code !== ORG_CUSTOM_CODE_MAP.RAZORPAY && custom_code !== ORG_CUSTOM_CODE_MAP.CURLEC
    );
  }

  get isOrgRZP() {
    const custom_code = this.orgCustomCode;
    return custom_code?.toLowerCase() === ORG_CUSTOM_CODE_MAP.RAZORPAY;
  }

  get isOrgAxis() {
    const custom_code = this.orgCustomCode;
    return custom_code?.toLowerCase() === ORG_CUSTOM_CODE_MAP.AXIS_BANK;
  }

  get isOrgKotak() {
    const custom_code = this.orgCustomCode;
    return custom_code?.toLowerCase() === ORG_CUSTOM_CODE_MAP.KOTAK_MAHINDRA_BANK;
  }

  /* Curlec Org Identifier */
  get isOrgCurlec() {
    const custom_code = this.orgCustomCode;
    return custom_code?.toLowerCase() === ORG_CUSTOM_CODE_MAP.CURLEC;
  }

  /* Check case-insensitive tag check */
  findTag(tag) {
    return this.tags.some((t) => t.toLowerCase() === tag.toLowerCase());
  }

  isProductHiddenForWhiteLabelledOrg(moduleName) {
    // show all products to Razorpay ORG
    if (!this.isWhiteLabelledOrg) return false;

    // show all products apart from the PRODUCT_KEY_MAPS list
    if (!PRODUCT_KEY_MAPS.includes(moduleName)) return false;

    // show product/s to merchant who has specific product tag
    if (this.findTag(`white_labelled_${moduleName}`)) return false;

    // For AXIS ORG, by default all the product apps has to be hidden
    if (this.isOrgAxis) return true;

    // since there is char constraint for feature flag we have shortforms for each product. Hence using FEATURE_FLAG_MAPS
    const orgFeatureFlag = FEATURE_FLAG_MAPS[moduleName] ?? moduleName;

    // hide product/s to ORG if there is specific product tag
    return isOrgFeatureExist(`white_labelled_${orgFeatureFlag}`);
  }

  isOrgAllowedFunctionality(featureName) {
    const restrictedFeaturesForOrg = antiOrgsFeatures[getOrg().custom_code];

    if (restrictedFeaturesForOrg) {
      const isFeatureAllowed = restrictedFeaturesForOrg.indexOf(featureName) === -1;

      return isFeatureAllowed;
    }

    return true; // By default it's allowed if not restricted
  }

  isAllowedEdit(moduleName) {
    let isEditAllowed = _isAllowed(this.userRole, moduleName, roleEditPermissions);

    if (this.isEditRestrictedByRazorX(moduleName)) {
      isEditAllowed = false;
    }

    if (this.isProductHiddenForWhiteLabelledOrg(moduleName)) {
      isEditAllowed = false;
    }

    return isEditAllowed;
  }

  isAllowedView(moduleName) {
    let isViewAllowed = _isAllowed(this.userRole, moduleName, roleViewPermissions);

    if (this.isViewRestrictedByRazorX(moduleName)) {
      isViewAllowed = false;
    }

    if (this.isProductHiddenForWhiteLabelledOrg(moduleName)) {
      isViewAllowed = false;
    }

    return isViewAllowed;
  }

  isOrgFeatureEnabled(feature) {
    const features = getOrg()?.features;
    return features?.indexOf(feature) > -1;
  }

  /*
   * isAllowedMultiple is for grouped tabs, example: Settings in side bar.
   * If any route is present in moduleNames, it will be treated for view only mode and will make parent group(hood) visible.
   * */
  isAllowedMultiple(moduleNames) {
    let isHoodAllowed = false;
    moduleNames = moduleNames.split(' ');

    for (let key = 0; key < moduleNames.length; key++) {
      const m = moduleNames[key];
      isHoodAllowed = this.isAllowedView(m);

      if (isHoodAllowed) {
        break;
      }
    }

    return isHoodAllowed;
  }

  get isContactMobileChangeAllowed() {
    // in case of restricted merchants
    // only owner and admin are alllowed to changed self contact_mobile
    return !this.isMerchantRestricted || ['owner', 'admin'].indexOf(this.role) > -1;
  }

  get isActivated() {
    return !!this.activated;
  }

  get instantActivation() {
    return {
      activation_flow: this.activation_flow,
      business_type: this.business_type,
      activated: this.activated,
      isUnregisteredBusiness: this.isUnregisteredBusiness,
      isInstantActivationEnabled: this.isInstantActivationEnabled,
      activation_form_milestone: this.activation_form_milestone,

      get isWhitelistFlow() {
        return this.activation_flow === 'whitelist';
      },

      get isBlacklistFlow() {
        return this.activation_flow === 'blacklist';
      },

      get isGraylistFlow() {
        return this.activation_flow === 'greylist';
      },

      get isL1Submitted() {
        const query = QueryString.parse(window.location.search);
        const isSourceRX = !!(query && query.merchant && query.merchant === 'x');

        // Assume L1 is submitted if activation form is inside RX and V2 onboarding experiment is enabled
        if (isSourceRX) {
          return true;
        }

        if (this.isInstantActivationEnabled) {
          if (this.activation_form_milestone === 'L1') {
            return true;
          }
        } else {
          if (!isSourceRX) {
            return true;
          }

          if (!!this.activated) {
            return true;
          }
          if (!this.isUnregisteredBusiness) {
            return !!this.activation_flow;
          }
        }

        return false;
      },
    };
  }

  get internationalActivationFlow() {
    return {
      international_activation_flow: this.international_activation_flow,

      get isWhitelistFlow() {
        return this.international_activation_flow === 'whitelist';
      },

      get isBlacklistFlow() {
        return this.international_activation_flow === 'blacklist';
      },

      get isGraylistFlow() {
        return this.international_activation_flow === 'greylist';
      },
    };
  }

  get isAccepted() {
    return this.activation_status === 'activated';
  }

  // getter to check if payPal is enabled for a certain merchant or not
  get isPayPalEnabled() {
    return this.methods?.paypal;
  }

  get needsClarification() {
    return this.activation_status === 'needs_clarification';
  }

  get isActivatedMCCPending() {
    return this.activation_status === 'activated_mcc_pending';
  }

  get isPaymentsEnabled() {
    return (
      ['instantly_activated', 'activated_mcc_pending', 'activated'].indexOf(
        this.activation_status,
      ) > -1
    );
  }
  // KYC form submitted
  get isSubmitted() {
    return !!parseInt(this.submitted, 10);
  }

  get isUnderReview() {
    return (
      this.activation_status === 'under_review' ||
      this.activation_status === 'kyc_qualified_unactivated'
    );
  }

  get isRejected() {
    return this.activation_status === 'rejected';
  }

  get isMarketplaceEnabled() {
    return this.isFeatureEnabled('marketplace');
  }

  get isVirtualAccountsEnabled() {
    return this.isFeatureEnabled('virtual_accounts');
  }

  get isSubscriptionsEnabled() {
    return this.isFeatureEnabled('subscriptions');
  }

  get isCovidFeatureEnabled() {
    return this.isFeatureEnabled('covid');
  }

  get isRepaymentBannerEnabled() {
    return this.getExpStatus('free_credit_recovery_banner');
  }

  get isMagicCheckoutEnabled() {
    return this.isFeatureEnabled('one_cc_merchant_dashboard') && this.isOrgRZP;
  }

  get isMerchantExpiryPPEnabled() {
    return this.isFeatureEnabled('enable_merchant_expiry_pp');
  }

  get isCustomerAmountEnabled() {
    return this.isFeatureEnabled('enable_customer_amount');
  }

  get isCreateOwnTemplateEnabled() {
    return this.isFeatureEnabled('enbl_create_own_tmpl');
  }

  get isNoExpiryMandatoryPP() {
    const orgFeatureEnabled = this.isOrgFeatureEnabled('hide_no_expiry_for_pp');
    const merchantFeatureEnabled = this.isMerchantExpiryPPEnabled;
    return orgFeatureEnabled ? merchantFeatureEnabled : true;
  }

  get showPayerNamePP() {
    return this.isOrgFeatureEnabled('enable_payer_name_for_pp');
  }

  get showCustomTemplatePP() {
    const orgFeatureEnabled = this.isOrgFeatureEnabled('hide_create_new_tmpl_pp');
    const merchantFeatureEnabled = this.isCreateOwnTemplateEnabled;
    return orgFeatureEnabled ? merchantFeatureEnabled : true;
  }

  get hideDynamicPriceFieldPP() {
    const orgFeatureEnabled = this.isOrgFeatureEnabled('hide_dynamic_price_pp');
    const merchantFeatureEnabled = this.isCustomerAmountEnabled;
    // hide Dynamic Price option if org feature `hide_dynamic_price_pp` is enabled & merchant feature `enable_customer_amount` is disabled.
    return orgFeatureEnabled ? !merchantFeatureEnabled : false;
  }

  get isBulkAddressUploadEnabled() {
    return this.getExpStatus('magic_bulk_address_live');
  }

  get isMagicCheckoutLive() {
    return this.isFeatureEnabled('one_click_checkout');
  }

  get isShipRocketEnabled() {
    // Disable this feature for curlec
    if (this.isOrgCurlec) return false;

    return true;
  }

  get isMagicSettingsEnabled() {
    return (
      [
        rolesList.OWNER,
        rolesList.ADMIN,
        rolesList.MANAGER,
        rolesList.OPERATIONS,
        rolesList.FINANCE,
      ].indexOf(this.userRole) > -1
    );
  }

  get isMagicRTOAnalyticsV3Enabled() {
    return getSplitzExperimentVariant('magic_rto_analytics_v3')?.variables?.result === 'on';
  }

  get isMagicPrepayCODEnabled() {
    const accessRoles = [
      rolesList.OWNER,
      rolesList.ADMIN,
      rolesList.MANAGER,
      rolesList.OPERATIONS,
      rolesList.FINANCE,
    ];

    return (
      getSplitzExperimentVariant('magic_prepay_cod')?.variables?.result === 'on' &&
      accessRoles.indexOf(this.userRole) > -1
    );
  }

  get isMagicOrderAnalyticsEnabled() {
    return getSplitzExperimentVariant('magic_order_analytics')?.variables?.result === 'on';
  }

  get isMagicOrderAnalyticsCREnabled() {
    return getSplitzExperimentVariant('magic_order_analytics_cr')?.variables?.result === 'on';
  }

  // These roles are added here to restrcit that the coupon editing can be done by the below mentioned roles and not all
  get isMagicShopifyOrderEditEnabled() {
    const accessRoles = [
      rolesList.OWNER,
      rolesList.ADMIN,
      rolesList.MANAGER,
      rolesList.OPERATIONS,
      rolesList.FINANCE,
    ];

    return (
      getSplitzExperimentVariant('magic_shopify_order_edit')?.variables?.result === 'on' &&
      accessRoles.indexOf(this.userRole) > -1
    );
  }

  get isMagicCODOrderAutomationEnabled() {
    const accessRoles = [
      rolesList.OWNER,
      rolesList.ADMIN,
      rolesList.MANAGER,
      rolesList.OPERATIONS,
      rolesList.FINANCE,
    ];
    return (
      this.getExpStatus('magic_cod_orders_automation_live') &&
      accessRoles.indexOf(this.userRole) > -1
    );
  }

  get isCardMultipleFrequencyEnabled() {
    return getSplitzExperimentVariant('recurring_card_multi_frequency')?.variables?.result === 'on';
  }

  get isDebitPatternEnabled() {
    return getSplitzExperimentVariant('recurring_debit_pattern')?.variables?.result === 'on';
  }

  get isMagicCODEngineEnabled() {
    return getSplitzExperimentVariant('magic_cod_engine')?.variables?.result === 'on';
  }

  get isShopifyMagicEnabled() {
    return this.getExpStatus('1cc_shopify_magic_enable');
  }

  get isMagicWoocEnabled() {
    return this.getExpStatus('1cc_wooc_magic_enable');
  }

  get isPaymentPagesEnabled() {
    const { isEnabled } = getOnBoardingDataFromLocalState('payment_pages');

    return !!isEnabled;
  }

  get isPaymentLinksEnabled() {
    const { isEnabled } = getOnBoardingDataFromLocalState('payment_links');

    return !!isEnabled;
  }

  get isInvoicesEnabled() {
    const { isEnabled } = getOnBoardingDataFromLocalState(RZPFeatures.INVOICE);

    return isEnabled;
  }

  get isOffersEnabled() {
    const { isEnabled } = getOnBoardingDataFromLocalState(RZPFeatures.OFFERS);

    return isEnabled;
  }

  get isAffordabilityWidgetEnabled() {
    const { isEnabled } = getOnBoardingDataFromLocalState(RZPFeatures.AFFORDABILITY_WIDGET);

    return isEnabled;
  }

  get isPaymentHandleEnabled() {
    const { isEnabled } = getOnBoardingDataFromLocalState(RZPFeatures.PH);

    return isEnabled;
  }

  get isRewardsEnabled() {
    const { isEnabled } = getOnBoardingDataFromLocalState(RZPFeatures.REWARDS);

    return isEnabled;
  }

  get isPaymentButtonsEnabled() {
    const { isEnabled } = getOnBoardingDataFromLocalState(RZPFeatures.PB);

    return isEnabled;
  }

  get isStoresEnabled() {
    return this.getExpStatus('stores');
  }

  get isQRCodeProductEnabled() {
    return this.isFeatureEnabled('qr_codes');
  }

  get isQRCodeDedicatedTerminalEnabled() {
    return this.getExpStatus('dedicated_terminal_qr_code');
  }

  get isOwner() {
    return this.userRole === rolesList.OWNER;
  }

  get isAdminOrOwner() {
    return this.isOwner || this.userRole === rolesList.ADMIN;
  }

  get isSupportRole() {
    return this.userRole === rolesList.SUPPORT;
  }

  get isRazorxRXCASelfServeFlowEnabled() {
    return this.getExpStatus('rx_ca_self_serve_flow');
  }

  get isRewardsPageEnabled() {
    return this.isFeatureEnabled('reward_merchant_dashboard');
  }

  get isPaymentPageEmailOptional() {
    return this.isFeatureEnabled('email_optional');
  }

  get isPaymentPageContactOptional() {
    return this.isFeatureEnabled('contact_optional');
  }

  get isPaymentPageCustomDomainEnabled() {
    return this.isFeatureEnabled('pp_custom_domain');
  }

  get isPaymentPageFileUploadEnabled() {
    return this.isFeatureEnabled('file_upload_pp');
  }

  get isPaymentPageMagicEnabled() {
    return this.getExpStatus('pp_magic_setting');
  }

  get isPaymentPageOnboardingRedirectionEnabled() {
    return this.getExpStatus('pp_onboarding_redirection_exp');
  }

  get isPaymentPageCustomDomainShowRemoveEnabled() {
    return this.getExpStatus('pp_custom_domain_show_remove');
  }

  get isPaymentPageStorefrontEnabled() {
    return this.isOrgRZP && getSplitzExperimentVariant('pp_ecommerce')?.variables?.result === 'on';
  }

  get isInvoiceCreateFlowUXOptimizationEnabled() {
    return this.getExpStatus('inv_create_flow_ux');
  }

  get isRTBProgramEnabled() {
    return this.isFeatureEnabled('rzp_trusted_badge');
  }

  get isDisputePresentmentEnabled() {
    return !this.isFeatureEnabled('exclude_disp_presentment');
  }

  get currentMerchant() {
    return this.merchants[this.current];
  }

  get isGSTDisabled() {
    return this.findTag('Gst_Invoice_Disabled');
  }

  get isRefundsDisabled() {
    return this.isFeatureEnabled('disable_refunds');
  }

  get isCashAdvanceDisabled() {
    return this.isFeatureEnabled('disable_loc_post_dpd'); // due to post dpd in capital products
  }

  get isLoansDisabled() {
    return this.isFeatureEnabled('disable_loans_post_dpd'); // due to post dpd in capital products
  }

  get isProjectNitroEnabled() {
    return (
      this.getExpStatus('project_nitro_1') ||
      this.getExpStatus('project_nitro_feb_2021') ||
      this.getExpStatus('project_nitro_feb_2021_1') ||
      this.getExpStatus('nitro_hyderabad_v2') ||
      this.getExpStatus('nitro_hyderabad_v3') ||
      this.getExpStatus('nitro_midmarket_mumbai_v1')
    );
  }

  get isDeveloperConsoleEnabled() {
    return getSplitzExperimentVariant('developer_console')?.variables?.result === 'on';
  }

  get isDeveloperConsoleWebhooksTabEnabled() {
    return getSplitzExperimentVariant('developer_console_webhooks_tab')?.variables?.result === 'on';
  }

  get isCatalystBannerFL() {
    return getSplitzExperimentVariant('catalyst_banner_fl_experiment')?.variables?.result === 'on';
  }
  get isCatalystBannerEF() {
    return getSplitzExperimentVariant('catalyst_banner_ef_experiment')?.variables?.result === 'on';
  }
  get isCatalystBannerG() {
    return getSplitzExperimentVariant('catalyst_banner_g_experiment')?.variables?.result === 'on';
  }

  get isShowRazorpayXWidgetEnabled() {
    return getSplitzExperimentVariant('show_razorpayx_widget_exp')?.variables?.result === 'on';
  }

  get isProjectMoonshineEnabled() {
    return getSplitzExperimentVariant('project_moonshine')?.variables?.result === 'on';
  }

  get isWhatsNewLazyEnabled() {
    return getSplitzExperimentVariant('whats_new_lazy_experiment')?.variables?.result === 'on';
  }

  get isCSSEducationEnabled() {
    return getSplitzExperimentVariant('cross_sell_edu_exp')?.variables?.result === 'on';
  }

  get isCSSOtherBusinessesEnabled() {
    return getSplitzExperimentVariant('cross_sell_other_exp')?.variables?.result === 'on';
  }
  get isLoanCustomAmountRepaymentEnabled() {
    return this.getExpStatus('loans_allow_custom_amount_repayment');
  }

  isNeostoneFlowEnabled = (showState = '') => {
    return (
      getXCAStatus(this).showState === showState &&
      this.isOwner &&
      !this.isRazorxRXCASelfServeFlowEnabled
    );
  };

  isICICILinkedCAFlowEnabled = (showState = '') => {
    return getXCAStatus(this).showState === showState;
  };

  get isPartOfZapierIntegrationExperiment() {
    return getSplitzExperimentVariant('zapier_integration')?.variables?.result === 'on';
  }

  /* Method to check if 2FA is enabled for Mobile Signup Users */
  get is2FAMobileSignupEnabled() {
    return getSplitzExperimentVariant('twoFA_mobile_signup_exp')?.variables?.result === 'on';
  }

  /* Method to get the Failure Analysis Text Variant */
  get faTextVariant() {
    return getSplitzExperimentVariant('failure_analysis_text_exp')?.variables?.result;
  }

  /* Method to get the Failure Analysis Payments Count Experiment */
  get showFAPaymantCount() {
    return getSplitzExperimentVariant('failure_analysis_payment_count_exp')?.variables?.result;
  }

  /* Method to get the weather to show Failure Analysis or not */
  get isFAEnabled() {
    return getSplitzExperimentVariant('failure_analysis_rollout_exp')?.variables?.result === 'on';
  }

  /* Method to get the maximum monthly trasaction volume for which we want to enable the FA */
  get getMaxFAMtv() {
    return parseInt(getSplitzExperimentVariant('failure_analysis_mtv_exp')?.variables?.result, 10);
  }

  get isChargeAtWillEnabled() {
    return this.findTag('Charge_at_will');
  }

  get isTPVEnabled() {
    return this.findTag('Tpv');
  }

  get isEsignEnabled() {
    return this.findTag('Esign');
  }

  get isMerchantRestricted() {
    return this.restricted;
  }

  get isAgentRole() {
    return this.findTag('enable_agent_role');
  }

  get isWorkboxEnable() {
    return this.getExpStatus('enable_workbox');
  }

  get isMobileSignupCareActive() {
    return this.getExpStatus('mobile_signup_care_changes_active');
  }
  get isSmartDashboardActive() {
    return this.getExpStatus('instrument_requests_smart_dashboard');
  }

  get isRazorxAnnouncementEnabled() {
    return this.findTag('announcement_razorpayx');
  }

  get isInvoiceReceiptMandatory() {
    return this.isFeatureEnabled('invoice_receipt_mandatory');
  }

  get isRBLRoleEnabled() {
    return this.findTag('enable_RBL_role');
  }

  get isRegistrationLinkRoleEnabled() {
    return this.findTag('Enable_auth_link_role');
  }

  get isTestModeBlocked() {
    return this.isFeatureEnabled('prevent_test_mode');
  }

  get isIssuingDashboardEnabled() {
    return this.isFeatureEnabled('razorpay_wallet');
  }

  get isIssuingBulkUploadEnabled() {
    const allowedRoles = [rolesList.MANAGER, rolesList.OWNER, rolesList.FINANCE];
    return this.isFeatureEnabled('razorpay_wallet') && allowedRoles.indexOf(this.userRole) > -1;
  }

  get isRegistrationLinkTokenAndPaymentsEnabled() {
    return (
      this.userRole !== rolesList.REGISTRATION_LINK_AGENT &&
      this.userRole !== rolesList.REGISTRATION_LINK_SUPERVISOR
    );
  }

  get isRegistrationLinkBatchUploadEnabled() {
    return this.userRole !== rolesList.REGISTRATION_LINK_AGENT;
  }

  get isRegistrationLinkBasedRole() {
    return (
      this.userRole === rolesList.REGISTRATION_LINK_AGENT ||
      this.userRole === rolesList.REGISTRATION_LINK_SUPERVISOR
    );
  }

  get isRegistrationLinkSupervisorRole() {
    return this.userRole === rolesList.REGISTRATION_LINK_SUPERVISOR;
  }

  get enabledFeatures() {
    const pluckKey = 'feature';

    return (this.features || []).map((object) => {
      return object[pluckKey];
    });
  }

  get showInstantActivation() {
    return this.isOrgRZP;
  }

  get isMinimumFirstPaymentEnabled() {
    return this.isFeatureEnabled('pl_first_min_amount');
  }

  get isPBDirectPluginLinks() {
    return this.getExpStatus('pb_direct_plugin_links');
  }

  /*
   * Detects whether user is partner or not.
   * If check has to be made for specific type of partners,
   * then send the types for which check has to be done in arguments
   */

  isPartner(...args) {
    const partnerTypes = [...args];
    return !!partnerTypes.length
      ? partnerTypes.indexOf(this.partner_type) > -1
      : !!this.partner_type;
  }

  // checks if the merchant or user has shown intent to become partner
  isPartnerIntent() {
    return this.partner_type === null && this.partner_intent;
  }

  isSignUpPartnerIntent() {
    return (
      this.partner_type === null && this.partner_intent && this.merchant_partner_intent === false
    );
  }

  isGoogleLogin() {
    return this.user.oauth_login;
  }

  get isHavingPartnerConfigs() {
    const currentMerchant = (this.merchants || {})[this.current];
    return !!currentMerchant.partner_type && (currentMerchant.partner || {}).has_commission_configs;
  }

  get isHavingSubventionConfigs() {
    const currentMerchant = (this.merchants || {})[this.current];
    return !!currentMerchant.partner_type && (currentMerchant.partner || {}).has_subvention_configs;
  }

  get isTwoFactorVerified() {
    return this.user.two_fa_verified;
  }

  get isTwoFactorSetupDone() {
    return this.user.contact_mobile && this.user.contact_mobile_verified;
  }

  getExpStatus(name) {
    const user = window.rzp_user;
    return ((this.experiments || user?.experiments || {})[name] || {}).result === 'on';
  }

  // For all Optimizer merchants success rate dashboard should be visible
  get isSuccessRateEnabled() {
    return this.findTag('success_rate') || this.isOptimizerEnabled;
  }

  /** Optimizer related getters - start */
  get isOptimizerRZPVASEnabled() {
    return this.isFeatureEnabled('optimizer_razorpay_vas');
  }

  get isOptimizerEnabled() {
    return this.isFeatureEnabled('raas') && !this.isOptimizerRZPVASEnabled;
  }

  get isSodexoInstrumentEnabled() {
    return getSplitzExperimentVariant('sodexo_instrument')?.variables?.result === 'on';
  }

  get isPaytmAutoDebitEnabled() {
    return this.isFeatureEnabled('wallet_paytm_auto_debit');
  }

  get isOptimizerOnboardingEnabled() {
    return this.getExpStatus('optimizer_onboarding');
  }

  get isHidePIDetails() {
    return this.getExpStatus('hide_PI_details');
  }

  get isCareHealthOwner() {
    return this.userRole === rolesList.OWNER && this.current === 'Icmg54HpdK0fdT';
  }

  get hideForNIASupportRole() {
    return !(this.isSupportRole && this.current === 'If9Z0dDl6Vht65');
  }

  get isSingleReconEnabled() {
    return this.isFeatureEnabled('enable_single_recon');
  }
  /** Optimizer related getters - end */

  get isOndemandSettlementEnabled() {
    return this.isFeatureEnabled('ES_ON_DEMAND');
  }

  get isOndemandRouteSettlementsEnabled() {
    return this.isFeatureEnabled('ondemand_route');
  }

  get isSupportDetails2FAEnabled() {
    return true;
  }

  get isComdelApiEnabled() {
    return this.getExpStatus('comdel_hdfc_test');
  }

  get isFdTicketsEnabled() {
    return true;
  }

  get isTicketCreationFlowRevamp() {
    return true;
  }

  get isAnnouncementIconEnabled() {
    return this.getExpStatus('AnnouncementIconJan2021');
  }

  get isWebsiteComplianceFlowEnabled() {
    return (
      this.isOrgRZP &&
      getSplitzExperimentVariant('website_compliance_flow_exp')?.variables?.result === 'on'
    );
  }

  get isWebsiteComplianceModalNonDismissible() {
    // if `isWebsitePolicyFailed` is failed make the non dismissible modal
    const isWebsitePolicyFailed = this.website_policy_verification_status === 'failed';

    return (
      this.isOrgRZP &&
      getSplitzExperimentVariant('website_compliance_modal_exp')?.variables?.result === 'on' &&
      isWebsitePolicyFailed
    );
  }

  get isWhatsNewSectionEnabled() {
    return this.getExpStatus('whats-new-dec-2020');
  }

  get isUxRevampPhase2Enabled() {
    return this.getExpStatus('settlement_ux_revamp_p2');
  }

  get iscaptureSettingsRevampEnabled() {
    return this.getExpStatus('capture_settings_revamp');
  }

  get isFeeCreditSelfServeEnabled() {
    return this.getExpStatus('fee_credit_self_serve');
  }

  get isRefundCreditSelfServeEnabled() {
    return this.getExpStatus('refund_credit_self_serve');
  }

  get isRefundSourceFallbackEnabled() {
    return this.getExpStatus('refund_source_fallback_enabled');
  }

  get isSettlementDashboardVisibilityEnabled() {
    return (
      getSplitzExperimentVariant('settlement_dashboard_visibility')?.variables?.result === 'on'
    );
  }

  get isReserveBalanceSelfServeEnabled() {
    return this.getExpStatus('reserve_bal_self_serve');
  }

  get isFtxEnabled() {
    return this.getExpStatus('ftx_2021');
  }

  get isEmailSelfServeEnabled() {
    return true;
  }

  get isCovidReliefFlowEnabled() {
    return this.getExpStatus('covid_19_donation_show');
  }

  get isFeeBearerSelfServeOn() {
    return true;
  }

  get isAutomaticSettlementEnabled() {
    return this.isFeatureEnabled('ES_AUTOMATIC');
  }

  get isAutomaticSettlementRestricted() {
    return this.isFeatureEnabled('es_automatic_restricted');
  }

  get isOndemandSettlementsRestricted() {
    return this.isFeatureEnabled('es_on_demand_restricted');
  }

  get isWebsiteSelfServeOn() {
    return true;
  }

  get isTransactionLimitUpdateSelfServeOn() {
    return true;
  }

  get isAdditionalDomainWhitelistSelfServeOn() {
    return this.getExpStatus('additional_domain_whitelist_self_serve');
  }

  get isCreditPullEnabled() {
    return this.isFeatureEnabled('show_credit_score');
  }

  get isDiwaliPromoEnabled() {
    return this.findTag('diwali_promotional_plan');
  }

  // Allowed roles can be revoked refund access selectively with this tag
  get isRefundAllowed() {
    return this.isAllowedEdit('refunds') && !this.isRefundsDisabled;
  }

  //instant settlements
  get isISBannerEnabled() {
    return this.getExpStatus('is_banner');
  }

  get isCapitalBannerEnabled() {
    return this.getExpStatus('capital_banner');
  }

  get isExpireByRequired() {
    return this.isFeatureEnabled('invoice_expire_by_reqd');
  }

  get isInstantActivationEnabled() {
    return this.getExpStatus('instant-activations-functionality');
  }

  get isInstantActivationVideoEnabled() {
    return (
      getSplitzExperimentVariant('instant_activations_video_enabled')?.variables?.result === 'on'
    );
  }

  get isFeEasyDashboardNCEnabled() {
    return getSplitzExperimentVariant('enable_easy_dashboard_nc')?.variables?.result === 'on';
  }

  get isPartnershipsInviteFlowEnabled() {
    return this.partner_type === 'reseller' && this.isOrgRZP;
  }

  get isAddReplyMigrationActive() {
    return getSplitzExperimentVariant('add_reply_migration')?.variables?.result === 'on';
  }
  get isInttCurrenciesEnabled() {
    return (
      this.currentMerchant.product_international === '1111000000' ||
      !!(this.methods || {}).paypal ||
      !!this.international
    );
  }

  get getPaymentLinkCustomizedFormFields() {
    return window.pl_customized_form_fields;
  }

  get isRegAutoKYCEnabled() {
    return true;
  }

  get getCurrencyList() {
    return window.currencyList;
  }

  get plDefaultExpiryTime() {
    return window.pl_expiry_in_hrs;
  }

  get isEnhancedEPOSEnabled() {
    return this.getExpStatus('sellerapp_plus');
  }

  get isMobileHotjarSurveyEnabled() {
    return this.getExpStatus('mobile_hotjar_survey');
  }

  get isNPSSurveyBannerEnabled() {
    return this.getExpStatus('nps_survey_banner');
  }

  get isShowCommissionBalanceEnabled() {
    return this.getExpStatus('show_commission_balance');
  }

  get isFirstAmountHidden() {
    return this.getExpStatus('hide_registration_link_first_amount');
  }

  get paymentLinkCreationFormExtraFields() {
    return window.pl_extra_fields;
  }

  get isAllowedTeamManagement() {
    return this.isMerchantRestricted ? this.isAllowedView('team') : this.isAllowedEdit('team');
  }

  get isCustomNotesDropdownEnabled() {
    return window.custom_notes && this.getExpStatus('custom_notes');
  }

  get isPaymentLinkCustomerNameFieldEnabled() {
    return window.is_pl_customer_name_field_enabled;
  }

  get isPaymentLinkBatchEnabledForSellerAppRole() {
    return this.getExpStatus('sellerapp_PL_batch_upload');
  }

  get isPaymentLinkDescriptionRequired() {
    const userBusinessType = Number(this.business_type);
    const isRazorXExperimentEnabled = this.getExpStatus('pl_description_required');

    // required for proprietorship and unregistered
    const REQUIRED_DESCRIPTION_BUSINESS_TYPES = [1, 11];
    return (
      REQUIRED_DESCRIPTION_BUSINESS_TYPES.indexOf(userBusinessType) !== -1 &&
      isRazorXExperimentEnabled
    );
  }

  get isBatchCancelEnabled() {
    return this.getExpStatus('batch_cancel');
  }

  get isVACreationBankAccountDisabled() {
    return this.getExpStatus('disable_va_creation_bank_account');
  }

  get isCompanyNameHiddenRazorX() {
    return this.getExpStatus('hide_company_name');
  }

  get isVirtualVPAPrefixEnabled() {
    // Doing 100% rollout since there is issues in razor-x
    // TODO: Completely remove views/SmartCollect/VirtualAccounts/Create/CreateV1
    return true;
  }

  get missedOrderPLBanner() {
    return this.getExpStatus('missed_order_pl_banner');
  }

  get isPaymentsExtraRefundDetailsEnabled() {
    return this.getExpStatus('payments_extra_refund_details');
  }

  get isEmandateNonzeroAmountEnabled() {
    return this.getExpStatus('emandate_nonzero_amount');
  }

  get isCardRecurringPaymentsBlocked() {
    return this.getExpStatus('card_recurring_payments_blocked');
  }

  get isSubscriptionOffersEnabled() {
    return this.getExpStatus('offer_on_subscription') && !this.isChargeAtWillEnabled;
  }

  // below getter is dead code, please remove
  get isSubscriptionOffersReportsEnabled() {
    return this.isSubscriptionOffersEnabled && this.getExpStatus('subscription_offers_reports');
  }

  get isCAWRecurringChargeAxisEnabled() {
    return (
      this.isFeatureEnabled('caw_recurring_charge_axis') ||
      this.getExpStatus('caw_recurring_charge_axis')
    );
  }

  // 100% rollout done. Exp to be removed shortly
  get isPaymentButtonEnabledByRazorX() {
    return true;
  }

  get isCriticalRouteExperimentEnabled() {
    return this.getExpStatus('validate_user_2fa_status');
  }

  get isBatchSchedulingOptionsExperimentEnabled() {
    return this.getExpStatus('batch_scheduling_options');
  }

  get isEmandateOnSubscriptionEnabled() {
    return this.getExpStatus('emandate_subscription');
  }

  get isDirectTransferEnabled() {
    return this.isFeatureEnabled('direct_transfer');
  }

  get isSubscriptionButtonEnabled() {
    return this.isSubscriptionsEnabled;
  }

  get isSubscriptionExpiryEnabled() {
    return this.getExpStatus('subscription_expiry');
  }

  get isCAWTPVEnabled() {
    return this.getExpStatus('caw_tpv');
  }

  get isBharatQREnabled() {
    return this.isFeatureEnabled('bharat_qr');
  }

  isQRCodeComingSoonEnabled(mode) {
    if (this.isQRCodeProductEnabled) {
      return true;
    }

    const status = !!getItem(`QR-codes-${mode}-${this.current}`);

    return status;
  }

  get isPaymentLinkCreationV2Enabled() {
    return this.isPaymentlinksV2Enabled;
  }

  get isRefundPendingStatusEnabled() {
    return this.isFeatureEnabled('refund_pending_status');
  }

  get isSellerAppRole() {
    const userRole = this.userRole;
    return [rolesList.SELLERAPP, rolesList.SELLERAPP_PLUS].indexOf(userRole) > -1;
  }

  get isRouteCodeSupportEnabled() {
    return this.isFeatureEnabled('route_code_support');
  }

  get isRouteLinkedAccountCreationDisabled() {
    return this.merchant.category === '6211' && this.merchant.category2 === 'mutual_funds';
  }

  get isSubscriptionPauseAndResumeEnabled() {
    return this.getExpStatus('pause_resume_enabled');
  }

  get isUPICAWEnabled() {
    return true;
  }

  get isPLSwitchEnabled() {
    const isRazorXExperimentEnabled = this.getExpStatus('pl_swith_v2');
    const isRoleAllowed =
      this.userRole === rolesList.OWNER ||
      this.userRole === rolesList.ADMIN ||
      this.userRole === rolesList.MANAGER;

    const isMerchantOnOldPL = this.isPaymentlinksV2CompatEnabled && !this.isPaymentlinksV2Enabled;

    return isRoleAllowed && isRazorXExperimentEnabled && isMerchantOnOldPL;
  }

  get isOnboardingV2Enabled() {
    return this.getExpStatus('onboarding_v2');
  }

  get isActivationMccPendingProgressbarDisabled() {
    return this.isOrgRZP;
  }

  get isBDAndAovEnabled() {
    return true;
  }

  get isAdharEkycRequired() {
    return this.isOrgRZP && this.getExpStatus('adharEkyc_for_reg_businessTypes');
  }

  get isAdharEkycRequiredForTrustSocietyNgo() {
    if (this.isSourceRX) {
      return false;
    }
    return this.getExpStatus('aadharEkyc_for_trust_society_ngo') && this.isOrgRZP;
  }

  get isInternalStatusPageEnabled() {
    return this.getExpStatus('status_page_enable');
  }

  // TODO: Remove from razorX bcoz it's rolled out 100%
  get isPaymentPageReceiptsEnabled() {
    return true;
  }

  get isPaymentPageDescriptionRequired() {
    const userBusinessType = Number(this.business_type);
    const isRazorXExperimentEnabled = this.getExpStatus('pp_description_required');

    // required for proprietorship and unregistered
    const REQUIRED_DESCRIPTION_BUSINESS_TYPES = [1, 11];
    return (
      REQUIRED_DESCRIPTION_BUSINESS_TYPES.indexOf(userBusinessType) !== -1 &&
      isRazorXExperimentEnabled
    );
  }

  // This is for new payment links microservice.
  // If enabled, then all the apis before sending data, and after fetching/receiving data must transform its data, as FE operate on old structure until 100% rollout.
  get isPaymentlinksV2Enabled() {
    return this.isFeatureEnabled('paymentlinks_v2');
  }

  get isPaymentlinksV2CompatEnabled() {
    return this.isFeatureEnabled('paymentlinks_v2_compat');
  }

  get isNonFldgLoansEnabled() {
    return this.isFeatureEnabled('allow_non_fldg_loans');
  }

  get isLoansEnabled() {
    return this.isFeatureEnabled('loan');
  }

  get isLOSEnabled() {
    return this.isFeatureEnabled('los');
  }

  get isLOCEnabled() {
    return this.isFeatureEnabled('loc');
  }

  get isCashAdvanceStage1Enabled() {
    return this.isFeatureEnabled('loc_stage_1');
  }

  get isCashAdvanceStage2Enabled() {
    return this.isFeatureEnabled('loc_stage_2');
  }

  get isLocCliOfferEnabled() {
    return this.isFeatureEnabled('loc_cli_offer');
  }

  get isWithdrawFeatureEnabled() {
    return this.isFeatureEnabled('withdraw_loc');
  }

  get isCashOnCardEnabled() {
    return this.isFeatureEnabled('cash_on_card');
  }

  get isNetBankingEnabled() {
    return true;
  }

  get isCardsLOSEnabled() {
    return this.isFeatureEnabled('capital_cards_eligible');
  }

  get isCardsEnabled() {
    return this.isFeatureEnabled('capital_cards');
  }

  get isUnregisteredBusiness() {
    const userBusinessType = Number(this.business_type);
    const UNREGISTERED_BUSINESS_TYPES = [2, 11];
    return UNREGISTERED_BUSINESS_TYPES.indexOf(userBusinessType) !== -1;
  }

  get isCommissionInvoicesEnabled() {
    return this.isFeatureEnabled('generate_partner_invoice');
  }

  get isPLBatchUploadEnabled() {
    return this.isFeatureEnabled('pl_batch_upload_feature');
  }

  get isBbpsEnabled() {
    return this.isFeatureEnabled('feature_bbps');
  }

  get isAppSwitcherEnabled() {
    return this.getExpStatus('app_switcher');
  }

  get isSourceRX() {
    const query = QueryString.parse(window.location.search);
    const isSourceRX = !!(query && query.merchant && query.merchant === 'x');
    return isSourceRX;
  }

  get showOnDemandDeduction() {
    return this.isFeatureEnabled('show_on_demand_deduction');
  }

  get isAutomatedLOCEligible() {
    return this.isFeatureEnabled('automated_loc_eligible');
  }

  get isNPSAnnouncementPG1m() {
    return this.isFeatureEnabled('nps_survey_pg_1m');
  }

  get isNPSAnnouncementPG6m() {
    return this.isFeatureEnabled('nps_survey_pg_6m');
  }

  get isNPSAnnouncementPL() {
    return this.isFeatureEnabled('nps_survey_payment_links');
  }

  get isMerchantExpiryPL() {
    return this.isFeatureEnabled('enable_merchant_expiry_pl');
  }

  get isEmailMandatoryOnL1() {
    return false;
  }

  get isEmailNonMandatoryOnL1() {
    return false;
  }

  get isEmailNonMandatoryOnL2Form() {
    if (this.isSourceRX) return false; // not required for Razorpay X;
    return this.isOrgRZP;
  }

  get isNPSAnnouncementPP() {
    return this.isFeatureEnabled('nps_survey_payment_pages');
  }

  get secondFactorAuthOfCurrentMerchant() {
    return this.merchants[this.current].second_factor_auth;
  }

  get secondFactorAuthOfUser() {
    return this.user.second_factor_auth;
  }
  // Expirment return the MID account has VA on Yes bank or ICICI account
  get isVAAccountOnSCMigration() {
    return this.getExpStatus('rbl_migration_banner');
  }

  // No experiment of disable-edit-<moduleName> => Module is not restricted
  isViewRestrictedByRazorX(moduleName) {
    // Eg: disable-view-reports (if corresponding experiment is "on", it can't be viewed by those merchants)
    return this.getExpStatus(`disable-view-${moduleName}`);
  }

  // No experiment of disable-edit-<moduleName> => Module is not restricted
  isEditRestrictedByRazorX(moduleName) {
    // Eg: disable-edit-reports (if corresponding experiment is "on", it can't be edited for those merchants)
    return this.getExpStatus(`disable-edit-${moduleName}`);
  }

  isInstrumentRequestAllowed() {
    return this.getExpStatus('instrument_request_merchant_dashboard');
  }

  get isInstrumentRequestHidden() {
    return isOrgFeatureExist('hide_instrument_request');
  }

  // Bank account auto update or old workflow with the approval from admin
  bankAccountAutoUpdateOrWorkflow() {
    return true;
  }

  // Blocks bank account update feature
  blockBankAccountUpdate() {
    return this.getExpStatus('block_bank_account_update_merchant_dashboard');
  }

  // NPS survey feature
  showNPSSurvey() {
    return this.getExpStatus('dashboard_show_nps_survey');
  }

  showCsmExperienceSurvey() {
    return this.getExpStatus('csm_experience_survey');
  }

  // CSAT Survey feature
  showCSATSurvey() {
    return this.getExpStatus('show_csat_survey');
  }

  // Setter Methods
  set secondFactorAuthOfCurrentMerchant(secondFactorAuth) {
    this.merchants[this.current].second_factor_auth = secondFactorAuth;
  }

  set secondFactorAuthOfUser(secondFactorAuth) {
    this.user.second_factor_auth = secondFactorAuth;
  }

  isWhatsappNotificationEnabled() {
    return this.getExpStatus('whatsapp_notification_enablement');
  }

  get isAppStoreEnabled() {
    return this.getExpStatus('partner_app_store');
  }

  get isPartnershipFUX() {
    // FUX disabled for partnerTypes - bank and fully_managed
    // only for RZP org
    if (this.isPartner('bank', 'fully_managed') || !this.isAllowedView('partner_home')) {
      return false;
    }
    return this.isOrgRZP || this.isOrgCurlec;
  }

  get isPartnershipNPS() {
    const variant = getSplitzExperimentVariant('partnership_nps');
    return variant?.name === 'exposed';
  }

  get isShowResumeOnboarding() {
    const variant = getSplitzExperimentVariant('show_resume_onboarding');
    return variant?.name === 'exposed';
  }

  get isSubMerchantKycEnabled() {
    // only for RZP org
    // only to owner,admin,manager
    return this.isOrgRZP && this.isAllowedView('submerchants');
  }

  get canSkipPoiValidation() {
    return true;
  }

  get canGenerateTnCPage() {
    // controlling TnC page for both rzp or axis with two seprate experiment.
    return (
      (this.getExpStatus('merchant_tnc') && this.isOrgAxis) ||
      (this.getExpStatus('rzp_merchant_tnc') && this.isOrgRZP)
    );
  }

  get isL2AllowedForPoiInitiated() {
    return this.isOrgRZP && this.isUnregisteredBusiness;
  }

  get isAadharEkycMandatory() {
    const query = QueryString.parse(window.location.search);
    const isSourceRX = !!(query && query.merchant && query.merchant === 'x');

    // not required for Razorpay X, partner accounts and sub merchants
    if (isSourceRX || this.isPartner() || this.isSubMerchant) {
      return false;
    }

    return this.getExpStatus('mandatory_aadhar_ekyc');
  }

  get isGstinMandatory() {
    return true;
  }

  get isGstinAddFlowEnabled() {
    return true;
  }

  get isGstinEditFlowEnabled() {
    return true;
  }

  get isSyncExperimentEnabled() {
    return this.isOrgRZP;
  }

  get isRecurringMoreAccountType() {
    return this.getExpStatus('recurring_more_account_type');
  }

  get isOnboardingCouponEnabled() {
    return this.getExpStatus('mtu_coupon_code') && this.isOrgRZP;
  }

  get autoOpenOnboardingCoupon() {
    return this.isOrgRZP;
  }

  get isIndependentPartnerKYCEnabled() {
    const variant = getSplitzExperimentVariant('independent_partner_kyc');
    return variant?.name === 'exposed';
  }

  get isAutoRefreshExperimentEnabled() {
    return this.getExpStatus('auto_refresh_experiment');
  }

  get isSyncBankVerificationEnabled() {
    if (this.isSourceRX) return false; // not required for Razorpay X;
    return this.getExpStatus('KARZA_BANK_ACCOUNT_VERIFICATION') && this.isOrgRZP;
  }

  get isProductRecommendationEnabled() {
    return this.getExpStatus('product_recommendation');
  }

  get isLoansCollectionsEnabled() {
    return this.getExpStatus('loans_collections_dashboard');
  }

  get isAutoPLEnabled() {
    return this.isOrgRZP;
  }

  get isGstinAutoPopulate() {
    return this.getExpStatus('bvs_get_gst_details');
  }

  get showL1FormOnLogin() {
    return (
      getSplitzExperimentVariant('show_L1_Form_on_login')?.variables?.result === 'on' &&
      this.isOrgRZP
    );
  }

  get autoOpenL1Form() {
    return this.getExpStatus('auto-open-L1-form') && this.isOrgRZP;
  }

  get isGstinLLpinCinSyncFlowEnabled() {
    // not required for Razorpay X, partner accounts and sub merchants
    if (this.isSourceRX || this.isPartner() || this.isSubMerchant) {
      return false;
    }
    return this.getExpStatus('bvs_in_sync') && this.isOrgRZP;
  }

  get isGstinSyncFlowEnabled() {
    return this.isGstinLLpinCinSyncFlowEnabled && this.getExpStatus('gstin_sync');
  }
  get isLlpinSyncFlowEnabled() {
    return this.isGstinLLpinCinSyncFlowEnabled && this.getExpStatus('llpin_sync');
  }
  get isCinSyncFlowEnabled() {
    return this.isGstinLLpinCinSyncFlowEnabled && this.getExpStatus('cin_sync');
  }

  get autoOpenL2Form() {
    return this.getExpStatus('auto-open-L2-form') && this.isOrgRZP;
  }

  get isLiteOnboarding() {
    return this.getExpStatus('lite_onboarding') && this.isOrgRZP;
  }

  get isUpdatedLiteOnboarding() {
    return this.getExpStatus('updated_lite_onboarding') && this.isOrgRZP;
  }

  get isActivationFormFullView() {
    // not required for Razorpay X, partner accounts and sub merchants
    if (this.isSourceRX || this.isPartner() || this.isSubMerchant) {
      return false;
    }
    return (
      getSplitzExperimentVariant('show_activation_form_full_view')?.variables?.result === 'on' &&
      this.isOrgRZP
    );
  }

  get isMsmeCertificateEnabled() {
    return (
      getSplitzExperimentVariant('COLLECT_MSME_CERTIFICATE_PROPRIETORSHIP')?.variables?.result ===
      'on'
    );
  }

  get isDigilockerEkyc() {
    return (
      getSplitzExperimentVariant('digilocker_aadhaar_ekyc')?.variables?.result === 'on' &&
      this.isOrgRZP
    );
  }

  get isOnboardAsResellers() {
    const variant = getSplitzExperimentVariant('partnership_onboard_resellers');
    return variant?.name === 'exposed';
  }

  get isShowInvoiceCurrentFY() {
    const variant = getSplitzExperimentVariant('invoice_currentFY');
    return variant?.name === 'exposed';
  }

  get isShowPayrollWidgetEnabled() {
    return getSplitzExperimentVariant('show_payroll_widget_exp').variables?.result === 'on';
  }

  get isShowAffordabilityWidget() {
    return getSplitzExperimentVariant('show_affordability_widget_exp').variables?.result === 'on';
  }

  get isShowAffWidgetShopifyWaitlist() {
    return (
      getSplitzExperimentVariant('show_aff_widget_shopify_wait_list').variables?.result === 'on'
    );
  }

  get isShowAffWidgetWoocWaitlist() {
    return getSplitzExperimentVariant('show_aff_widget_wooc_wait_list').variables?.result === 'on';
  }

  get isShowSegregatedCreditEmi() {
    return (
      getSplitzExperimentVariant('show_segregated_credit_emi_methods').variables?.result === 'on'
    );
  }

  get isApiKeysRevampEnabled() {
    return (
      getSplitzExperimentVariant('api_keys_revamp')?.variables?.result === 'on' && !this.isOrgCurlec
    );
  }

  get isAccountAndSettingsRevampEnabled() {
    return (
      getSplitzExperimentVariant('account_settings_revamp')?.variables?.result === 'on' &&
      (this.isOrgRZP || this.isOrgCurlec)
    );
  }

  get isPaymentHandleSplitzEnabled() {
    return getSplitzExperimentVariant('payment_handle_onboarding')?.variables?.result === 'on';
  }

  get isBundlePricingEnabled() {
    return getSplitzExperimentVariant('bundle_pricing')?.variables?.result === 'on';
  }

  get isProductLedOnboarding() {
    return getSplitzExperimentVariant('product_led_onboarding')?.variables?.result === 'on';
  }

  get isBankAccountUpdateRevampEnabled() {
    return getSplitzExperimentVariant('bank_account_update_revamp')?.variables?.result === 'on';
  }

  get isGetTicketApiMigration() {
    return getSplitzExperimentVariant('get_ticket_migration')?.variables?.result === 'on';
  }

  get isContactDetailsRevamp() {
    return getSplitzExperimentVariant('contact_details_revamp')?.variables?.result === 'on';
  }

  get isUserNameUpdateEnabled() {
    return getSplitzExperimentVariant('user_name_update')?.variables?.result === 'on';
  }

  get showTerminalStatusBanner() {
    return getSplitzExperimentVariant('show_terminal_status_banner')?.variables?.result === 'on';
  }

  get isProductLedOnboardingRZP() {
    return this.isProductLedOnboarding && this.isOrgRZP;
  }

  get isIERevampEnabled() {
    return (
      getSplitzExperimentVariant('international_enablement_revamp')?.variables?.result === 'on'
    );
  }

  get isUniversalSearchEnabled() {
    return (
      getSplitzExperimentVariant('universal_search_enabled')?.variables?.result === 'on' &&
      this.isOrgRZP
    );
  }

  get isSearchv2Phase1Enabled() {
    return (
      getSplitzExperimentVariant('search_v2_phase_1')?.variables?.result === 'on' && this.isOrgRZP
    );
  }

  get isLeftNavRevampEnabled() {
    return getSplitzExperimentVariant('left_nav_revamp')?.variables?.result === 'on';
  }

  get isFetchTicketsApiMigration() {
    return getSplitzExperimentVariant('fetch_tickets_migration')?.variables?.result === 'on';
  }

  get isPartnershipForCapitalEnabled() {
    const variant = getSplitzExperimentVariant('partnership_capital');
    return variant?.name === 'enable';
  }

  get isRevokeApplicationEnabled() {
    const variant = getSplitzExperimentVariant('revoke_application');
    return variant?.name === 'enable';
  }

  get isSettlementV3RevampEnabled() {
    return (
      getSplitzExperimentVariant('settlement_v3_revamp')?.variables?.result === 'on' &&
      this.isOrgRZP
    );
  }

  get isPartnershipForPhantomEnabled() {
    const variant = getSplitzExperimentVariant('partnership_for_phantom');
    return variant?.name === 'enable';
  }

  get isCustomReportExtensionsEnabled() {
    return this.isOrgFeatureEnabled('custom_report_extensions');
  }

  get isInternationalMethodsHidden() {
    return this.isOrgFeatureEnabled('hide_international_methods');
  }

  get isShowInternationalPaymentBtnExpEnabled() {
    const variant = getSplitzExperimentVariant('show_international_payments_button_ab');

    return (
      getMode() === 'live' &&
      variant?.variables?.enable === 'true' &&
      (this.internationalActivationFlow.isWhitelistFlow ||
        this.internationalActivationFlow.isGraylistFlow ||
        !this.internationalActivationFlow.international_activation_flow) &&
      !this.international
    );
  }

  get isFtuxEnabled() {
    return getSplitzExperimentVariant('onboarding_ftux')?.variables?.result === 'on';
  }

  get isEcosystemDowntimeEnabled() {
    return getSplitzExperimentVariant('ecosystem_downtimes')?.variables?.result === 'on';
  }

  get showIsPlusPlusExperiment() {
    return getSplitzExperimentVariant('capital_isplusplus_splitz')?.variables?.result === 'on';
  }

  get isCheckoutAnalyticsEnabled() {
    return getSplitzExperimentVariant('checkout_analytics')?.variables?.result === 'on';
  }

  get isSrAdminEnabled() {
    return getSplitzExperimentVariant('success_rate_admin')?.variables?.result === 'on';
  }

  get isDynamicPlOffset() {
    return this.isFeatureEnabled('dynamic_pl_offset');
  }

  get isLRSEducationFlow() {
    return this.isFeatureEnabled('lrs_education_flow');
  }

  get isOmniChannelMerchant() {
    const variant = getSplitzExperimentVariant('omni_channel_merchants');
    return variant?.name === 'show-ezetap-txn';
  }
}

function _isAllowed(userRole, moduleName, permissionsMap) {
  if (!moduleName) return false;

  const restrictedModulesForOrg = antiOrgsModules[getOrg().custom_code];

  if (restrictedModulesForOrg) {
    const isModuleAllowed = restrictedModulesForOrg.indexOf(moduleName) === -1;
    if (!isModuleAllowed) {
      return false; // Module not allowed for Org
    }
  }

  const allowedRoles = permissionsMap[moduleName.toLowerCase()];
  if (!allowedRoles) {
    return false; // Module is missing in the map
  }

  const isAllowed = allowedRoles.indexOf(userRole) > -1;
  return isAllowed;
}

function getSplitzExperimentVariant(experimentName) {
  const splitzExperiments = window.rzp_user?.splitz_experiments;
  let splitzExperimentVariant = null;

  if (splitzExperiments) {
    Object.keys(splitzExperiments).forEach((experimentId) => {
      const splitzExperiment = splitzExperiments[experimentId];
      if (abExperimentsMap[experimentName]?.includes(experimentId) && !isEmpty(splitzExperiment)) {
        splitzExperimentVariant = splitzExperiment;
      }
    });
  }
  return splitzExperimentVariant || {};
}

/* Check case-insensitive feature flag check */
export function isOrgFeatureExist(feature) {
  const features = getOrg()?.features;
  return features?.indexOf(feature) > -1;
}
