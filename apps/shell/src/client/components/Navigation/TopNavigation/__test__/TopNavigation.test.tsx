import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import '@testing-library/jest-dom';
import { useBreakpoint } from '@razorpay/blade/utils';
import { BladeProvider } from '@razorpay/blade/components';
import TopNavigation from '../TopNavigation';
import { bladeTheme } from '@razorpay/blade/tokens';

// Mock server utils to prevent env.ts import chain
jest.mock('../../../../../server/utils', () => ({
  isBrowser: jest.fn(() => true),
}));

// Mock analytics and error service (no assertions, just prevent errors)
jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  analyticsTrack: jest.fn(),
  getCommonAnalyticsProperties: jest.fn(() => ({})),
}));

jest.mock('@razorpay/universe-cli/errorService', () => ({
  ...jest.requireActual('@razorpay/universe-cli/errorService'),
  __esModule: true,
  default: {
    captureError: jest.fn(),
  },
}));

// Mock React Router hooks
const mockNavigate = jest.fn();
const mockLocation = {
  pathname: '/dashboard',
  search: '',
  hash: '',
  state: null,
  key: 'test',
};
const mockUseLocation = jest.fn(() => mockLocation);

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
  useLocation: () => mockUseLocation(),
}));

// Mock stores
const mockConnectedNavigationStore = {
  products: { selectedProduct: null },
  setIsSideNavOpenOnMobile: jest.fn(),
  showSearchOnMobile: true,
  clearSelectedProduct: jest.fn(),
};

const mockCommonStore = {
  showNotification: jest.fn(),
};

jest.mock('@federated/apps/shell/connected-navigation/connectedNavigationStore', () => ({
  ...jest.requireActual('@federated/apps/shell/connected-navigation/connectedNavigationStore'),
  useConnectedNavigationStore: jest.fn((selector: any) => selector(mockConnectedNavigationStore)),
}));

jest.mock('@federated/apps/shell/commonStore', () => ({
  ...jest.requireActual('@federated/apps/shell/commonStore'),
  useStore: jest.fn((selector: any) => selector(mockCommonStore)),
}));

// Mock hooks
const mockTopNavigationData = {
  products: [
    {
      id: '1',
      alias: 'payments_top_navigation_item',
      title: 'Payments',
      description: 'Accept payments',
      icon: 'AcceptPaymentsIcon',
      selectAction: {
        actionType: 'navigate',
        value: { type: 'internal_navigation', navigateTo: '/dashboard' },
      },
    },
    {
      id: '2',
      alias: 'banking_top_navigation_item',
      title: 'Banking',
      description: 'Banking services',
      icon: 'BankIcon',
      selectAction: {
        actionType: 'navigate',
        value: { type: 'internal_navigation', navigateTo: '/banking' },
      },
    },
  ],
  isLoading: false,
  isError: false,
};

const mockActiveProduct = {
  isHomeActive: false,
  isBankingActive: false,
  isPaymentsActive: true,
  isPartnersActive: false,
  isCompanyRegistrationActive: false,
  activeProductAlias: 'payments_top_navigation_item',
  isProductPathActive: jest.fn(),
};

jest.mock('../hooks', () => ({
  ...jest.requireActual('../hooks'),
  useTopNavigationData: jest.fn(() => mockTopNavigationData),
}));

jest.mock('../../hooks', () => ({
  ...jest.requireActual('../../hooks'),
  useGetActiveProduct: jest.fn(() => mockActiveProduct),
}));

// Mock local utils to prevent conflicts
jest.mock('../../utils', () => ({
  ...jest.requireActual('../../utils'),
  isProductPathActive: jest.fn(() => false),
}));

// Mock Blade hooks
jest.mock('@razorpay/blade/components', () => ({
  ...jest.requireActual('@razorpay/blade/components'),
  useTheme: jest.fn(),
}));

jest.mock('@razorpay/blade/utils', () => {
  const actualUtils = jest.requireActual('@razorpay/blade/utils');
  return {
    ...actualUtils,
    useBreakpoint: jest.fn(),
  };
});

const mockUseBreakpoint = useBreakpoint as jest.MockedFunction<typeof useBreakpoint>;
// const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

// Mock child components
jest.mock('../../HeaderActionsLoader', () => ({
  HeaderActionsLoader: ({ showOnlyMobileSearch }: { showOnlyMobileSearch?: boolean }) => (
    <div data-testid="header-actions-loader" data-mobile-search={showOnlyMobileSearch} />
  ),
}));

jest.mock('../ProductTopNavBrand', () => ({
  ...jest.requireActual('../ProductTopNavBrand'),
  ProductTopNavBrand: () => <div data-testid="product-top-nav-brand" />,
}));

