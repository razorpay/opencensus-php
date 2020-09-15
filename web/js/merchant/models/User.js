import ajax from 'merchant/utils/ajax';
import QueryString from 'query-string';
import { filterBy } from 'common/utils/rzp-utils';
import { RZPFeatures } from 'merchant/helpers/data';

import { fetchFeaturesAjax } from 'merchant/reducers/config';
import LocalStorageService from 'common/utils/localStorage';
import { getOrg, getMode } from 'merchant/store';
import { getOnBoardingDataFromLocalState } from 'merchant/components/OnBoarding';
import { getURLQueryParams } from 'common/utils/rzp-utils';

import rolesList from 'merchant/helpers/permissions/roles-list';
import {
  roleEditPermissions,
  roleViewPermissions,
  antiOrgsModules,
  antiOrgsFeatures,
} from 'merchant/helpers/permissions';

// TODO: Rename fn. name
export function setFeatures(features) {
  let enabledFeatures = filterBy(features, 'value', true);

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
    let promise = new Promise((resolve, reject) => {
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
              let newUser = new User(response.data);
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
      var currentMerchant = this.merchants[this.current];
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

    return isEditAllowed;
  }

  isAllowedView(moduleName) {
    let isViewAllowed = _isAllowed(this.userRole, moduleName, roleViewPermissions);

    if (this.isViewRestrictedByRazorX(moduleName)) {
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
      isRXV2OnboardingEnabled: this.isRXV2OnboardingEnabled,

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
        const isRXV2Onboarding = this.isRXV2OnboardingEnabled && isSourceRX;

        // Assume L1 is submitted if activation form is inside RX and V2 onboarding experiment is enabled
        if (isRXV2Onboarding) {
          return true;
        }

        if (this.activated === 1) {
          return true;
        }
        if (!this.isUnregisteredBusiness) {
          return !!this.activation_flow;
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

  get needsClarification() {
    return this.activation_status === 'needs_clarification';
  }

  // KYC form submitted
  get isSubmitted() {
    return !!parseInt(this.submitted);
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

  // 100% rollout done. Exp to be removed shortly
  get isPaymentButtonsEnabled() {
    return true;

    const { isEnabled } = getOnBoardingDataFromLocalState(RZPFeatures.PB);

    return isEnabled;
  }

  get isPaymentPageEmailOptional() {
    return this.isFeatureEnabled('email_optional');
  }

  get isPaymentPageContactOptional() {
    return this.isFeatureEnabled('contact_optional');
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
    return this.getExpStatus('project_nitro');
  }

  get isChargeAtWillEnabled() {
    return this.findTag('Charge_at_will');
  }

  get isMerchantRestricted() {
    return this.restricted;
  }

  get isAgentRole() {
    return this.findTag('enable_agent_role');
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
    let pluckKey = 'feature';

    return (this.features || []).map((object) => {
      return object[pluckKey];
    });
  }

  get showInstantActivation() {
    return this.isOrgRZP && (!!this.activation_flow || this.instant_activations);
  }

  get isMinimumFirstPaymentEnabled() {
    return this.isFeatureEnabled('pl_first_min_amount');
  }

  /* Check case-insensitive tag check existence */
  findTag(tag) {
    return this.tags.some((t) => t.toLowerCase() === tag.toLowerCase());
  }

  /**
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

  get isOndemandSettlementEnabled() {
    return this.isFeatureEnabled('ES_ON_DEMAND');
  }

  get isSettlementOndemandRouteEnabled() {
    return this.isFeatureEnabled('use_settlement_ondemand');
  }

  get isAutomaticSettlementEnabled() {
    return this.isFeatureEnabled('ES_AUTOMATIC');
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

  get isInttCurrenciesEnabled() {
    return true;

    return !!this.international && this.getExpStatus('international_currencies');
  }

  get isDefaultPLBatchRemindersEnabled() {
    return this.getExpStatus('pl_batch_reminders');
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

  get isRXV2OnboardingEnabled() {
    return this.getExpStatus('rx_onboarding_v2');
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

  get isEmandateNonzeroAmountEnabled() {
    return this.getExpStatus('emandate_nonzero_amount');
  }

  get isUPISubscriptionEnabled() {
    return this.getExpStatus('upi_subscription');
  }

  get isPaymentButtonEnabledByRazorX() {
    return this.getExpStatus('enable_payment_buttons');
  }

  get isCriticalRouteExperimentEnabled() {
    return this.getExpStatus('validate_user_2fa_status');
  }

  get isBatchSchedulingOptionsExperimentEnabled() {
    return this.getExpStatus('batch_scheduling_options');
  }

  get isSubscriptionButtonEnabledByRazorX() {
    return this.getExpStatus('enable_subscription_buttons');
  }

  get isInstantBatchRefundsEnabled() {
    return this.getExpStatus('batch_service_refund_migration');
  }

  get isSellerAppRole() {
    const userRole = this.userRole;
    return [rolesList.SELLERAPP, rolesList.SELLERAPP_PLUS].indexOf(userRole) > -1;
  }

  get isSupportCallEnabled() {
    return this.getExpStatus('support_call') && this.isActivated;
  }

  get isRouteCodeSupportEnabled() {
    return this.isFeatureEnabled('route_code_support');
  }

  get isUPICAWEnabled() {
    return this.getExpStatus('upi_caw');
  }

  // TODO: Remove from razorX bcoz it's rolled out 100%
  get isPaymentPageReceiptsEnabled() {
    return true;
    return this.getExpStatus('enable_payment_page_receipt');
  }

  // This is for new payment links microservice.
  // If enabled, then all the apis before sending data, and after fetching/receiving data must transform its data, as FE operate on old structure until 100% rollout.
  get isPaymentlinksV2Enabled() {
    return this.isFeatureEnabled('paymentlinks_v2');
  }

  get isPaymentlinksV2CompatEnabled() {
    return this.isFeatureEnabled('paymentlinks_v2_compat');
  }

  get isLoansEnabled() {
    return this.isFeatureEnabled('loan');
  }

  get isWithdrawEnabled() {
    return this.isFeatureEnabled('withdraw_loc');
  }

  get isFlashCreditStage1Enabled() {
    return this.isFeatureEnabled('loc_stage_1');
  }

  get isFlashCreditStage2Enabled() {
    return this.isFeatureEnabled('loc_stage_2');
  }

  get isUnregisteredBusiness() {
    const userBusinessType = Number(this.business_type);
    const UNREGISTERED_BUSINESS_TYPES = [2, 11];
    return UNREGISTERED_BUSINESS_TYPES.indexOf(userBusinessType) !== -1;
  }

  get isCommissionInvoicesEnabled() {
    return this.isFeatureEnabled('generate_partner_invoice');
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
}

function _isAllowed(userRole, moduleName, permissionsMap) {
  if (!moduleName) {
    return;
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
