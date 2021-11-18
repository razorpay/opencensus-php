import ajax from 'merchant/utils/ajax';
import QueryString from 'query-string';
import { filterBy, getURLQueryParams } from 'common/utils/rzp-utils';
import { RZPFeatures } from 'merchant/helpers/data';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';
import isEmpty from '@universe/utils/isEmpty';
import { fetchFeaturesAjax } from 'merchant/reducers/config';
import { getOrg } from 'merchant/store';
import { getOnBoardingDataFromLocalState } from 'merchant/components/OnBoarding';
import { getItem } from 'common/utils/localStorage';
import { getXCAStatus } from 'common/ui/NotificationsDropdown/Neostone/common/utils';

import rolesList from 'merchant/helpers/permissions/roles-list';
import {
  roleEditPermissions,
  roleViewPermissions,
  antiOrgsModules,
  antiOrgsFeatures,
} from 'merchant/helpers/permissions';

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

// TODO: Rename fn. name
export function setFeatures(features) {
  const enabledFeatures = filterBy(features, 'value', true);

  return enabledFeatures;
}

export default class User {
  merchants = {};

  constructor(props) {
    Object.assign(this, props);
    if (!this.tags) {
      this.tags = [];
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

  get isOrgRZP() {
    const org = getOrg();

    if (org && org.custom_code && org.custom_code.toLowerCase() === 'rzp') {
      return true;
    }

    return false;
  }

  isProductHiddenForWhiteLabelledOrg(moduleName) {
    if (!PRODUCT_KEY_MAPS.includes(moduleName)) {
      return false;
    }

    if (!this.isWhiteLabelledOrg) {
      return false;
    }

    return !this.findTag(`white_labelled_${moduleName}`);
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

  // KYC form submitted
  get isSubmitted() {
    return !!parseInt(this.submitted, 10);
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

  get isSuperCheckoutEnabled() {
    return (
      this.isFeatureEnabled('one_cc_merchant_dashboard') &&
      getSplitzExperimentVariant('dashboard_super_checkout')?.variables?.result === 'on'
    );
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

  get isStoresUrlEnabled() {
    return this.getExpStatus('stores_url');
  }

  get isQRCodeProductEnabled() {
    return this.isFeatureEnabled('qr_codes');
  }

  get isOwner() {
    return this.userRole === rolesList.OWNER;
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

  get isInvoiceCreateFlowUXOptimizationEnabled() {
    return this.getExpStatus('inv_create_flow_ux');
  }

  get isRTBProgramEnabled() {
    return this.isFeatureEnabled('rzp_trusted_badge');
  }

  get isDisputePresentmentEnabled() {
    return this.isFeatureEnabled('dispute_presentment');
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

  get isProjectNitroEnabled() {
    // moving nitro to splitz phase wise, so keeping checks for both splitz and razorx experiments currently.

    const splitzExperimentVariant = getSplitzExperimentVariant('project_nitro');

    if (splitzExperimentVariant?.variables) {
      return splitzExperimentVariant.variables?.result === 'on';
    }

    return (
      this.getExpStatus('project_nitro') ||
      this.getExpStatus('project_nitro_1') ||
      this.getExpStatus('project_nitro_feb_2021') ||
      this.getExpStatus('project_nitro_feb_2021_1') ||
      this.getExpStatus('nitro_hyderabad_v2') ||
      this.getExpStatus('nitro_hyderabad_v3') ||
      this.getExpStatus('nitro_midmarket_mumbai_v1')
    );
  }

  get isProjectKeystoneCorporateCardsEnabled() {
    return (
      getSplitzExperimentVariant('keystone_corporate_cards_experiment')?.variables?.result === 'on'
    );
  }

  get isProjectKeystoneCashAdvanceEnabled() {
    return (
      getSplitzExperimentVariant('keystone_cash_advance_experiment')?.variables?.result === 'on'
    );
  }

  get isProjectNitroCorporateCard() {
    return getSplitzExperimentVariant('nitro_corporate_cards')?.variables?.result === 'on';
  }

  get isGrowthServiceEnabled() {
    return (
      getSplitzExperimentVariant('growth_service_rollout_experiment')?.variables?.result === 'on'
    );
  }

  get isProjectMoonshineEnabled() {
    return getSplitzExperimentVariant('project_moonshine')?.variables?.result === 'on';
  }

  get isWhatsNewLazyEnabled() {
    return getSplitzExperimentVariant('whats_new_lazy_experiment')?.variables?.result === 'on';
  }

  get isAbcBannerEnabled() {
    return getSplitzExperimentVariant('abc_banner_experiment')?.variables?.result === 'on';
  }

  get isPartOfNeostone() {
    return getSplitzExperimentVariant('neostone_experiment')?.variables?.result === 'on';
  }

  isNeostoneFlowEnabled = (showState = '') => {
    return (
      this.isProjectNitroEnabled &&
      this.isPartOfNeostone &&
      getXCAStatus(this).showState === showState &&
      this.isOwner &&
      !this.isRazorxRXCASelfServeFlowEnabled
    );
  };

  get isStartupCongratulationBannerEnabled() {
    return (
      getSplitzExperimentVariant('startup_congratulations_banner_experiment')?.variables?.result ===
      'on'
    );
  }
  get isCrossBorderPaymentsCampaignEnabled() {
    return getSplitzExperimentVariant('cross_border_payments_campaign')?.variables?.result === 'on';
  }

  get isNitroIciciBrandedCampaignEnabled() {
    return getSplitzExperimentVariant('nitro_icici_branded_experiment')?.variables?.result === 'on';
  }

  get isNitroIciciRemarketingCampaignEnabled() {
    return (
      getSplitzExperimentVariant('nitro_icici_remarketing_experiment')?.variables?.result === 'on'
    );
  }

  get isNitroCCCampaignEnabled() {
    return getSplitzExperimentVariant('nitro_CC_experiment')?.variables?.result === 'on';
  }

  get isCatalystCampaignEnabled() {
    return getSplitzExperimentVariant('catalyst_campaign_experiment')?.variables?.result === 'on';
  }

  get isUltraCampaignBannerEnabled() {
    return (
      getSplitzExperimentVariant('ultra_campaign_banner_experiment')?.variables?.result === 'on'
    );
  }

  get isUltraP2CashAdvanceCampaignBannerEnabled() {
    return (
      getSplitzExperimentVariant('ultra_p2_cash_advance_banner_experiment')?.variables?.result ===
      'on'
    );
  }

  get isNitroFormFillEnabled() {
    return getSplitzExperimentVariant('nitro_form_ab_experiment')?.variables?.result === 'on';
  }

  get isPartOfAiSensyBannerExperiment() {
    return getSplitzExperimentVariant('ai_sensy_banner')?.variables?.result === 'on';
  }

  get isPartOfZapierIntegrationExperiment() {
    return getSplitzExperimentVariant('zapier_integration')?.variables?.result === 'on';
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

  get isCallbackCategoryExpEnabled() {
    return this.getExpStatus('schedule_callback_category');
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

  get isNewSupportChangesEnabled() {
    return this.getExpStatus('TicketSystemSupport');
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
    return !!this.isOrgRZP;
  }

  get isMinimumFirstPaymentEnabled() {
    return this.isFeatureEnabled('pl_first_min_amount');
  }

  get isPPSuccessPage() {
    return this.getExpStatus('pp_success_page');
  }

  get isPPNewFooterUX() {
    return this.getExpStatus('pp_hostedpage_new_footer');
  }

  get isPPDonationGoalTracker() {
    return this.getExpStatus('pp_donation_goal_tracker');
  }

  /* Check case-insensitive tag check existence */
  findTag(tag) {
    return this.tags.some((t) => t.toLowerCase() === tag.toLowerCase());
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
    return ((this.experiments || {})[name] || {}).result === 'on';
  }

  get isAddProviderEnabled() {
    return this.getExpStatus('optimizer_add_provider');
  }

  get isOndemandSettlementEnabled() {
    return this.isFeatureEnabled('ES_ON_DEMAND');
  }

  get isSupportDetails2FAEnabled() {
    return this.getExpStatus('support_details_2FA');
  }

  get isComdelApiEnabled() {
    return this.getExpStatus('comdel_hdfc_test');
  }

  get isFdTicketsEnabled() {
    return this.getExpStatus('view_fd_tickets');
  }

  get isNewGrievanceFlowEnabled() {
    return this.getExpStatus('show_new_grievance_flow');
  }

  get isTicketRevampFlowEnabled() {
    return this.getExpStatus('ticket_creation_flow_revamp_dashboard');
  }

  get isScheduleCallbackEnabled() {
    return this.getExpStatus('show_schedule_callback');
  }

  get isTicketCreationFlowRevamp() {
    return this.getExpStatus('ticket_creation_flow_revamp');
  }

  get isAnnouncementIconEnabled() {
    return this.getExpStatus('AnnouncementIconJan2021');
  }

  get isAnnouncementTextEnabled() {
    return getSplitzExperimentVariant('announcement_text_experiment')?.variables?.result === 'on';
  }

  get isWhatsNewTextEnabled() {
    return getSplitzExperimentVariant('whats_new_text_experiment')?.variables?.result === 'on';
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

  get isSelfServeCreditsEnabled() {
    return this.getExpStatus('self_serve_credits');
  }

  get isFtxEnabled() {
    return this.getExpStatus('ftx_2021');
  }

  get isEmailSelfServeEnabled() {
    return this.getExpStatus('email_self_serve');
  }

  get isCovidReliefFlowEnabled() {
    return this.getExpStatus('covid_19_donation_show');
  }

  get isFeeBearerSelfServeOn() {
    return this.getExpStatus('fee_bearer_self_serve');
  }

  get isAutomaticSettlementEnabled() {
    return this.isFeatureEnabled('ES_AUTOMATIC');
  }

  get isOndemandSettlementsRestricted() {
    return this.isFeatureEnabled('es_on_demand_restricted');
  }

  get isWebsiteSelfServeOn() {
    return this.getExpStatus('website_self_serve');
  }

  get isTransactionLimitUpdateSelfServeOn() {
    return this.getExpStatus('transaction_limit_update_self_serve');
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

  get isNewPPSuccessModalEnabled() {
    return this.getExpStatus('new_pp_success_modal');
  }

  get isVirtualVPAPrefixEnabled() {
    // Doing 100% rollout since there is issues in razor-x
    // TODO: Completely remove views/SmartCollect/VirtualAccounts/Create/CreateV1
    return true;
  }

  get isSmartCollectAdvancedSearchFeaturesEnabled() {
    return this.getExpStatus('va_search');
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

  get isQRCodesEnabled() {
    if (this.isQRCodeComingSoonExpEnabled) {
      return true;
    }

    // TODO: remove this
    return this.getExpStatus('qr_codes');
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

  get isQRCodeComingSoonExpEnabled() {
    if (this.isQRCodeProductEnabled) {
      return true;
    }

    return this.getExpStatus('qr_code_coming_soon');
  }

  get isPaymentLinkCreationV2Enabled() {
    return this.isPaymentlinksV2Enabled;
  }

  get isSellerAppRole() {
    const userRole = this.userRole;
    return [rolesList.SELLERAPP, rolesList.SELLERAPP_PLUS].indexOf(userRole) > -1;
  }

  get isRouteCodeSupportEnabled() {
    return this.isFeatureEnabled('route_code_support');
  }

  get isRouteBatchUploadEnabled() {
    return this.getExpStatus('route_batch_upload');
  }

  get isRouteTransferStateEnabled() {
    return this.getExpStatus('route_transfer_state');
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

  get isBDAndAovEnabled() {
    return this.getExpStatus('aov_functionality');
  }

  get isWebhooksStatsEnabled() {
    // currently this experiment is not added in User/Service.php
    return this.getExpStatus('webhook_stats');
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

  get isPaymentPageZapierBannerEnabled() {
    return getSplitzExperimentVariant('pp_zapier_announcement')?.variables?.result === 'on';
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

  get isWithdrawFeatureEnabled() {
    return this.isFeatureEnabled('withdraw_loc');
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

  get isOrgAxis() {
    const currentOrg = getOrg().custom_code;

    return currentOrg === 'axis';
  }

  get isWhiteLabelledOrg() {
    return this.isOrgAxis;
  }

  get orgCustomCode() {
    return getOrg().custom_code;
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

  get isNPSAnnouncementPP() {
    return this.isFeatureEnabled('nps_survey_payment_pages');
  }

  get secondFactorAuthOfCurrentMerchant() {
    return this.merchants[this.current].second_factor_auth;
  }

  get secondFactorAuthOfUser() {
    return this.user.second_factor_auth;
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

  // Bank account auto update or old workflow with the approval from admin
  bankAccountAutoUpdateOrWorkflow() {
    return this.getExpStatus('bank_account_update_merchant_dashboard');
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

  get isPartnershipForXEnabled() {
    const variant = getSplitzExperimentVariant('partnership_for_razorpayx');
    return variant?.name === 'exposed';
  }

  get isSubMerchantKycResellerEnabled() {
    const variant = getSplitzExperimentVariant('submerchant_kyc_reseller');
    return variant?.name === 'exposed';
  }

  get canSkipPoiValidation() {
    return this.getExpStatus('bvs_personal_pan_validation');
  }

  get canGenerateTnCPage() {
    // controlling TnC page for both rzp or axis with two seprate experiment.
    return (
      (this.getExpStatus('merchant_tnc') && this.isOrgAxis) ||
      (this.getExpStatus('rzp_merchant_tnc') && this.isOrgRZP)
    );
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
    return this.getExpStatus('mandatory_gstin_input');
  }

  get isSyncExperimentEnabled() {
    return this.getExpStatus('sync_experiment') && !!this.isOrgRZP;
  }

  get isRecurringMoreAccountType() {
    return this.getExpStatus('recurring_more_account_type');
  }

  get isOnboardingCouponEnabled() {
    return this.getExpStatus('mtu_coupon_code') && !!this.isOrgRZP;
  }

  get autoOpenOnboardingCoupon() {
    return this.getExpStatus('auto_open_mtu_coupon') && !!this.isOrgRZP;
  }

  get isIndependentPartnerKYCEnabled() {
    const variant = getSplitzExperimentVariant('independent_partner_kyc');
    return variant?.name === 'exposed';
  }

  get isAutoRefreshExperimentEnabled() {
    return this.getExpStatus('auto_refresh_experiment');
  }

  get isSyncBankVerificationEnabled() {
    return this.getExpStatus('KARZA_BANK_ACCOUNT_VERIFICATION') && !!this.isOrgRZP;
  }

  get isProductRecommendationEnabled() {
    return this.getExpStatus('product_recommendation');
  }

  get canSwitchOnboardingCard() {
    return this.getExpStatus('switch_onboarding_card');
  }

  get isLoansCollectionsEnabled() {
    return this.getExpStatus('loans_collections_dashboard');
  }

  get isAutoPLEnabled() {
    return this.getExpStatus('auto_pl') && !!this.isOrgRZP;
  }

  get isGstinAutoPopulate() {
    return this.getExpStatus('bvs_get_gst_details');
  }

  get showL1FormOnLogin() {
    return this.getExpStatus('show_L1_Form_on_login') && !!this.isOrgRZP;
  }

  get autoOpenL1Form() {
    return this.getExpStatus('auto-open-L1-form') && !!this.isOrgRZP;
  }

  get isNewSettlementServiceEnabled() {
    return this.isFeatureEnabled('new_settlement_service');
  }

  get autoOpenL2Form() {
    return this.getExpStatus('auto-open-L2-form') && !!this.isOrgRZP;
  }

  get isLiteOnboarding() {
    return this.getExpStatus('lite_onboarding') && !!this.isOrgRZP;
  }

  get isUpdatedLiteOnboarding() {
    return this.getExpStatus('updated_lite_onboarding') && !!this.isOrgRZP;
  }
}

function _isAllowed(userRole, moduleName, permissionsMap) {
  if (!moduleName) {
    return false;
  }

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