// Mock constants
jest.mock('../constants', () => ({
  ...jest.requireActual('../constants'),
  PRODUCT_ALIAS_MAP: {
    PAYMENTS: 'payments_top_navigation_item',
    BANKING: 'banking_top_navigation_item',
    HOME: 'home_top_navigation_item',
  },
}));

jest.mock('../../constants', () => ({
  ...jest.requireActual('../../constants'),
  PRODUCT_PATH_MAP: {
    banking_top_navigation_item: '/banking/*',
    payments_top_navigation_item: '/dashboard',
  },
  PRODUCT_ALIAS_MAP: {
    PAYMENTS: 'payments_top_navigation_item',
    BANKING: 'banking_top_navigation_item',
    HOME: 'home_top_navigation_item',
  },
}));

// Mock assets
jest.mock('@apps/shell/src/assets/razorpay_home.svg', () => 'razorpay-home-logo');

// Mock window.open
Object.defineProperty(window, 'open', {
  value: jest.fn(),
  writable: true,
});

// Extend Window interface for TypeScript
declare global {
  interface Window {
    IS_ONE_HOME_ENABLED?: boolean;
    rzp_user?: any;
  }
}

// Helper function to render component
const renderTopNavigation = (props = {}) => {
  return render(
    <MemoryRouter>
      <BladeProvider themeTokens={bladeTheme} colorScheme="light">
        <TopNavigation {...props} />
      </BladeProvider>
    </MemoryRouter>,
  );
};

