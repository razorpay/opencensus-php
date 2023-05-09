import User, { isOrgFeatureExist } from 'merchant/models/User';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { AdditionalContextInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';

export const isConfigurationViewAllowed = (user: User): boolean =>
  user.isAllowedView('configuration');

export const isProfileViewAllowed = (user: User): boolean => user.isAllowedView('profile');

export const isFlashCheckoutAllowed = (user: User): boolean =>
  user.isOrgAllowedFunctionality('flashcheckout') &&
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.FlashCheckout);

export const isSkipMandatorySummaryPageAllowed = (user: User): boolean =>
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.MandateSummary);

export const isSmsNotificationEnabled = (user: User): boolean => !!user.contact_mobile;

export const isEmailNotificationEnabled = (user: User): boolean =>
  [rolesList.OWNER, rolesList.ADMIN].includes(user.role);

export const isWhatsappNotificationEnabled = (user: User): boolean =>
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.WhatsappNotification) &&
  user.isWhatsappNotificationEnabled() &&
  user.user &&
  !!user.user.contact_mobile &&
  user.activation_status === 'activated' &&
  (user.role === rolesList.OWNER || user.role === rolesList.ADMIN);

export const isTrustedBadgeAllowed = (user: User): boolean =>
  user.isAllowedView('trustedbadge') &&
  !user.isOrgAxis &&
  !isOrgFeatureExist('hide_razorpay_text_link') &&
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.TrustedBadge);

export const isPaymentMethodEnabled = (user: User, mode: string): boolean =>
  ((user.isOrgRZP === true && user.isInstrumentRequestAllowed()) ||
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
}: Pick<AdditionalContextInterface, 'user' | 'websiteSectionDetailsData'>): boolean =>
  user.isWebsiteComplianceFlowEnabled &&
  websiteSectionDetailsData.data.isWebsiteSectionsApplicable &&
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.WebsiteAppDetails);

export const isGstDetailsEnabled = (user: User): boolean =>
  user.isAllowedView('profile_gst') &&
  !user.isUnregisteredBusiness &&
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Gst);

export const isAccountDetailsEnabled = (user: User): boolean => user.isActivated;

export const isTeamManagementAllowed = (user: User): boolean => user.isAllowedTeamManagement;

export const isSupportTicketEnabled = (user: User): boolean =>
  user.isAdminOrOwner &&
  user.isFdTicketsEnabled &&
  !user.isComdelApiEnabled &&
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.SupportHistory);

export const isBalancesEnabled = (user: User): boolean =>
  user.isAllowedView('add_funds') && !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Balances);

export const isCreditsEnabled = (user: User): boolean =>
  user.isAllowedView('credits') && !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Credits);

export const isReminderEnabled = (user: User): boolean =>
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Reminders);

export const isPaymentCaptureAndRefundEnabled = (user: User): boolean =>
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentCapture) ||
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds);

export const isFailedPaymentRetryEnabled = (user: User): boolean =>
  user.isFeatureEnabled('missed_orders_plink');

export const isBankAccountDetailsAllowed = (user: User): boolean =>
  !isOrgFeatureExist('hide_settlement_details') &&
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.BankAccount);

export const isSettlementsAllowed = (user: User): boolean =>
  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Settlements);

export const shouldShowFIRCSection = (user: User): boolean => {
  /**
   * Show FIRC section either when user is international enabled or when opgsp_import_flow feature flag is enabled.
   */
  if (user.international) {
    return true;
  }
  return user.findTag('opgsp_import_flow');
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
