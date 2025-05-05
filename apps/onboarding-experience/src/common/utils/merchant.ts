import { LADashboardUser } from '@libs/shared-types/la';
import { PaymentsDashboardUser } from '@libs/shared-types/payments';
import {
  PaymentAcceptanceChannelsType,
  PAYMENT_CHANNEL_OPTIONS,
} from 'apps/onboarding-experience/src/common/types/merchant';

// Checks if any of the specified payment channel options are accepted by the merchant
export const areAnyOptionsAccepted = (
  paymentChannels: PaymentAcceptanceChannelsType,
  options: PAYMENT_CHANNEL_OPTIONS[],
): boolean => {
  return options.some((option) => !!paymentChannels[option]?.accept);
};

// Checks if the merchant has added any website or android or app store URL
export const hasAddedWebsite = (user: LADashboardUser | PaymentsDashboardUser): boolean => {
  return Boolean(
    !!user.business_website ||
      !!user.appstore_url ||
      !!user.playstore_url ||
      !!user.additional_websites?.length,
  );
};

export const PLAY_STORE_APP_URL_PATTERN =
  /^https:\/\/play\.google\.com\/store\/apps\/details\?id=.*/;

export const APP_STORE_APP_URL_PATTERN = /^https:\/\/apps\.apple\.com\/.*/;

export const getSpecificPlatformType = (url?: string): string => {
  if (url && APP_STORE_APP_URL_PATTERN.test(url)) {
    return 'ios';
  } else if (url && PLAY_STORE_APP_URL_PATTERN.test(url)) {
    return 'android';
  } else {
    return 'website';
  }
};
