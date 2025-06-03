import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';

/**
 * Payment channels that indicate a merchant is using the Payment Gateway (PG)
 */
export const PG_CHANNEL_OPTIONS = [
  PAYMENT_CHANNEL_OPTIONS.Websites,
  PAYMENT_CHANNEL_OPTIONS.IOS,
  PAYMENT_CHANNEL_OPTIONS.Android,
];

/**
 * Payment channels that indicate a merchant is using no-code payment solutions
 */
export const NO_CODE_CHANNEL_OPTIONS = [
  PAYMENT_CHANNEL_OPTIONS.SocialMedia,
  PAYMENT_CHANNEL_OPTIONS.WhatsappSmsEmail,
  PAYMENT_CHANNEL_OPTIONS.Others,
];
