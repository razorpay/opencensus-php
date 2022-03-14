import { FLASH_CHECKOUT, SKIP_CARD_MANDATE_SUMMARY } from './deeplink-constants';

export const skipCardMandateSummaryProps = {
  title: 'Skip Mandate Summary Page for Cards',
  desc: 'Skip showing mandate summary page for credit and debit card payments to your users.',
  hashedWith: SKIP_CARD_MANDATE_SUMMARY,
  featureAPIKey: 'card_mandate_skip_page',
  featureName: 'Skip Card Mandate Summary',
};

export const flashCheckoutProps = {
  title: 'Flash Checkout',
  desc: "Securely save the card details of your customers, with Razorpay's Flash Checkout.",
  hashedWith: FLASH_CHECKOUT,
  featureAPIKey: 'noflashcheckout',
  isFeatureAPIKeyReversed: true, // This is to indicate that the feature api key is actually reverse when compared to UI description
  featureName: 'Flash Checkout',
};
