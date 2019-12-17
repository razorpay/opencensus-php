import ajax from 'merchant/utils/ajax';
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
        .then(response => {
          // Risky. fetchFeaturesAjax can make the request always in 'test'mode.
          // But hopefully, it will happen after cycle of App.js fetch User where it updatesSession with correct mode
          fetchFeaturesAjax(response.data.current)
            .catch(_ => _)
            .then(data => {
              let newUser = new User(response.data);
              newUser.features = setFeatures(
                data.success ? data.data.features : []
              );
              response.data = newUser;
              resolve(response);
            })
            .catch(err => {
              reject(err);
            });
        })
        .catch(err => reject(err));
    });

    return promise;
  }

  get userRole() {
    if (this.current && Object.keys(this.merchants).length) {
      return this.merchants[this.current].role;
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
      const isFeatureAllowed =
        restrictedFeaturesForOrg.indexOf(featureName) === -1;

      return isFeatureAllowed;
    }

    return true; // By default it's allowed if not restricted
  }

  isAllowedEdit(moduleName) {
    let isEditAllowed = _isAllowed(
      this.userRole,
      moduleName,
      roleEditPermissions
    );

    if (this.isEditRestrictedByRazorX(moduleName)) {
      isEditAllowed = false;
    }

    return isEditAllowed;
  }

  isAllowedView(moduleName) {
    let isViewAllowed = _isAllowed(
      this.userRole,
      moduleName,
      roleViewPermissions
    );

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

  get isActivated() {
    return !!parseInt(this.activated);
  }

  get instantActivation() {
    return {
      activation_flow: this.activation_flow,
      business_type: this.business_type,
      activated: this.activated,
      isUnregisteredBusiness: this.isUnregisteredBusiness,

      get isWhitelistFlow() {
        return this.activation_flow === 'whitelist';
      },

      get isBlacklistFlow() {
        return this.activation_flow === 'blacklist';
      },

      get isGraylistFlow() {
        return this.activation_flow === 'greylist';
      },

      get isUnregBizActivated() {
        return this.activated === 1;
      },

      get isL1Submitted() {
        return (
          (!this.isUnregisteredBusiness && !!this.activation_flow) ||
          this.isUnregBizActivated
        );
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

  get currentMerchant() {
    return this.merchants[this.current];
  }

  get isGSTDisabled() {
    return this.findTag('Gst_Invoice_Disabled');
  }

  get isRefundsDisabled() {
    return this.isFeatureEnabled('disable_refunds');
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

  get enabledFeatures() {
    let pluckKey = 'feature';

    return (this.features || []).map(object => {
      return object[pluckKey];
    });
  }

  get showInstantActivation() {
    return (
      this.isOrgRZP && (!!this.activation_flow || this.instant_activations)
    );
  }

  get isMinimumFirstPaymentEnabled() {
    return this.isFeatureEnabled('pl_first_min_amount');
  }

  /* Check case-insensitive tag check existence */
  findTag(tag) {
    return this.tags.some(t => t.toLowerCase() === tag.toLowerCase());
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
      this.partner_type === null &&
      this.partner_intent &&
      this.merchant_partner_intent === false
    );
  }
  get isHavingPartnerConfigs() {
    const currentMerchant = (this.merchants || {})[this.current];
    return (
      !!currentMerchant.partner_type &&
      (currentMerchant.partner || {}).has_commission_configs
    );
  }

  get isHavingSubventionConfigs() {
    const currentMerchant = (this.merchants || {})[this.current];
    return (
      !!currentMerchant.partner_type &&
      (currentMerchant.partner || {}).has_subvention_configs
    );
  }

  getExpStatus(name) {
    return ((this.experiments || {})[name] || {}).result === 'on';
  }

  get isOndemandSettlementEnabled() {
    return this.isFeatureEnabled('ES_ON_DEMAND');
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
    return (
      !!this.international && this.getExpStatus('international_currencies')
    );
  }

  get isRemindersEnabled() {
    return this.getExpStatus('reminders');
  }

  get getPaymentLinkCustomizedFormFields() {
    return window.pl_customized_form_fields;
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

  // Payment pages multiple line items
  get isPPMLIEnabled() {
    return this.getExpStatus('paymentpages_mli');
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

  get isUnregBizFlowEnabled() {
    // return true;
    return this.getExpStatus('non_registered_onboarding');
  }

  get isFirstAmountHidden() {
    return this.getExpStatus('hide_registration_link_first_amount');
  }

  get paymentLinkCreationFormExtraFields() {
    return window.pl_extra_fields;
  }

  get isAllowedTeamManagement() {
    return this.isMerchantRestricted
      ? this.isAllowedView('team')
      : this.isAllowedEdit('team');
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

  get isVPAFeatureEnabled() {
    return this.getExpStatus('vpa_enabled');
  }

  get isNewPPSuccessModalEnabled() {
    return this.getExpStatus('new_pp_success_modal');
  }

  get isSellerAppRole() {
    const userRole = this.userRole;
    return (
      [rolesList.SELLERAPP, rolesList.SELLERAPP_PLUS].indexOf(userRole) > -1
    );
  }

  get isSupportCallEnabled() {
    return this.getExpStatus('support_call');
  }

  get isUnregisteredBusiness() {
    const userBusinessType = Number(this.business_type);
    const UNREGISTERED_BUSINESS_TYPES = [2, 11];
    return UNREGISTERED_BUSINESS_TYPES.indexOf(userBusinessType) !== -1;
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
