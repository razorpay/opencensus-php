import * as posAgentUtils from 'common/utils/posAgent';
import HeaderNav from 'merchant/components/HeaderNav/index';
import { isMobileDevice } from 'merchant/components/Home/data';
import User from 'merchant/models/User';
import store from 'merchant/store';
import { render, screen, userEvent } from 'test-utils';
import * as rtuxUtils from 'merchant/containers/Home/RTUX/utils';

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
  default: ({ children }) => {
    return (
      <div>
        <div>GrowthAssetEB Component</div>
        {children}
      </div>
    );
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

jest.mock('merchant/components/HeaderNav/SupportRequestDropdown', () => ({
  ...jest.requireActual('merchant/components/HeaderNav/SupportRequestDropdown'),
  __esModule: true,
  default: () => {
    return <div>SupportRequestDropdown Component</div>;
  },
}));

jest.mock('common/ui/OffersForYou', () => ({
  ...jest.requireActual('common/ui/OffersForYou'),
  __esModule: true,
  default: () => {
    return <div>OffersForYou Component</div>;
  },
}));

jest.mock('common/ui/WhatsNew/Icon', () => ({
  ...jest.requireActual('common/ui/OffersForYou'),
  __esModule: true,
  default: () => {
    return <div>Announcement Component</div>;
  },
}));

jest.mock('common/ui/WhatsNew/Old', () => ({
  ...jest.requireActual('common/ui/OffersForYou'),
  __esModule: true,
  default: () => {
    return <div>WhatsNew Component</div>;
  },
}));

jest.mock('merchant/components/HeaderNav/UniversalSearch', () => ({
  ...jest.requireActual('merchant/components/HeaderNav/UniversalSearch'),
  __esModule: true,
  default: () => {
    return <div>Universal Search Component</div>;
  },
}));

jest.mock('merchant/views/EcosystemDowntimes', () => ({
  ...jest.requireActual('merchant/views/EcosystemDowntimes'),
  __esModule: true,
  default: () => {
    return <div>Ecosystem downtimes Component</div>;
  },
}));

jest.mock('@federated/apps/shell/commonStore', () => ({
  __esModule: true,
  ...jest.requireActual('@federated/apps/shell/commonStore'),
  useStore: (cb) => cb({ session: { user: {} } }),
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
const mockWindowReload = jest.fn();

describe('test for HeaderNav component', () => {
  beforeEach(() => {
    mockedFn.mockReset();
    const location = {
      reload: mockWindowReload,
    };
    Object.defineProperty(window, 'location', {
      value: location,
    });
  });

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
    jest.spyOn(user, 'isAccepted', 'get').mockReturnValue(true);
    jest.spyOn(user, 'isOrgAxis', 'get').mockReturnValue(false);
    jest.spyOn(user, 'isOrgKotak', 'get').mockReturnValue(false);
    mockedFn.mockImplementation((path) => path === 'app_switcher.app_switcher');
    const text = 'AppSwitcher Component';
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).not.toBeInTheDocument();
  });

  test('should show GrowthAsset component(exclusive offers)', () => {
    const props = { ...defaultProps };
    const text = 'OffersForYou Component';
    renderApp({ props });
    expect(screen.queryAllByText(text)).toBeDefined();
  });

  test('should show app switcher', () => {
    const props = { ...defaultProps };
    const updatedState = updateStore(
      {
        merchant: {
          country_code: 'IN',
        },
      },
      { features: [] },
    );
    const user = updatedState.session.user;
    jest.spyOn(user, 'isAccepted', 'get').mockReturnValue(true);
    jest.spyOn(user, 'isOrgAxis', 'get').mockReturnValue(false);
    jest.spyOn(user, 'isOrgKotak', 'get').mockReturnValue(false);

    const text = 'AppSwitcher Component';
    renderApp({ props, updatedState });
    expect(screen.queryByText(text)).toBeInTheDocument();
  });

  test('should show support request dropdown', () => {
    const props = { ...defaultProps, showMobileNav: false };
    const updatedState = updateStore(
      {
        merchant: {
          country_code: 'IN',
        },
      },
      { features: [] },
    );
    const user = updatedState.session.user;
    jest.spyOn(user, 'isMobileSignupCareActive', 'get').mockReturnValue(true);

    const text = 'SupportRequestDropdown Component';
    renderApp({ props, updatedState });

    expect(screen.queryByText(text)).toBeInTheDocument();
  });

  describe('homepage - rtux', () => {
    const homepageRtuxProps = {
      isSidebarV2: true,
    };
    test('should not show GrowthAsset component(exclusive offers)', () => {
      jest.spyOn(rtuxUtils, 'isRTUXHomepageEnabled').mockReturnValue(true);
      const props = { ...defaultProps, ...homepageRtuxProps };
      const text = 'OffersForYou Component';
      renderApp({ props });
      expect(screen.queryByText(text)).not.toBeInTheDocument();
    });

    test('should not show app switcher', () => {
      const props = { ...defaultProps, ...homepageRtuxProps };
      const updatedState = updateStore(
        {
          merchant: {
            country_code: 'IN',
          },
        },
        { features: [] },
      );
      const user = updatedState.session.user;
      jest.spyOn(user, 'isAccepted', 'get').mockReturnValue(true);
      jest.spyOn(user, 'isOrgAxis', 'get').mockReturnValue(false);
      jest.spyOn(user, 'isOrgKotak', 'get').mockReturnValue(false);

      const text = 'AppSwitcher Component';
      renderApp({ props, updatedState });
      expect(screen.queryByText(text)).not.toBeInTheDocument();
    });

    test('should not show support request dropdown', () => {
      const props = { ...defaultProps, ...homepageRtuxProps, showMobileNav: false };
      const updatedState = updateStore(
        {
          merchant: {
            country_code: 'IN',
          },
        },
        { features: [] },
      );
      const user = updatedState.session.user;
      jest.spyOn(user, 'isMobileSignupCareActive', 'get').mockReturnValue(true);

      const text = 'SupportRequestDropdown Component';
      renderApp({ props, updatedState });

      expect(screen.queryByText(text)).not.toBeInTheDocument();
    });

    test('should show announcements component', () => {
      const props = { ...defaultProps };
      const updatedState = updateStore(
        {
          merchant: {
            country_code: 'IN',
          },
        },
        { features: [] },
      );

      isMobileDevice.mockImplementation(() => false);
      const user = updatedState.session.user;
      jest.spyOn(user, 'isWhatsNewLazyEnabled', 'get').mockReturnValue(true);

      const text = 'Announcement Component';
      renderApp({ props, updatedState });
      expect(screen.queryByText(text)).toBeInTheDocument();
    });

    test('should show user profile component', () => {
      const props = { ...defaultProps };

      const text = 'ProfileDropdown Component';
      renderApp({ props });
      expect(screen.queryByText(text)).toBeInTheDocument();
    });

    test('should show universal search component', () => {
      const props = { ...defaultProps };
      const updatedState = updateStore(
        {
          merchant: {
            country_code: 'IN',
          },
        },
        { features: [] },
      );
      const user = updatedState.session.user;
      jest.spyOn(user, 'isUniversalSearchEnabled', 'get').mockReturnValue(true);

      const text = 'Universal Search Component';
      renderApp({ props });
      expect(screen.queryByText(text)).toBeInTheDocument();
    });

    test('should show ecosystem downtime component', () => {
      const props = { ...defaultProps };
      const updatedState = updateStore(
        {
          merchant: {
            country_code: 'IN',
          },
        },
        { features: [] },
      );
      const user = updatedState.session.user;
      jest.spyOn(user, 'isOrgRZP', 'get').mockReturnValue(true);
      jest.spyOn(user, 'isEcosystemDowntimeEnabled', 'get').mockReturnValue(true);

      const text = 'Ecosystem downtimes Component';
      renderApp({ props });
      expect(screen.queryByText(text)).toBeInTheDocument();
    });
  });
});
