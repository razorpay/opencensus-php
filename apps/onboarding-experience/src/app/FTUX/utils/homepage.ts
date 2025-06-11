import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import {
  PaymentAcceptanceChannelsType,
  PAYMENT_CHANNEL_OPTIONS,
  MerchantActivationDataType,
} from '@OnboardingExperienceCommons/types/merchant';
import { NO_CODE_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/constants/merchant';
import {
  NO_CODE_PAGE_LAYOUT,
  PG_PAGE_LAYOUT,
  PG_PLUS_NO_CODE_PAGE_LAYOUT,
} from '@FTUX/constants/homepage';
import { WebsiteVerificationUpdateStatus } from '@OnboardingExperienceCommons/types/onboarding';
import { hasAddedWebsite } from '@OnboardingExperienceCommons/utils/merchant';
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

  return [...NO_CODE_PAGE_LAYOUT];
};

/**
 * Determines the appropriate accordion title based on the merchant's payment acceptance channels
 */
export const getAccordionWebsiteTitle = (
  paymentChannels?: PaymentAcceptanceChannelsType,
): string => {
  // Check if merchant intends to accept payments through different channels
  const isWebsiteIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Websites,
  ]);
  const isAppIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.IOS,
    PAYMENT_CHANNEL_OPTIONS.Android,
  ]);

  // Both website and at least one mobile platform
  if (isWebsiteIntent && isAppIntent) {
    return 'Add your website/app details';
  }
  // Only mobile platforms (iOS and/or Android)
  if (isAppIntent) {
    return 'Add your app links';
  }

  // Default case: only website or no specific intent indicated
  return 'Add your website details';
};

/**
 * Maps WebsiteVerificationAutomationStatus to WebsiteUpdateAutomationStatus
 * since they have the same enum values but different types
 */
export const mapToWebsiteUpdateData = (websiteUpdateData?: WebsiteVerificationUpdateStatus) => {
  if (!websiteUpdateData) return undefined;

  return {
    current_status: websiteUpdateData.currentStatus,
    current_status_updated_at: websiteUpdateData.currentStatusUpdatedAt,
    main_page_url: websiteUpdateData.mainPageUrl,
    website_verification_stage: websiteUpdateData.websiteVerificationStage,
    website_verification_page_status: websiteUpdateData.websiteVerificationPageStatus,
  };
};

export const getAccordionCompletedSteps = (merchant?: MerchantActivationDataType): number => {
  const isMerchantActivated = Boolean(merchant?.activation?.isActivated);
  const hasWebsite = hasAddedWebsite(merchant?.business?.paymentAcceptanceChannels);
  const hasApiKeys = Boolean(merchant?.apiKeys?.[0]?.id);
  const hasTransacted = Boolean(merchant?.activation?.isTransacted);

  // Do not evaluate the website section for non-activated merchants
  if (!isMerchantActivated) {
    if (!hasApiKeys) return 0;
    if (!hasTransacted) return 1;
    return 2;
  }

  const apiKeyAccess = merchant?.hasApiKeyAccess;

  if (!hasWebsite || !apiKeyAccess) return 0;
  if (!hasApiKeys) return 1;
  if (!hasTransacted) return 2;
  return 3;
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
 * @returns An object with icon arrays and formatted text to display in the merchant header
 */
export const getMerchantHeaderData = (paymentChannels?: PaymentAcceptanceChannelsType) => {
  // Default header data with empty arrays/values
  const addedPlatforms: string[] = [];
  const headerIcons: string[] = [];

  // Early return if no payment channels provided
  if (!paymentChannels) {
    headerIcons.push(NoCodeHeaderIcon);
    addedPlatforms.push('Ready to use products');
    return {
      icons: headerIcons,
      text: 'Ready to use products',
    };
  }

  // Determine merchant category based on their selected payment channels
  const isNoCodeMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, NO_CODE_CHANNEL_OPTIONS);
  const isWebsiteMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Websites,
  ]);
  const isAppMerchant = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.IOS,
    PAYMENT_CHANNEL_OPTIONS.Android,
  ]);

  // Add icons and platform names based on merchant categories
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

export const getWebsiteAdditionContent = (
  paymentChannels?: PaymentAcceptanceChannelsType,
): {
  title: string;
  initialContent: string | string[];
} => {
  // Check if merchant intends to accept payments through different channels
  const isWebsiteIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Websites,
  ]);
  const isAndroidIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [
    PAYMENT_CHANNEL_OPTIONS.Android,
  ]);
  const isIosIntent = hasAcceptedAnyPaymentChannel(paymentChannels, [PAYMENT_CHANNEL_OPTIONS.IOS]);

  const hasAndroidUrl = hasAddedWebsite(paymentChannels, [PAYMENT_CHANNEL_OPTIONS.Android]);
  const hasIosUrl = hasAddedWebsite(paymentChannels, [PAYMENT_CHANNEL_OPTIONS.IOS]);

  // THis will be defautl in case of any error
  let data = {
    title: 'Website link',
    initialContent: [
      "To accept payments, you'll need a ready website.",
      "If you don't have one yet, you can try a test integration from step 2 or explore ready-to-use payment options below.",
      'Turn on test mode (bottom left of the side navigation)',
    ],
  };

  // Both website and at least one mobile platform
  if (isWebsiteIntent) {
    return data;
  }

  // Check if merchant has both Android and iOS apps but hasn't added links for either one
  if (isAndroidIntent && isIosIntent && !hasAndroidUrl && !hasIosUrl) {
    return {
      title: 'App link',
      initialContent:
        "Add your Play Store / App Store link. If it's not ready, move to step two and set up payments in Test Mode.",
    };
  }
  // Check if merchant has an Android app but hasn't added the Play Store link yet
  if (isAndroidIntent && !hasAndroidUrl) {
    return {
      title: 'Android app',
      initialContent:
        "Add your Play Store app link. If it's not ready, move to step two and set up payments in Test Mode.",
    };
  }
  // Check if merchant has an iOS app but hasn't added the App Store link yet
  if (isIosIntent && !hasIosUrl) {
    return {
      title: 'iOS app',
      initialContent:
        "Add your App Store app link. If it's not ready, move to step two and set up payments in Test Mode.",
    };
  }

  // Default case: For No Code intent merchants who added the website later
  return data;
};

/**
 * Formats a name to have a maximum of 14 characters.
 * If the name is longer than 14 characters, it will try to include
 * as many words as possible within the character limit.
 * Also converts text to title case (first letter of each word capitalized, rest lowercase).
 *
 * @param name - The full name to format
 * @returns Formatted name with maximum 14 characters and in title case
 */
export const formatName = (name: string): string => {
  if (!name || typeof name !== 'string') {
    return '';
  }

  // Trim whitespace and handle multiple spaces
  const trimmedName = name.trim().replace(/\s+/g, ' ');

  if (!trimmedName) {
    return '';
  }

  // Convert to title case (first letter of each word capitalized, rest lowercase)
  const titleCaseName = trimmedName
    .toLowerCase()
    .split(' ')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');

  // If already within limit, return as is
  if (titleCaseName.length <= 14) {
    return titleCaseName;
  }

  // Handle case where first word is already too long
  const words = titleCaseName.split(' ');
  if (words[0].length > 14) {
    return words[0];
  }

  // Try to fit as many words as possible
  let result = words[0];

  for (let i = 1; i < words.length; i++) {
    const potentialResult = `${result} ${words[i]}`;

    if (potentialResult.length <= 14) {
      result = potentialResult;
    } else {
      // No more words can fit
      break;
    }
  }

  return result;
};