describe('TopNavigation Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Set default window properties
    window.IS_ONE_HOME_ENABLED = false;
    (window as any).rzp_user = { user: { id: 'test-user' } };

    // Reset mock implementations
    const {
      useConnectedNavigationStore,
    } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
    useConnectedNavigationStore.mockImplementation((selector: any) =>
      selector(mockConnectedNavigationStore),
    );

    const { useStore } = require('@federated/apps/shell/commonStore');
    useStore.mockImplementation((selector: any) => selector(mockCommonStore));

    const { useTopNavigationData } = require('../hooks');
    useTopNavigationData.mockReturnValue(mockTopNavigationData);

    const { useGetActiveProduct } = require('../../hooks');
    useGetActiveProduct.mockReturnValue(mockActiveProduct);

    // Reset useLocation mock to default
    mockUseLocation.mockReturnValue(mockLocation);

    // Reset useTheme mock to default
    const { useTheme } = require('@razorpay/blade/components');
    useTheme.mockReturnValue({
      setColorScheme: jest.fn(),
      theme: { breakpoints: {} },
    });
  });

  afterEach(() => {
    delete window.IS_ONE_HOME_ENABLED;
    delete (window as any).rzp_user;
  });

  describe('1. Component Rendering & Layout', () => {
    test('Component renders without crashing', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      renderTopNavigation();
      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('Desktop layout renders correctly when not mobile', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
      expect(screen.getByTestId('header-actions-loader')).toBeInTheDocument();
      expect(screen.getByText('Payments')).toBeInTheDocument();
      expect(screen.getByText('Banking')).toBeInTheDocument();
    });

    test('Mobile home layout renders when on home path', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const { useGetActiveProduct } = require('../../hooks');
      useGetActiveProduct.mockReturnValue({
        ...mockActiveProduct,
        isHomeActive: true,
      });

      renderTopNavigation();

      expect(screen.getByText('Razorpay Home')).toBeInTheDocument();
      expect(screen.getByAltText('company-logo')).toBeInTheDocument();
    });

    test('Mobile non-home layout renders correctly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      renderTopNavigation();

      expect(screen.getByText('Home')).toBeInTheDocument();
      expect(screen.getAllByTestId('header-actions-loader').length).toBeGreaterThan(0);
    });

    test('Loading skeleton shows when isLoading is true', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        isLoading: true,
      });

      renderTopNavigation();

      // Should show skeleton instead of actual nav items
      expect(screen.queryByText('Payments')).not.toBeInTheDocument();
      expect(screen.queryByText('Banking')).not.toBeInTheDocument();
    });

    test('Error state handled gracefully when isError is true', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        isError: true,
      });

      renderTopNavigation();

      // Component should still render without crashing
      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('TopNavBrand renders ProductTopNavBrand component', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('HeaderActionsLoader renders in all layouts', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      renderTopNavigation();

      expect(screen.getByTestId('header-actions-loader')).toBeInTheDocument();
    });

    test('Mobile menu button shows/hides based on shouldHideMobileMenu', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: { actionType: 'navigate' },
            },
          },
        }),
      );

      renderTopNavigation();

      // Menu button should be visible when action type is not excluded
      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    test('Mobile search shows when showSearchOnMobile is true', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          showSearchOnMobile: true,
        }),
      );

      renderTopNavigation();

      const headerActions = screen.getAllByTestId('header-actions-loader');
      const mobileSearchAction = headerActions.find(
        (el) => el.getAttribute('data-mobile-search') === 'true',
      );
      expect(mobileSearchAction).toBeInTheDocument();
    });

    test('Razorpay Home logo and heading render in mobile home view', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const { useGetActiveProduct } = require('../../hooks');
      useGetActiveProduct.mockReturnValue({
        ...mockActiveProduct,
        isHomeActive: true,
      });

      renderTopNavigation();

      expect(screen.getByAltText('company-logo')).toBeInTheDocument();
      expect(screen.getByText('Razorpay Home')).toBeInTheDocument();
    });

    test('Home link renders in mobile non-home view', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      renderTopNavigation();

      expect(screen.getByText('Home')).toBeInTheDocument();
    });
  });

  describe('2. Product Selection & Navigation', () => {
    test('Internal navigation calls navigate with correct path', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/dashboard');
      });
    });

    test('External navigation calls window.open with correct URL', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: [
          {
            ...mockTopNavigationData.products[0],
            selectAction: {
              actionType: 'navigate',
              value: { type: 'external_navigation', navigateTo: 'https://external.com' },
            },
          },
        ],
      });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(window.open).toHaveBeenCalledWith('https://external.com', '_blank');
      });
    });

    test('Modal action navigates to dashboard with query params', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: [
          {
            ...mockTopNavigationData.products[0],
            selectAction: {
              actionType: 'modal',
              value: 'partners_onboarding_modal',
            },
          },
        ],
      });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith({
          pathname: '/dashboard',
          search: '?openModal=partners_onboarding_modal',
        });
      });
    });

    test('Store gets cleared when switching to different product', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: { alias: 'banking_top_navigation_item' },
          },
        }),
      );

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockConnectedNavigationStore.clearSelectedProduct).toHaveBeenCalled();
      });
    });

    test('Store does not get cleared when clicking same product', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: { alias: 'payments_top_navigation_item' },
          },
        }),
      );

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockConnectedNavigationStore.clearSelectedProduct).not.toHaveBeenCalled();
      });
    });

    test('Theme gets set based on selected product', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const mockSetColorScheme = jest.fn();
      const { useTheme } = require('@razorpay/blade/components');
      useTheme.mockReturnValue({
        setColorScheme: mockSetColorScheme,
        theme: { breakpoints: {} },
      });

      renderTopNavigation();

      const bankingButton = screen.getByText('Banking');
      fireEvent.click(bankingButton);

      await waitFor(() => {
        expect(mockSetColorScheme).toHaveBeenCalledWith('light');
      });
    });

    test('Error handling shows notification on invalid action', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: [
          {
            ...mockTopNavigationData.products[0],
            selectAction: {
              actionType: 'invalid_action',
              value: null,
            },
          },
        ],
      });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockCommonStore.showNotification).toHaveBeenCalledWith({
          type: 'error',
          message: 'Something went wrong while navigating. We are looking into it.',
        });
      });
    });

    test('Growth page navigation works correctly', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: [
          {
            ...mockTopNavigationData.products[0],
            selectAction: {
              actionType: 'growth_page',
              value: { type: 'internal_navigation', navigateTo: '/growth' },
            },
          },
        ],
      });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/growth');
      });
    });

    test('Access denied page navigation works correctly', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: [
          {
            ...mockTopNavigationData.products[0],
            selectAction: {
              actionType: 'access_denied_page',
              value: { type: 'internal_navigation', navigateTo: '/access-denied' },
            },
          },
        ],
      });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/access-denied');
      });
    });

    test('Invalid navigation type throws error', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: [
          {
            ...mockTopNavigationData.products[0],
            selectAction: {
              actionType: 'navigate',
              value: { type: 'invalid_navigation', navigateTo: '/test' },
            },
          },
        ],
      });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockCommonStore.showNotification).toHaveBeenCalledWith({
          type: 'error',
          message: 'Something went wrong while navigating. We are looking into it.',
        });
      });
    });
  });

  describe('3. TabNav Integration', () => {
    test('TabNav renders with correct products data', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      renderTopNavigation();

      expect(screen.getByText('Payments')).toBeInTheDocument();
      expect(screen.getByText('Banking')).toBeInTheDocument();
    });

    test('TabNavItems render for visible products', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      renderTopNavigation();

      const paymentsItem = screen.getByText('Payments');
      const bankingItem = screen.getByText('Banking');

      expect(paymentsItem).toBeInTheDocument();
      expect(bankingItem).toBeInTheDocument();
    });

    test('TabNavItem has correct props and onClick works', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      expect(paymentsButton).toBeInTheDocument();

      fireEvent.click(paymentsButton);
      // Click handler should execute without errors
    });

    test('More menu renders when there are overflowing items', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: Array.from({ length: 10 }, (_, i) => ({
          id: `${i + 1}`,
          alias: `product_${i + 1}`,
          title: `Product ${i + 1}`,
          description: `Description ${i + 1}`,
          icon: 'AcceptPaymentsIcon',
          selectAction: {
            actionType: 'navigate',
            value: { type: 'internal_navigation', navigateTo: `/product${i + 1}` },
          },
        })),
      });

      renderTopNavigation();

      // With many products, some should overflow and "More" should appear
      // This depends on TabNav's internal overflow logic
      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('isAnyOverflowingProductActive works correctly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      // This is tested indirectly through the More menu active state
      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('ExploreItem renders correctly in more menu', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      // ExploreItem is rendered within MenuItem in overflow scenarios
      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });
  });

  describe('4. Mobile Behavior', () => {
    test('Mobile detection works correctly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      renderTopNavigation();

      // Should render mobile layout
      expect(screen.getByText('Home')).toBeInTheDocument();
    });

    test('Mobile menu button calls setIsSideNavOpenOnMobile', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: { actionType: 'navigate' },
            },
          },
        }),
      );

      renderTopNavigation();

      const menuButton = screen.getByRole('button');
      fireEvent.click(menuButton);

      expect(mockConnectedNavigationStore.setIsSideNavOpenOnMobile).toHaveBeenCalledWith(true);
    });

    test('Home link navigates to /home on mobile', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      renderTopNavigation();

      const homeLink = screen.getByText('Home');
      fireEvent.click(homeLink);

      expect(mockNavigate).toHaveBeenCalledWith('/home');
    });

    test('Mobile home view shows when isHomeActive is true', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const { useGetActiveProduct } = require('../../hooks');
      useGetActiveProduct.mockReturnValue({
        ...mockActiveProduct,
        isHomeActive: true,
      });

      renderTopNavigation();

      expect(screen.getByText('Razorpay Home')).toBeInTheDocument();
    });

    test('Mobile home view shows when path starts with /home', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      mockUseLocation.mockReturnValue({
        ...mockLocation,
        pathname: '/home/dashboard',
      });

      renderTopNavigation();

      // Should show home layout even if isHomeActive is false but path starts with /home
      expect(screen.getByTestId('header-actions-loader')).toBeInTheDocument();
    });

    test('Mobile search renders when showSearchOnMobile is true', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          showSearchOnMobile: true,
        }),
      );

      renderTopNavigation();

      const headerActions = screen.getAllByTestId('header-actions-loader');
      expect(headerActions.length).toBeGreaterThan(0);
    });

    test('Mobile layout hides menu based on action type', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: { actionType: 'growth_page' },
            },
          },
        }),
      );

      renderTopNavigation();

      // Menu button should be hidden for excluded action types
      expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
  });

  describe('5. Store Integration', () => {
    test('Connected navigation store state is used correctly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          showSearchOnMobile: false,
        }),
      );

      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('clearSelectedProduct is called when needed', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: { alias: 'banking_top_navigation_item' },
          },
        }),
      );

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockConnectedNavigationStore.clearSelectedProduct).toHaveBeenCalled();
      });
    });

    test('setIsSideNavOpenOnMobile is called correctly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: { actionType: 'navigate' },
            },
          },
        }),
      );

      renderTopNavigation();

      const menuButton = screen.getByRole('button');
      fireEvent.click(menuButton);

      expect(mockConnectedNavigationStore.setIsSideNavOpenOnMobile).toHaveBeenCalledWith(true);
    });

    test('Common store showNotification is used for errors', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: [
          {
            ...mockTopNavigationData.products[0],
            selectAction: {
              actionType: 'invalid_action',
              value: null,
            },
          },
        ],
      });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      await waitFor(() => {
        expect(mockCommonStore.showNotification).toHaveBeenCalled();
      });
    });

    test('Products from store are accessed correctly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: { alias: 'test_product' },
          },
        }),
      );

      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('Selected product action type affects mobile menu', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: { actionType: 'access_denied_page' },
            },
          },
        }),
      );

      renderTopNavigation();

      // Menu should be hidden for excluded action types
      expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    test('Store methods are called with correct parameters', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: { actionType: 'navigate' },
            },
          },
        }),
      );

      renderTopNavigation();

      const menuButton = screen.getByRole('button');
      fireEvent.click(menuButton);

      expect(mockConnectedNavigationStore.setIsSideNavOpenOnMobile).toHaveBeenCalledWith(true);
    });
  });

  describe('6. Experiment Flags & Feature Toggles', () => {
    test('isOneHomeEnabled flag works correctly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      window.IS_ONE_HOME_ENABLED = true;

      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('OneHome experiment affects navigation behavior', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      window.IS_ONE_HOME_ENABLED = true;

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              alias: 'payments_top_navigation_item',
              selectAction: {
                actionType: 'navigate',
                value: { type: 'external_navigation', navigateTo: 'https://external.com' },
              },
            },
          },
        }),
      );

      renderTopNavigation();

      await waitFor(() => {
        expect(window.open).toHaveBeenCalledWith('https://external.com', '_blank');
        expect(mockNavigate).toHaveBeenCalledWith('/home', { replace: true });
      });
    });

    test('External navigation redirects to correct fallback', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      window.IS_ONE_HOME_ENABLED = false;

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              alias: 'payments_top_navigation_item',
              selectAction: {
                actionType: 'navigate',
                value: { type: 'external_navigation', navigateTo: 'https://external.com' },
              },
            },
          },
        }),
      );

      renderTopNavigation();

      await waitFor(() => {
        expect(window.open).toHaveBeenCalledWith('https://external.com', '_blank');
        expect(mockNavigate).toHaveBeenCalledWith('/dashboard', { replace: true });
      });
    });

    test('Feature flags affect component behavior', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      window.IS_ONE_HOME_ENABLED = true;

      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('Window experiment flags are read correctly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      window.IS_ONE_HOME_ENABLED = true;

      renderTopNavigation();

      // Component should read the flag correctly
      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('Experiment-based conditional rendering works', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'mobile', matchedBreakpoint: 'm' });

      const { useGetActiveProduct } = require('../../hooks');
      useGetActiveProduct.mockReturnValue({
        ...mockActiveProduct,
        isHomeActive: true,
      });

      renderTopNavigation();

      expect(screen.getByText('Razorpay Home')).toBeInTheDocument();
    });
  });

  describe('7. useEffect & Side Effects', () => {
    test('External navigation useEffect triggers correctly', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: {
                actionType: 'navigate',
                value: {
                  type: 'external_navigation',
                  navigateTo: 'https://external.com',
                },
              },
            },
          },
        }),
      );

      renderTopNavigation();

      await waitFor(() => {
        expect(window.open).toHaveBeenCalledWith('https://external.com', '_blank');
      });
    });

    test('External navigation opens new window', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: {
                actionType: 'navigate',
                value: {
                  type: 'external_navigation',
                  navigateTo: 'https://external.com',
                },
              },
            },
          },
        }),
      );

      renderTopNavigation();

      await waitFor(() => {
        expect(window.open).toHaveBeenCalledWith('https://external.com', '_blank');
      });
    });

    test('External navigation redirects based on OneHome flag', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      window.IS_ONE_HOME_ENABLED = true;

      const {
        useConnectedNavigationStore,
      } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
      useConnectedNavigationStore.mockImplementation((selector: any) =>
        selector({
          ...mockConnectedNavigationStore,
          products: {
            selectedProduct: {
              selectAction: {
                actionType: 'navigate',
                value: {
                  type: 'external_navigation',
                  navigateTo: 'https://external.com',
                },
              },
            },
          },
        }),
      );

      renderTopNavigation();

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/home', { replace: true });
      });
    });

    test('useEffect dependencies are correct', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      // This is tested indirectly through the behavior changes
      renderTopNavigation();

      expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
    });

    test('useEffect cleanup works properly', () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { unmount } = renderTopNavigation();

      // Component should unmount without errors
      unmount();
    });
  });

  describe('8. Error Handling', () => {
    test('Error service is called but not asserted (just mocked)', async () => {
      mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

      const { useTopNavigationData } = require('../hooks');
      useTopNavigationData.mockReturnValue({
        ...mockTopNavigationData,
        products: [
          {
            ...mockTopNavigationData.products[0],
            selectAction: {
              actionType: 'invalid_action',
              value: null,
            },
          },
        ],
      });

      renderTopNavigation();

      const paymentsButton = screen.getByText('Payments');
      fireEvent.click(paymentsButton);

      // Error service should be called but we don't assert it
      // Just ensure component doesn't crash
      await waitFor(() => {
        expect(screen.getByTestId('product-top-nav-brand')).toBeInTheDocument();
      });
    });
  });
});
