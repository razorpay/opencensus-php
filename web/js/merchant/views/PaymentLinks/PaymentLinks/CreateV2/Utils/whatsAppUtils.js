import {
  FEATURE_WHATSAPP_PL,
  BusinessServiceProvider,
} from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';
import { isWhatsAppAccountSetupEnabled } from 'merchant/views/AccountAndSettings/utils/conditionUtils';

export const getWhatsPLNotificationStatus = ({ user, splitz, featureStatus = {} }) => {
  const { title } = BusinessServiceProvider[0];
  const { features = {}, loading: isFeatureLoading } = featureStatus;
  const isNotificationShow = isWhatsAppAccountSetupEnabled(user, splitz);
  const isNotificationEnabled = !!features[FEATURE_WHATSAPP_PL];

  return {
    isFeatureLoading,
    isNotificationShow,
    isNotificationEnabled,
    title: isNotificationEnabled
      ? 'Whatsapp notifications are enabled by default'
      : 'Whatsapp notifications are now available',
    CtaText: isNotificationEnabled ? 'Turn off here' : 'Enable now',
    businessProviderName: title,
  };
};
