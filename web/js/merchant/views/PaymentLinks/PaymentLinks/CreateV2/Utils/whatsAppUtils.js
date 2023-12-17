import {
  FEATURE_WHATSAPP_PL,
  BusinessServiceProvider,
} from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import { isWhatsAppAccountSetupEnabled } from 'merchant/views/AccountAndSettings/utils/conditionUtils';

export const getWhatsPLNotificationStatus = ({ user, splitz }) => {
  const { title } = BusinessServiceProvider[0];
  const isNotificationEnabled = user.isFeatureEnabled(FEATURE_WHATSAPP_PL);
  const isNotificationShow = isWhatsAppAccountSetupEnabled(user, splitz);

  return {
    isNotificationShow,
    isNotificationEnabled,
    title: isNotificationEnabled
      ? 'Whatsapp notifications are enabled by default'
      : 'Whatsapp notifications are now available',
    CtaText: isNotificationEnabled ? 'Turn off here' : 'Enable now',
    businessProviderName: title,
  };
};
