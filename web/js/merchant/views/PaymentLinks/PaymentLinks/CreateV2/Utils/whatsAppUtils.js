import { getApplicationStatus } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/utils';
import { FEATURE_WHATSAPP_PL } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import { isWhatsAppAccountSetupEnabled } from 'merchant/views/AccountAndSettings/utils/conditionUtils';

export const getWhatsPLNotificationStatus = ({ user, applications, splitz }) => {
  const { connectedAppsloading, tokens = [] } = applications || {};
  const { isActive, businessProvider } = getApplicationStatus({ tokens });
  const isNotificationEnabled = user.isFeatureEnabled(FEATURE_WHATSAPP_PL);
  const isWhatsAppNotificationsEnabled = isActive && isNotificationEnabled;
  const isWhatsAppFeatEnabled = isWhatsAppAccountSetupEnabled(user, splitz);

  return {
    isApplicationsLoading: connectedAppsloading,
    isNotificationShow: isWhatsAppFeatEnabled,
    isWhatsAppNotificationsEnabled,
    title: isWhatsAppNotificationsEnabled
      ? 'Whatsapp notifications are enabled by default'
      : 'Whatsapp notifications are now available',
    CtaText: isWhatsAppNotificationsEnabled ? 'Turn off here' : 'Enable now',
    businessProviderName: businessProvider.title,
  };
};
