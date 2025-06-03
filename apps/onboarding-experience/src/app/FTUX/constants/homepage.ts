import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';
import {
  MERCHANT_FEATURE_FLAGS,
  MerchantOnboardingDataRequestEnum,
} from '@OnboardingExperienceCommons/types/onboarding';

/**
 * Feature flags that must be fetched for the FTUX homepage to function correctly
 */
export const FTUX_FEATURE_FLAGS = [
  MERCHANT_FEATURE_FLAGS.SHOW_PG_V3,
  MERCHANT_FEATURE_FLAGS.PG_V3_ONBOARDING_COMPLETE,
];

/**
 * Data requirements that must be fetched during for loading the FTUX homepage
 * This includes feature flags, workflow status, verification status, and plugin information
 * to ensure the FTUX homepage has all necessary data for proper rendering
 */
export const FTUX_REQUIRED_DATA_REQUEST = [
  MerchantOnboardingDataRequestEnum.FEATURE_FLAGS,
  MerchantOnboardingDataRequestEnum.SELF_SERVE_WORKFLOW_STATUS,
  MerchantOnboardingDataRequestEnum.WEBSITE_VERIFICATION_STATUS,
  MerchantOnboardingDataRequestEnum.SELECTED_PLUGINS,
  MerchantOnboardingDataRequestEnum.SUPPORTED_PLUGINS,
];

/**
 * Defines the order and types of UI elements shown to PG merchants
 */
export const PG_PAGE_LAYOUT: HOMEPAGE_ELEMENTS[] = [
  HOMEPAGE_ELEMENTS.ACCORDION,
  HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT,
  HOMEPAGE_ELEMENTS.PAYMENT_HANDLE,
  HOMEPAGE_ELEMENTS.NOCODE_NUDGE,
  HOMEPAGE_ELEMENTS.BROWSE_ALL,
];

/**
 * Defines the order and types of UI elements shown to merchants using no-code solutions
 */
export const NO_CODE_PAGE_LAYOUT: HOMEPAGE_ELEMENTS[] = [
  HOMEPAGE_ELEMENTS.NOCODE_NUDGE,
  HOMEPAGE_ELEMENTS.BROWSE_ALL,
  HOMEPAGE_ELEMENTS.PAYMENT_HANDLE,
  HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT,
  HOMEPAGE_ELEMENTS.WEBSITE_NUDGE,
];

/**
 * Homepage layout configuration for merchants who use both PG and No-Code solutions
 */
export const PG_PLUS_NO_CODE_PAGE_LAYOUT: HOMEPAGE_ELEMENTS[] = [
  HOMEPAGE_ELEMENTS.ACCORDION,
  HOMEPAGE_ELEMENTS.NOCODE_NUDGE,
  HOMEPAGE_ELEMENTS.BROWSE_ALL,
  HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT,
  HOMEPAGE_ELEMENTS.PAYMENT_HANDLE,
];
