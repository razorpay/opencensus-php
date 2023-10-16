import HeaderNav from 'merchant/components/HeaderNav/index';
import { isMobileDevice } from 'merchant/components/Home/data';
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
  isMobile: false,
};

jest.mock('merchant/components/Home/data', () => ({ isMobileDevice: jest.fn() }));

jest.mock('common/ui/GrowthAssetEB', () => ({
  ...jest.requireActual('common/ui/GrowthAssetEB'),
  __esModule: true,
  default: () => {
    return <div>GrowthAssetEB Component</div>;
  },
}));

jest.mock('merchant/components/HeaderNav/StatusDetails/index', () => ({
  ...jest.requireActual('merchant/components/HeaderNav/StatusDetails/index'),
  __esModule: true,
  default: () => {
    return <div>StatusDetails Component</div>;
  },
}));

jest.mock('merchant/components/HeaderNav/AppSwitcher', () => ({
  ...jest.requireActual('merchant/components/HeaderNav/AppSwitcher'),
  __esModule: true,
  default: () => {
    return <div>AppSwitcher Component</div>;
  },
}));

jest.mock('merchant/components/HeaderNav/ProfileDropdown', () => ({
  ...jest.requireActual('merchant/components/HeaderNav/ProfileDropdown'),
  __esModule: true,
  default: () => {
    return <div>ProfileDropdown Component</div>;
  },
}));

window.rzpQ = {
  onbr: () => ({
    success: (name, obj) => {
      return name + obj.menu_title;
    },
  }),
  component: (component) => component,
};

const updateStore = (user, org = {}) => {
  const updatedStore = store.getState();
  updatedStore.session.user = new User({ ...updatedStore.session.user, ...user });
  updatedStore.session.org = { ...updatedStore.session.org, ...org };
  return updatedStore;
};

const renderApp = ({ props, state }) => render(<HeaderNav {...props} />, { initialState: state });

describe('test for HeaderNav component', () => {
  test('should hide component if announcements.announcements is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore({
      merchant: {
        country_code: 'MY',
      },
    });

    isMobileDevice.mockImplementation(() => false);
    mockedFn.mockImplementation((path) => path === 'announcements.announcements');
    const text = 'GrowthAssetEB Component';
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();

    isMobileDevice.mockImplementation(() => true);
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });

  test('should hide component if app_switcher.app_switcher is enabled', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore(
      {
        merchant: {
          country_code: 'MY',
        },
      },
      { features: [] },
    );
    const user = updatedState.session.user;
    jest.spyOn(user, 'isAppSwitcherEnabled', 'get').mockReturnValue(true);
    jest.spyOn(user, 'isAccepted', 'get').mockReturnValue(true);
    jest.spyOn(user, 'isOrgAxis', 'get').mockReturnValue(false);
    jest.spyOn(user, 'isOrgKotak', 'get').mockReturnValue(false);
    mockedFn.mockImplementation((path) => path === 'app_switcher.app_switcher');
    const text = 'AppSwitcher Component';
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });
});
