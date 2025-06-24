import React from 'react';
import { screen } from '@testing-library/react';
import AccessDeniedPage from '../AccessDenied';
import { render } from '@apps/shell/src/services/test/render';

// Since we're using the proper render with BladeProvider, we only need to
// mock the specific components and hooks that are causing issues
jest.mock('@razorpay/blade/utils', () => {
  const originalModule = jest.requireActual('@razorpay/blade/utils');
  return {
    ...originalModule,
    useBreakpoint: () => ({
      matchedDeviceType: 'desktop',
    }),
  };
});

// Mock the LockIcon component
jest.mock('@razorpay/blade/components', () => {
  const originalModule = jest.requireActual('@razorpay/blade/components');
  return {
    ...originalModule,
    LockIcon: () => <div data-testid="lock-icon" />,
  };
});

// Mock the useConnectedNavigationStore hook
jest.mock('@federated/apps/shell/connected-navigation/connectedNavigationStore', () => {
  const originalModule = jest.requireActual(
    '@federated/apps/shell/connected-navigation/connectedNavigationStore',
  );
  return {
    ...originalModule,
    useConnectedNavigationStore: jest.fn().mockReturnValue({
      products: {
        selectedProduct: {
          selectAction: {
            pageData: {
              title: 'Access Denied',
              description: 'You do not have permission to access this page',
            },
          },
        },
      },
    }),
  };
});

describe('AccessDeniedPage', () => {
  test('renders with title and description from store', () => {
    render(<AccessDeniedPage />);

    expect(screen.getByText('Access Denied')).toBeTruthy();
    expect(screen.getByText('You do not have permission to access this page')).toBeTruthy();
  });

  test('displays the LockIcon', () => {
    render(<AccessDeniedPage />);

    expect(screen.getByTestId('lock-icon')).toBeTruthy();
  });
});
