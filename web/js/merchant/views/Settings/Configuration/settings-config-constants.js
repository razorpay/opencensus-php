import { FLASH_CHECKOUT, SKIP_CARD_MANDATE_SUMMARY } from './deeplink-constants';
import { isOrgFeatureExist } from 'merchant/models/User';
const hideRazorpayTextLink = isOrgFeatureExist('hide_razorpay_text_link');
const flashCheckoutDesc = `Securely save the card details of your customers, with ${
  hideRazorpayTextLink ? '' : "Razorpay's"
} Flash Checkout.`;
export const skipCardMandateSummaryProps = {
  title: 'Skip Mandate Summary Page for Cards',
  desc: 'Skip showing mandate summary page for credit and debit card payments to your users.',
  hashedWith: SKIP_CARD_MANDATE_SUMMARY,
  featureAPIKey: 'card_mandate_skip_page',
  featureName: 'Skip Card Mandate Summary',
};

export const flashCheckoutProps = {
  title: 'Flash Checkout',
  desc: flashCheckoutDesc,
  hashedWith: FLASH_CHECKOUT,
  featureAPIKey: 'noflashcheckout',
  isFeatureAPIKeyReversed: true, // This is to indicate that the feature api key is actually reverse when compared to UI description
  featureName: 'Flash Checkout',
};
