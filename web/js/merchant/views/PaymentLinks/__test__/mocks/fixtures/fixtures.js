import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';

jest.mock('merchant/components/ShowWhen', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/components/ShowWhen'),
  ShowWhenRoute: ({
    // additionalCondition,
    path,
    component: Component,
  }) => {
    // TODO: Use additionalCondition check
    // if (additionalCondition())
    return (
      <div data-testid={path}>
        <Component />
      </div>
    );
    // return null;
  },
  default: ({
    // additionalCondition,
    children,
  }) => {
    // TODO: Use additionalCondition check
    // if (additionalCondition()) {
    return children;
    // }
    // return null;
  },
}));

jest.mock('merchant/components/Announcements/SwitchToPaymentLinksV2', () => () => (
  <div>Switch to Payment Links V2</div>
));

jest.mock('merchant/views/PaymentLinks/OnBoarding', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/views/PaymentLinks/OnBoarding'),
  getIsAllowedResetPaymentLinksOnBoarding: () => false,
  getIsPaymentLinksEnabled: () => true,
  default: () => <div>Payment Links Onboarding Flow</div>,
}));

jest.mock('merchant/views/PaymentLinks/QuickGuide', () => ({
  __esModule: true,
  ...jest.requireActual('merchant/views/PaymentLinks/QuickGuide'),
  getPaymentLinksQuickGuideIsClosed: () => false,
  default: () => <div>Quick guide flow</div>,
}));

jest.mock('merchant/components/Announcements/AnnouncementBanner', () => ({ children }) => (
  <div>{children}</div>
));

jest.mock('merchant/components/Home/data', () => ({
  isMobileDevice: jest.fn().mockReturnValue(true),
}));

const storeData = store.getState();

export const onboarding = {
  products: {
    payment_links: {
      feature: 'payment_links',
      isEnabled: true,
      isQuickGuideOpen: true,
      isTour: false,
      lastElementId: undefined,
      showOnboarding: true,
    },
  },
};

const mockFn = jest.fn((value = true) => value);

export const paymentLinkStoreConfiguration = {
  isAllowedEdit: jest.fn(() => 'activated'),
  missedOrderPLBanner: jest.fn(() => true),
  isAllowedView: jest.fn(() => true),
  isPLBatchUploadEnabled: jest.fn(mockFn),
  isPaymentLinkBatchEnabledForSellerAppRole: jest.fn(() => true),
  isPaymentlinksV2Enabled: true,
  findTag: jest.fn(() => false),
};

export const defaultProps = {
  closeModal: () => {},
  openModal: () => {},
};

export const paymentLinkItems = {
  loading: false,
  paymentlinks: [
    {
      accept_partial: false,
      amount: 100,
      amount_paid: 0,
      cancelled_at: 0,
      created_at: 1669786953,
      currency: 'INR',
      description: 'sasff',
      expire_by: 0,
      expired_at: 0,
      first_min_partial_amount: 0,
      id: 'plink_Km8evXT2XtGXc7',
      notes: [],
      payments: null,
      reference_id: '',
      reminder_enable: false,
      reminders: [],
      short_url: 'https://rzp.io/i/GN3SOBucBc',
      status: 'created',
      updated_at: 1669786953,
      upi_link: false,
      user_id: 'F1fmi7bRy3m3I0',
      type: 'link',
      customer_details: {},
      email_notify: '0',
      sms_notify: '0',
      receipt: '',
      partial_payment: false,
      first_payment_min_amount: 0,
    },
    {
      accept_partial: false,
      amount: 100,
      amount_paid: 0,
      cancelled_at: 0,
      created_at: 1669786929,
      currency: 'INR',
      description: 'sample',
      expire_by: 0,
      expired_at: 0,
      first_min_partial_amount: 0,
      id: 'plink_Km8eVYUsUJDScE',
      notes: [],
      payments: null,
      reference_id: '',
      reminder_enable: false,
      reminders: [],
      short_url: 'https://rzp.io/i/6GMlWyfWl',
      status: 'created',
      updated_at: 1669786929,
      upi_link: false,
      user_id: 'F1fmi7bRy3m3I0',
      type: 'link',
      customer_details: {},
      email_notify: '0',
      sms_notify: '0',
      receipt: '',
      partial_payment: false,
      first_payment_min_amount: 0,
    },
  ],
  count: 2,
  items: [
    {
      accept_partial: false,
      amount: 100,
      amount_paid: 0,
      cancelled_at: 0,
      created_at: 1669786953,
      currency: 'INR',
      description: 'sasff',
      expire_by: 0,
      expired_at: 0,
      first_min_partial_amount: 0,
      id: 'plink_Km8evXT2XtGXc7',
      notes: [],
      payments: null,
      reference_id: '',
      reminder_enable: false,
      reminders: [],
      short_url: 'https://rzp.io/i/GN3SOBucBc',
      status: 'created',
      updated_at: 1669786953,
      upi_link: false,
      user_id: 'F1fmi7bRy3m3I0',
      type: 'link',
      customer_details: {},
      email_notify: '0',
      sms_notify: '0',
      receipt: '',
      partial_payment: false,
      first_payment_min_amount: 0,
    },
    {
      accept_partial: false,
      amount: 100,
      amount_paid: 0,
      cancelled_at: 0,
      created_at: 1669786929,
      currency: 'INR',
      description: 'sample',
      expire_by: 0,
      expired_at: 0,
      first_min_partial_amount: 0,
      id: 'plink_Km8eVYUsUJDScE',
      notes: [],
      payments: null,
      reference_id: '',
      reminder_enable: false,
      reminders: [],
      short_url: 'https://rzp.io/i/6GMlWyfWl',
      status: 'created',
      updated_at: 1669786929,
      upi_link: false,
      user_id: 'F1fmi7bRy3m3I0',
      type: 'link',
      customer_details: {},
      email_notify: '0',
      sms_notify: '0',
      receipt: '',
      partial_payment: false,
      first_payment_min_amount: 0,
    },
  ],
};

export const quickGuideProps = (status) => {
  return {
    paymentLinkStatus: status,
    paymentReceiveStatus: status,
    paymentlinks: paymentLinkItems,
  };
};

export const orgAxisConfiguration = (value) => {
  return {
    paymentLinksProductOnBoarding: {
      feature: 'payment_links',
      isEnabled: true,
      isQuickGuideOpen: false,
      isTour: false,
      lastElementId: undefined,
      showOnboarding: true,
    },
    user: {
      isOrgAxis: value,
    },
  };
};

export const rzpUserConfig = (activationStatus, role) => {
  return {
    activation_status: activationStatus,
    role,
  };
};

export const updateUser = (getStateSpy, user = {}) => {
  getStateSpy.mockImplementation(() => {
    const clonedStore = cloneDeep(storeData);
    clonedStore.session.user = {
      ...clonedStore.session.user,
      ...user,
    };
    return clonedStore;
  });
};
