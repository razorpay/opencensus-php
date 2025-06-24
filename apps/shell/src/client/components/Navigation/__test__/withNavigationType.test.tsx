import React from 'react';
import { screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import withNavigationType from '../withNavigationType';
import { render } from '@apps/shell/src/services/test/render';

// Create mocks for lazy-loaded components
const GrowthPageMock = () => <div data-testid="growth-page">Growth Page</div>;
const AccessDeniedPageMock = () => <div data-testid="access-denied-page">Access Denied Page</div>;

// Mock the Suspense and lazy components to avoid async loading issues
jest.mock('react', () => {
  const originalReact = jest.requireActual('react');
  return {
    ...originalReact,
    Suspense: ({ children }: { children: React.ReactNode }) => children,
    lazy: jest.fn().mockImplementation((importFn) => {
      // Return the corresponding mock based on the import path
      if (importFn.toString().includes('GrowthPage')) {
        return GrowthPageMock;
      } else if (importFn.toString().includes('AccessDenied')) {
        return AccessDeniedPageMock;
      }
      return () => <div>Unknown Component</div>;
    }),
  };
});

// Mock the navigation store
jest.mock('@federated/apps/shell/connected-navigation/connectedNavigationStore', () => {
  return {
    useConnectedNavigationStore: jest.fn(),
  };
});

// Mock other dependencies
jest.mock('@libs/shared-ui', () => {
  const originalModule = jest.requireActual('@libs/shared-ui');
  return {
    ...originalModule,
    DashboardLoader: () => <div data-testid="dashboard-loader">Loading...</div>,
  };
});

describe('withNavigationType HOC', () => {
  const {
    useConnectedNavigationStore,
  } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');

  // Create a simple wrapped component for testing
  const TestComponent = () => <div data-testid="test-component">Test Component</div>;
  const WrappedComponent = withNavigationType(TestComponent);

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders a loader when no navigation type is available', () => {
    // Mock store with no selected product
    useConnectedNavigationStore.mockReturnValue({
      products: {
        selectedProduct: {
          selectAction: {
            actionType: null,
          },
        },
      },
    });

    render(<WrappedComponent />);

    expect(screen.getByTestId('dashboard-loader')).toBeInTheDocument();
  });

  test('renders AccessDeniedPage when type is access_denied_page', () => {
    // Mock store with access_denied_page type
    useConnectedNavigationStore.mockReturnValue({
      products: {
        selectedProduct: {
          selectAction: {
            actionType: 'access_denied_page',
          },
        },
      },
    });

    render(<WrappedComponent />);

    // Access denied page should be rendered within a WorkspaceWrapper
    expect(screen.getByTestId('access-denied-page')).toBeInTheDocument();
  });

  test('renders GrowthPage when type is growth_page', () => {
    // Mock store with growth_page type
    useConnectedNavigationStore.mockReturnValue({
      products: {
        selectedProduct: {
          selectAction: {
            actionType: 'growth_page',
          },
        },
      },
    });

    render(<WrappedComponent />);

    // Growth page should be rendered within a WorkspaceWrapper
    expect(screen.getByTestId('growth-page')).toBeInTheDocument();
  });

  test('renders original component when type is unknown', () => {
    // Mock store with unknown type
    useConnectedNavigationStore.mockReturnValue({
      products: {
        selectedProduct: {
          selectAction: {
            actionType: 'unknown_type',
          },
        },
      },
    });

    render(<WrappedComponent />);

    // Should render the original wrapped component
    expect(screen.getByTestId('test-component')).toBeInTheDocument();
  });
});
