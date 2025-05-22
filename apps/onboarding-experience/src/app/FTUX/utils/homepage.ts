import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import {
  NO_CODE_PAGE_LAYOUT,
  PG_PAGE_LAYOUT,
  PG_PLUS_NO_CODE_PAGE_LAYOUT,
} from '@FTUX/constants/homepage';
import {
  PaymentAcceptanceChannelsType,
  PAYMENT_CHANNEL_OPTIONS,
} from '@OnboardingExperienceCommons/types/merchant';

interface LayoutOptions {
  isPgMerchant: boolean;
  isNoCodeMerchant: boolean;
  hasWebsite: boolean;
}

/**
 * Determines which layout configuration to use based on merchant type and website status
 *
 * The function selects the appropriate homepage layout based on:
 * 1. If merchant uses both PG and no-code solutions or has a website -> combined layout
 * 2. If merchant only uses PG -> PG-specific layout
 * 3. If merchant only uses no-code solutions -> no-code specific layout
 */
export const getLayoutByMerchantType = ({
  isPgMerchant,
  isNoCodeMerchant,
  hasWebsite,
}: LayoutOptions): HOMEPAGE_ELEMENTS[] => {
  if ((isPgMerchant && isNoCodeMerchant) || hasWebsite) {
    return [...PG_PLUS_NO_CODE_PAGE_LAYOUT];
  }
  if (isPgMerchant) {
    return [...PG_PAGE_LAYOUT];
  }
  if (isNoCodeMerchant) {
    return [...NO_CODE_PAGE_LAYOUT];
  }
  return [];
};

/**
 * Determines the appropriate accordion title based on the merchant's payment acceptance channels
 */
export const getAccordionWebsiteTitle = (
  paymentChannels?: PaymentAcceptanceChannelsType,
): string => {
  // Check if merchant intends to accept payments through different channels
  const isWebsiteIntent = paymentChannels?.[PAYMENT_CHANNEL_OPTIONS.Websites]?.accept;
  const isAndroidIntent = paymentChannels?.[PAYMENT_CHANNEL_OPTIONS.Android]?.accept;
  const isIosIntent = paymentChannels?.[PAYMENT_CHANNEL_OPTIONS.IOS]?.accept;

  // Both website and at least one mobile platform
  if (isWebsiteIntent && (isIosIntent || isAndroidIntent)) {
    return 'Add your website/app details';
  }
  // Only mobile platforms (iOS and/or Android)
  if (isIosIntent || isAndroidIntent) {
    return 'Add your app links';
  }

  // Default case: only website or no specific intent indicated
  return 'Add your website details';
};
