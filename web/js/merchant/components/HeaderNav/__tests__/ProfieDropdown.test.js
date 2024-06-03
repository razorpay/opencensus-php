import * as posAgentUtils from 'common/utils/posAgent';
import ProfileDropdown from 'merchant/components/HeaderNav/ProfileDropdown';
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

const defaultProps = {
  showMobileNav: true,
  showGSTModal: true,
};

jest.mock('merchant/views/PaymentHandle/components/DropDownSlug', () => ({
  ...jest.requireActual('merchant/views/PaymentHandle/components/DropDownSlug'),
  __esModule: true,
  default: () => {
    return <div>DropDownSlug Component</div>;
  },
}));

jest.mock('merchant/components/HeaderNav/ProfileDropdownV2', () => ({
  __esModule: true,
  default: () => {
    return <div>ProfileDropdownV2 Component</div>;
  },
}));

const updateStore = (user, org = {}) => {
  const updatedStore = store.getState();
  updatedStore.session.user = new User({ ...updatedStore.session.user, ...user });
  updatedStore.session.org = { ...updatedStore.session.org, ...org };
  return updatedStore;
};

const renderApp = ({ props, state }) =>
  render(<ProfileDropdown {...props} />, { initialState: state });

describe('test for ProfileDropdown component', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });
  test('should hide component if profile.razorpay_me is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore(
      {
        user: {
          name: 'test',
        },
        current: 'abcd',
        merchants: {
          abcd: { name: 'abcd', id: '12345' },
        },
      },
      { features: [] },
    );
    const user = updatedState.session.user;
    jest.spyOn(user, 'isPaymentHandleSplitzEnabled', 'get').mockReturnValue(true);
    mockedFn.mockImplementation((path) => path === 'profile.razorpay_me');
    const text = 'DropDownSlug Component';
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });

  test('should hide component if account.gst is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore(
      {
        isAllowedView: jest.fn(),
      },
      { features: [] },
    );

    updatedState.session.user.isAllowedView.mockImplementation(() => true);
    const text = 'GST Details';
    mockedFn.mockImplementation((path) => path === 'account.gst');
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });

  test('should hide component if documentation.documentation is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore(
      {
        isOrgAllowedFunctionality: jest.fn(),
      },
      { features: [] },
    );

    updatedState.session.user.isOrgAllowedFunctionality.mockImplementation(() => true);
    const text = 'Documentation';
    mockedFn.mockImplementation((path) => path === 'documentation.documentation');
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });

  test('should render profile dropdown v2 when isRTUXHomepage', () => {
    const props = { ...defaultProps, isRTUXHomepage: true };

    renderApp({ props });
    expect(screen.queryByText('ProfileDropdownV2 Component')).toBeInTheDocument();
  });

  test('should render sales profile dropdown if it is a sales agent logged in', () => {
    const props = { ...defaultProps };
    jest.spyOn(posAgentUtils, 'checkIfPosSalesAgent').mockReturnValue({
      isEnabled: true,
      isPosSalesAgent: true,
    });
    const updatedState = updateStore({
      user: {
        name: 'test',
      },
      role: 'pos_sales_agent',
    });

    renderApp({ props, updatedState });
    expect(screen.getByTestId('pos-sales-agent-content')).toBeInTheDocument();
    expect(screen.getByText('Logged in as:')).toBeInTheDocument();
    expect(screen.getByText('Logout')).toBeInTheDocument();
  });
});
