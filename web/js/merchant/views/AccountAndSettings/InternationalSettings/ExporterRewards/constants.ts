/* eslint-disable i18n-rules/no-hardcoded-i18n-types */

export const EXPORTER_REWARDS_LINKS = {
  KNOW_MORE: 'https://razorpay.com/docs/international/exporter-rewards',
  TERMS: 'https://razorpay.com/docs/international/exporter-rewards/terms-of-use',
  PRIVACY_POLICY: 'https://razorpay.com/docs/international/exporter-rewards/privacy-policy',
};

export const ONBOARDING_CARDS = [
  {
    icon: '/dist/css/assets/product_onboarding/rewards_leading_brands.svg',
    title: 'Increasing rewards',
    desc: 'As you hit the next milestone, you receive exponential bump in the rewards you receive too',
  },
  {
    icon: '/dist/css/assets/product_onboarding/rewards_business_growth.svg',
    title: 'Redeem rewards with ease',
    // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
    desc: 'Most rewards are credits or vouchers which you can easily redeem on Razorpay products',
  },
  {
    icon: '/dist/css/assets/product_onboarding/rewards_free_cost.svg',
    title: 'Tips and suggestions',
    desc: 'Get tips and suggestions around boosting your GMV and hence maximize your rewards',
  },
];

export const REWARD_HISTORY_STALE_TIME = 15 * 60 * 1000; // Data remains fresh for 15 minutes
export const TABLE_PAGE_SIZE = 10;

export const REWARD_HISTORY_STATUS = {
  pending: {
    text: 'Processing',
    color: 'notice',
  },
  processed: {
    text: 'Credited',
    color: 'positive',
  },
  rejected: {
    text: 'Rejected',
    color: 'negative',
  },
};
