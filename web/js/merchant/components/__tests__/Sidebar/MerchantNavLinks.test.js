import MerchantNavLinks from 'merchant/components/Sidebar/MerchantNavLinks';
import User from 'merchant/models/User';
import store from 'merchant/store';
import { render, screen, updateUseI18ServiceSpy } from 'test-utils';

const defaultProps = {
  routes: {
    transactions: 'transactions',
    invoices: 'invoices',
    paymentlinks: 'paymentlinks',
    paymentpages: 'paymentpages',
    stores: 'stores',
    apiKeys: 'apiKeys',
    subscription_buttons: 'subscription_buttons',
    marketplace: 'marketplace',
    subscriptions: 'subscriptions',
    qrCodes: 'qrCodes',
    smartCollect: 'smartCollect',
    magicCheckout: 'magicCheckout',
    bbps: 'bbps',
    paymentMetrics: 'paymentMetrics',
    account: 'account',
    developersWebhooks: 'developersWebhooks',
    settings: 'settings',
  },
  user: { isPaymentButtonEnabledByRazorX: false },
  isChargeAtWillEnabled: false,
};

jest.mock('merchant/components/Sidebar/helpers', () => ({
  usePosOnboardingExperiment: () => ({
    isPosOnboardingEnabled: jest.fn(),
  }),
  getIsBankingEnabled: jest.fn(),
}));

const updateStore = (user, org = {}) => {
  const updatedStore = store.getState();
  updatedStore.session.user = new User({ ...updatedStore.session.user, ...user });
  updatedStore.session.org = { ...updatedStore.session.org, ...org };
  return updatedStore;
};

const renderApp = ({ props, state }) =>
  render(<MerchantNavLinks {...props} />, { initialState: state });

const MERCHANT_NAV_LINKS = [
  {
    label: 'Payment Pages',
    configPath: 'payment_pages.payment_pages',
  },
  {
    label: 'Invoices',
    configPath: 'invoices.invoice',
  },
  {
    label: 'Payment Links',
    configPath: 'payment_links.payment_link',
  },
  {
    label: 'Route',
    configPath: 'route.marketplace',
  },
  {
    label: 'Subscriptions',
    configPath: 'subscription.subscription',
  },
  {
    label: 'QR Codes',
    configPath: 'qr_code.qr_code',
  },
  {
    label: 'Smart Collect',
    configPath: 'smart_collect.virtual_accounts',
  },
  {
    label: 'Customers',
    configPath: 'customers.customer',
  },
  {
    label: 'Offers',
    configPath: 'offers.offers',
  },
  {
    label: 'Checkout Rewards',
    configPath: 'checkout_rewards.checkout_rewards',
  },
];

describe('test for MerchantNavLinks component', () => {
  test.each(MERCHANT_NAV_LINKS)(
    `should hide merchant link for i18n config is enabled`,
    ({ label, configPath }) => {
      const props = { ...defaultProps };
      const updatedState = updateStore(
        {
          isAllowedView: jest.fn(),
          isAllowedMultiple: jest.fn(),
        },
        { features: [] },
      );
      const user = updatedState.session.user;
      updateUseI18ServiceSpy(configPath);

      if (label === 'Payment Button') {
        jest.spyOn(user, 'isSubscriptionButtonEnabled', 'get').mockReturnValue(true);
        updatedState.session.user.isAllowedMultiple.mockImplementation(() => true);
      }
      if (label === 'Stores') {
        jest.spyOn(user, 'isStoresEnabled', 'get').mockReturnValue(true);
      }
      updatedState.session.user.isAllowedView.mockImplementation(() => true);
      renderApp({ props, updatedState });
      const expectedText = `${label}`;
      expect(screen.queryByText(expectedText)).toBe(null);
    },
  );
});
