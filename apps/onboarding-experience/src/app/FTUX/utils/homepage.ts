import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import {
  PaymentAcceptanceChannelsType,
  PAYMENT_CHANNEL_OPTIONS,
} from '@OnboardingExperienceCommons/types/merchant';
import { NO_CODE_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/constants/merchant';
import {
  NO_CODE_PAGE_LAYOUT,
  PG_PAGE_LAYOUT,
  PG_PLUS_NO_CODE_PAGE_LAYOUT,
} from '@FTUX/constants/homepage';
import { hasAcceptedAnyPaymentChannel } from '@OnboardingExperienceCommons/utils/merchant';
import NoCodeHeaderIcon from '@OnboardingExperienceAssets/NoCodeHeaderIcon.svg';
import WebsiteHeaderIcon from '@OnboardingExperienceAssets/WebsiteHeaderIcon.svg';
import AppHeaderIcon from '@OnboardingExperienceAssets/AppHeaderIcon.svg';

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
  if ((isPgMerchant || hasWebsite) && isNoCodeMerchant) {
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

/**
 * Formats an array of platform names into a readable string
 * Example: ['Website', 'Apps', 'Social Media'] becomes "Website, Apps & Social Media"
 * Example: ['Website', 'Apps'] becomes "Website & Apps"
 *
 * @param platforms - Array of platform names to format
 * @returns Formatted string of platforms
 */
export const formatPlatformList = (platforms: string[]): string => {
  if (platforms.length < 2) {
    return platforms.join('');
  } else {
    const platformsCopy = [...platforms];
    const lastOption = platformsCopy.pop();
    return `${platformsCopy.join(', ')} & ${lastOption}`;
  }
};

/**
 * Generates header data for a merchant based on their selected payment channels
 *
 * @param paymentChannels - Object containing merchant's payment acceptance channel preferences
 * @returns An object with icon and text to display in the merchant header
 */
export const getMerchantHeaderData = (paymentChannels?: PaymentAcceptanceChannelsType) => {
  // Default header data with fallback icon and empty text
  const addedPlatforms: string[] = [];
  const headerIcons = [];

  // Determine merchant category based on their selected payment channels
  const isNoCodeMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, NO_CODE_CHANNEL_OPTIONS);
  const isWebsiteMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Websites,
  ]);
  const isAppMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.IOS,
    PAYMENT_CHANNEL_OPTIONS.Android,
  ]);

  // Select the appropriate icon based on merchant category
  if (isWebsiteMerchant) {
    headerIcons.push(WebsiteHeaderIcon);
    addedPlatforms.push('Website');
  }
  if (isAppMerchant) {
    headerIcons.push(AppHeaderIcon);
    addedPlatforms.push('Apps');
  }
  if (isNoCodeMerchant || addedPlatforms.length === 0) {
    headerIcons.push(NoCodeHeaderIcon);
    addedPlatforms.push('Ready to use products');
  }

  return {
    icons: headerIcons,
    text: formatPlatformList(addedPlatforms),
  };
};
