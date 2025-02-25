import Mobile from 'merchant/containers/Home/Mobile';
import User from 'merchant/models/User';
import store from 'merchant/store';
import { render, screen } from 'test-utils';

const mockedFn = jest.fn();
jest.mock('common/i18', () => ({
  __esModule: true,
  withI18Service: (Component) => (props) =>
    <Component i18={{ isConfigTagEnabled: mockedFn }} {...props} />,
  useI18Service: () => ({
    isConfigTagEnabled: jest.fn(),
  }),
}));

jest.mock('@federated/apps/shell/commonStore', () => ({
  __esModule: true,
  ...jest.requireActual('@federated/apps/shell/commonStore'),
  useStore: (cb) => cb({ session: { user: {} } }),
}));

const defaultProps = {
  showInstantActivation: false,
  ondemand_restrictions: {
    data: {},
  },
  current_balance: {
    data: {
      balance: 1000,
    },
  },
  onExtraContentMount: jest.fn(),
  startDate: {
    unix: jest.fn,
  },
  endDate: {
    unix: jest.fn,
  },
};

jest.mock('merchant/components/Announcements/SupportRequest', () => ({
  ...jest.requireActual('merchant/components/Announcements/SupportRequest'),
  __esModule: true,
  default: () => {
    return <div>SupportRequest Component</div>;
  },
}));

jest.mock('merchant/components/EasterEgg', () => ({
  ...jest.requireActual('merchant/components/EasterEgg'),
  __esModule: true,
  default: () => {
    return <div>EasterEgg Component</div>;
  },
}));

jest.mock('common/ui/DateRangePicker', () => ({
  ...jest.requireActual('common/ui/DateRangePicker'),
  __esModule: true,
  default: () => {
    return <div>DateRangePicker Component</div>;
  },
}));

jest.mock('merchant/containers/Home/KeyMetrics', () => ({
  __esModule: true,
  default: () => {
    return <div>KeyMetrics Component</div>;
  },
}));

jest.mock('merchant/containers/Home/RecentActivity', () => ({
  __esModule: true,
  default: () => {
    return <div>RecentActivity Component</div>;
  },
}));

jest.mock('merchant/views/TicketSupport/utils', () => ({
  STATUSES: {
    2: 'ACTIVE',
    6: 'AWAITING_YOUR_REPLY',
  },
}));

jest.mock('react-lazyload', () => ({
  __esModule: true,
  default: ({ children }) => children,
}));

jest.spyOn(window, 'close').mockImplementation(jest.fn());

const updateStore = (user, org = {}) => {
  const updatedStore = store.getState();
  updatedStore.session.user = new User({ ...updatedStore.session.user, ...user });
  updatedStore.session.org = { ...updatedStore.session.org, ...org };
  return updatedStore;
};

const renderApp = ({ props, state }) => render(<Mobile {...props} />, { initialState: state });

describe('test for Mobile component', () => {
  test('should hide component if product_recommendations_kyc.product_recommendation_kyc is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore(
      {
        merchant: { country_code: 'MY' },
      },
      { features: [] },
    );
    updatedState.config.ticketsRaisedByAgents = {
      data: [
        {},
        [
          {
            status: 6,
            created_at: 1693133717,
          },
        ],
      ],
    };
    const user = updatedState.session.user;
    jest.spyOn(user, 'isMobileSignupCareActive', 'get').mockReturnValue(true);
    mockedFn.mockImplementation(
      (path) => path === 'product_recommendations_kyc.product_recommendation_kyc',
    );
    renderApp({ props, updatedState });
    expect(screen.queryByText('SupportRequest Component')).not.toBeInTheDocument();
    expect(screen.queryByText('EasterEgg Component')).not.toBeInTheDocument();
  });
});
