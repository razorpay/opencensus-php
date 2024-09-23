import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';
import rolesList from 'merchant/helpers/permissions/roles-list';
import User, { isOrgFeatureExist } from 'merchant/models/User';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import { AdditionalContextInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

export const isConfigurationViewAllowed = (user: User): boolean =>
  user.isAllowedView('configuration');

export const isProfileViewAllowed = (user: User): boolean => user.isAllowedView('profile');

export const isFlashCheckoutAllowed = (user: User, extraConfig: ExtraConfig): boolean =>
  user.isOrgAllowedFunctionality('flashcheckout') &&
  !extraConfig.isConfigTagEnabled('flash_checkout.flash_checkout');

export const isSkipMandatorySummaryPageAllowed = (extraConfig: ExtraConfig): boolean =>
  !extraConfig.isConfigTagEnabled('account.mandate_summary');

export const isCheckoutV2SettingsAllowed = (extraConfig: ExtraConfig): boolean => {
  return isExperimentActive(extraConfig.abExperiments.checkout_editor_v2_preview);
};

export const isSmsNotificationEnabled = (user: User): boolean => !!user.contact_mobile;

export const isEmailNotificationEnabled = (user: User): boolean =>
  [rolesList.OWNER, rolesList.ADMIN].includes(user.role);

export const isWhatsappNotificationEnabled = (user: User, extraConfig: ExtraConfig): boolean =>
  !extraConfig.isConfigTagEnabled('account.whatsapp_notification') &&
  user.isWhatsappNotificationEnabled() &&
  user.user &&
  !!user.user.contact_mobile &&
  user.activation_status === 'activated' &&
  (user.role === rolesList.OWNER || user.role === rolesList.ADMIN);

export const isTrustedBadgeAllowed = (user: User, extraConfig: ExtraConfig): boolean =>
  user.isAllowedView('trustedbadge') &&
  !user.isOrgAxis &&
  !isOrgFeatureExist('hide_razorpay_text_link') &&
  !extraConfig.isConfigTagEnabled('account.trusted_badge');

export const isPaymentMethodEnabled = (user: User, mode: string): boolean =>
  ((user.isOrgRZP === true && user.isCountryIndia && user.isInstrumentRequestAllowed()) ||
    user.isInstrumentRequestHidden) &&
  mode !== 'test';

export const shouldShowFeeBearerSelfServe = ({
  user: { isOrgRZP, isAccepted, role, isFeeBearerSelfServeOn, isPayPalEnabled, international },
  allowCFBInternational,
}: {
  user: User;
  allowCFBInternational: boolean;
}): boolean => {
  const shouldAllow =
    isOrgRZP && isAccepted && role === 'owner' && isFeeBearerSelfServeOn && !isPayPalEnabled;
  return allowCFBInternational ? shouldAllow && international : shouldAllow && !international;
};

export const isApiKeyEnabled = (user: User): boolean => user.isAllowedView('api_keys');

export const isWebhookEnabled = (user: User): boolean => user.isAllowedView('webhooks');

export const isApplicationEnabled = (user: User): boolean => user.isAllowedView('applications');

export const isWebsiteDetailsEnabled = ({
  user,
  websiteSectionDetailsData,
  extraConfig,
}: Pick<
  AdditionalContextInterface,
  'user' | 'websiteSectionDetailsData' | 'extraConfig'
>): boolean =>
  user.isWebsiteComplianceFlowEnabled &&
  websiteSectionDetailsData.data.isWebsiteSectionsApplicable &&
  !extraConfig.isConfigTagEnabled('contact.website_app_details');

export const isGstDetailsEnabled = (user: User, extraConfig: ExtraConfig): boolean =>
  user.isAllowedView('profile_gst') && !extraConfig.isConfigTagEnabled('account.gst');

export const isAccountDetailsEnabled = (user: User): boolean => user.isActivated;

export const isTeamManagementAllowed = (user: User): boolean => user.isAllowedTeamManagement;

export const isSupportTicketEnabled = (user: User, extraConfig: ExtraConfig): boolean =>
  user.isAdminOrOwner &&
  user.isFdTicketsEnabled &&
  !user.isComdelApiEnabled &&
  !extraConfig.isConfigTagEnabled('account.support_history');

export const isBalancesEnabled = (user: User, extraConfig: ExtraConfig): boolean =>
  user.isAllowedView('add_funds') && !extraConfig.isConfigTagEnabled('account.balances');

export const isCreditsEnabled = (user: User, extraConfig: ExtraConfig): boolean =>
  user.isAllowedView('credits') && !extraConfig.isConfigTagEnabled('account.credits');

export const isReminderEnabled = (extraConfig: ExtraConfig): boolean =>
  !extraConfig.isConfigTagEnabled('reminders.reminder');

export const isCustomerSupportDetailsEnabled = (extraConfig: ExtraConfig): boolean =>
  !extraConfig.isConfigTagEnabled('account.customer_support_details');

export const isPaymentCaptureAndRefundEnabled = (extraConfig: ExtraConfig): boolean =>
  !extraConfig.isConfigTagEnabled('account.payment_capture') ||
  !extraConfig.isConfigTagEnabled('refunds.refund');

export const isFailedPaymentRetryEnabled = (user: User): boolean =>
  user.isFeatureEnabled('missed_orders_plink');

export const isBankAccountDetailsAllowed = (extraConfig: ExtraConfig): boolean =>
  !isOrgFeatureExist('hide_settlement_details') &&
  !extraConfig.isConfigTagEnabled('account.bank_account');

export const isSettlementsAllowed = (extraConfig: ExtraConfig): boolean =>
  !extraConfig.isConfigTagEnabled('settlements.settlement');

export const shouldShowFIRCSection = (user: User, extraConfig: ExtraConfig): boolean => {
  if (extraConfig.isConfigTagEnabled('settings.international')) {
    return false;
  }

  /**
   * Show FIRC section either when user is international enabled or when opgsp_import_flow feature flag is enabled.
   */
  if (user.international) {
    return true;
  }

  return user.findTag('opgsp_import_flow');
};

export const isExporterRewardsEnabled = (user: User): boolean => {
  return user.isFeatureEnabled('intl_exporter_rewards');
};

export const exporterRewardsOnboardingStatus = (user: User): boolean => {
  return !user.isFeatureEnabled('intl_exporter_rewards_tnc');
};

export const accountAccessHoverDescription = (user: User): boolean => {
  if (user?.has_key_access) {
    return user?.isOrgCurlec
      ? ATTR_DETAILS.curlec_access_user_account.desc
      : ATTR_DETAILS.access_user_account.desc;
  }
  return user?.isOrgCurlec
    ? ATTR_DETAILS.curlec_restricted_access_user_account.desc
    : ATTR_DETAILS.restricted_access_user_account.desc;
};

export const shouldShowTeamInvitations = (user: User): boolean =>
  user.user?.invitations?.length > 0;

export const isWhatsAppAccountSetupEnabled = (
  user: User,
  { abExperiments }: Pick<SpiltzContextState, 'abExperiments'> = { abExperiments: {} },
  roleCheckEnable?: boolean,
): boolean => {
  if (user.isCountrySingapore) return false;

  if (!abExperiments?.whatsAppPLEnabled) {
    return false;
  }
  if (roleCheckEnable) {
    return isExperimentEnabled(abExperiments.whatsAppPLEnabled) && user.isOrgRZP && user.isOwner;
  }
  return isExperimentEnabled(abExperiments.whatsAppPLEnabled) && user.isOrgRZP;
};
