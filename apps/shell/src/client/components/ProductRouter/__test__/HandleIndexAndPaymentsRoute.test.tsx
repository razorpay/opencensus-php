import React, { Suspense } from 'react';
import { screen } from '@testing-library/react';
import '@testing-library/jest-dom'; // Import jest-dom for type support
import HandleIndexAndPaymentsRoute from '../HandleIndexAndPaymentsRoute';
import { render } from '@apps/shell/src/services/test/render';

// Create a mock to track where Navigate would redirect to
const mockNavigate = { to: '', replace: false };

// Create a custom render function that wraps with Suspense
const renderWithSuspense = (ui: React.ReactElement, options?: { route: string }) => {
  return render(<Suspense fallback={<div>Loading...</div>}>{ui}</Suspense>, options);
};

// Original mock implementation for useStore
const originalStoreImplementation = (selector: any) => {
  const mockState = {
    session: {
      user: {
        partner_type: null, // Default to null, can be changed in specific tests
      },
    },
  };
  return selector(mockState);
};

// Mock React's lazy function to immediately return the component
jest.mock('react', () => {
  const originalReact = jest.requireActual('react');
  return {
    ...originalReact,
    lazy: jest.fn().mockImplementation((importFn) => {
      // Return a non-suspending component directly
      const Component = () => <div data-testid="payments-dashboard">Payments Dashboard</div>;
      return Component;
    }),
  };
});

// Mock the React Router Navigate component
jest.mock('react-router-dom', () => {
  const actual = jest.requireActual('react-router-dom');
  return {
    ...actual,
    Navigate: (props: { to: string; replace?: boolean }) => {
      // Store navigation info for assertions
      mockNavigate.to = props.to;
      mockNavigate.replace = !!props.replace;
      return null;
    },
  };
});

// Mock the dependencies
jest.mock('@federated/apps/shell/commonStore', () => {
  const originalModule = jest.requireActual('@federated/apps/shell/commonStore');
  return {
    ...originalModule,
    useStore: jest.fn().mockImplementation(originalStoreImplementation),
  };
});

// Mock the connected navigation store for withNavigationType
jest.mock('@federated/apps/shell/connected-navigation/connectedNavigationStore', () => ({
  useConnectedNavigationStore: jest.fn().mockReturnValue({
    products: {
      selectedProduct: {
        selectAction: {
          actionType: 'random_action_type', // This forces it to fallback to DashboardEntry
        },
      },
    },
  }),
}));

// Since we're mocking React.lazy, we don't need to mock this import
jest.mock('@federated/dashboards/payments/entry', () => ({}));

describe('HandleIndexAndPaymentsRoute', () => {
  beforeEach(() => {
    jest.clearAllMocks();

    // Reset navigation mock and global variables
    mockNavigate.to = '';
    mockNavigate.replace = false;
    window.IS_ONE_HOME_ENABLED = true;

    // Reset useStore mock to default implementation
    const { useStore } = require('@federated/apps/shell/commonStore');
    useStore.mockImplementation(originalStoreImplementation);
  });

  test('redirects to home when path is / and OneHome is enabled', () => {
    renderWithSuspense(<HandleIndexAndPaymentsRoute />, { route: '/' });

    // Verify redirection using our mock
    expect(mockNavigate.to).toBe('/home');
    expect(mockNavigate.replace).toBe(true);
  });

  test('redirects to partners when path is / and user is a partner', () => {
    // Set OneHome as disabled
    window.IS_ONE_HOME_ENABLED = false;

    // Mock useStore to return a partner user
    const { useStore } = require('@federated/apps/shell/commonStore');
    useStore.mockImplementation((selector: any) => {
      const mockState = {
        session: {
          user: {
            partner_type: 'some_partner_type',
          },
        },
      };
      return selector(mockState);
    });

    renderWithSuspense(<HandleIndexAndPaymentsRoute />, { route: '/' });

    // Verify redirection using our mock
    expect(mockNavigate.to).toBe('/partners');
    expect(mockNavigate.replace).toBe(true);
  });

  test('redirects to home when path is / and OneHome is enabled and user is a partner', () => {
    // Set OneHome as enabled (already set in beforeEach)
    window.IS_ONE_HOME_ENABLED = true;

    // Mock useStore to return a partner user
    const { useStore } = require('@federated/apps/shell/commonStore');
    useStore.mockImplementation((selector: any) => {
      const mockState = {
        session: {
          user: {
            partner_type: 'some_partner_type',
          },
        },
      };
      return selector(mockState);
    });

    renderWithSuspense(<HandleIndexAndPaymentsRoute />, { route: '/' });

    // Verify redirection using our mock
    expect(mockNavigate.to).toBe('/home');
    expect(mockNavigate.replace).toBe(true);
  });

  test('redirects to /dashboard when path is / and user is not a partner and OneHome is disabled', () => {
    // Set OneHome as disabled
    window.IS_ONE_HOME_ENABLED = false;

    renderWithSuspense(<HandleIndexAndPaymentsRoute />, { route: '/' });

    // Verify redirection using our mock
    expect(mockNavigate.to).toBe('/dashboard');
    expect(mockNavigate.replace).toBe(true);
  });

  test('renders PaymentsDashboard for any non-root path', () => {
    renderWithSuspense(<HandleIndexAndPaymentsRoute />, { route: '/payments' });

    // Should render the PaymentsDashboard component
    expect(screen.getByTestId('payments-dashboard')).toBeInTheDocument();
  });

  test('renders PaymentsDashboard for other non-root paths', () => {
    renderWithSuspense(<HandleIndexAndPaymentsRoute />, { route: '/some-other-path' });

    // Should render the PaymentsDashboard component
    expect(screen.getByTestId('payments-dashboard')).toBeInTheDocument();
  });
});
