import store from 'merchant/store';

const merchantId = 'XXXXXXXXXXXXXX';
const templateId = 'TTTTTTTTTTTTTT';
const variant = 'default';

const gsModalMock = {
  id: 'testId',
  header: {
    pillText: 'hello world',
    title: 'hello world',
    icon: {
      alt: 'alt_text',
      src: 'https://cdn.razorpay.com/static/assets/growth-assets/pricing-bundle/growth.svg',
    },
  },
  heroImage: {
    alt: 'alt_text',
    src: 'https://cdn.razorpay.com/static/assets/growth-assets/pricing-bundle/growth.svg',
  },
};

const state = store.getState();

const getState = ({
  current = merchantId,
  isAllowedMultiple = false,
  isAccountAndSettingsRevampEnabled = false,
} = {}): Record<string, unknown> => ({
  ...state,
  session: {
    ...state.session,
    user: {
      ...state.session.user,
      current,
      isAllowedMultiple() {
        return isAllowedMultiple;
      },
      get isAccountAndSettingsRevampEnabled() {
        return isAccountAndSettingsRevampEnabled;
      },
    },
  },
});

const getMonthlyDiscount = (monthlyPrice, annualPrice) => {
  const projectedPrice = monthlyPrice * 12;
  const percentSavings = Math.floor(((projectedPrice - annualPrice) * 100) / projectedPrice);
  return { projectedPrice, percentSavings };
};

export { merchantId, templateId, gsModalMock, variant, getState, getMonthlyDiscount };
