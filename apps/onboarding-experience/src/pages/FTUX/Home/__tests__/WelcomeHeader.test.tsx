import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import WelcomeHeader from '../WelcomeHeader';

// Mock the useMerchant hook
jest.mock('apps/onboarding-experience/src/common/hooks/useMerchant', () => ({
  __esModule: true,
  default: jest.fn(),
}));

// Import the mocked hook
import useMerchant from 'apps/onboarding-experience/src/common/hooks/useMerchant';

describe('WelcomeHeader Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders with merchant name when merchant data is available', () => {
    // Mock the hook to return merchant data
    (useMerchant as jest.Mock).mockReturnValue({
      data: {
        merchantById: {
          name: {
            registered: 'Test Merchant',
          },
        },
      },
    });

    renderWithWrappers(<WelcomeHeader />);

    // Check if merchant name is displayed
    expect(screen.getByText('Welcome, Test Merchant')).toBeInTheDocument();
  });

  test('renders without merchant name when merchant data is not available', () => {
    // Mock the hook to return undefined merchant data
    (useMerchant as jest.Mock).mockReturnValue({
      data: null,
    });

    renderWithWrappers(<WelcomeHeader />);

    // Should display "Welcome, " without a name
    expect(screen.getByText('Welcome,')).toBeInTheDocument();
  });

  test('renders without merchant name when merchant name is undefined', () => {
    // Mock the hook to return data but with undefined name
    (useMerchant as jest.Mock).mockReturnValue({
      data: {
        merchantById: {
          name: {
            registered: undefined,
          },
        },
      },
    });

    renderWithWrappers(<WelcomeHeader />);
    expect(screen.getByText('Welcome,')).toBeInTheDocument();
  });
});
