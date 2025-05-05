import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import WelcomeHeader from '../WelcomeHeader';
import { useMerchantContext } from '@FTUX/context/MerchantContext';

// Mock dependencies
jest.mock('@FTUX/context/MerchantContext');

describe('WelcomeHeader Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders with merchant name when merchant data is available', () => {
    // Mock the hook to return merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
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
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: null,
    });

    renderWithWrappers(<WelcomeHeader />);

    // Should display "Welcome, " without a name
    expect(screen.getByText('Welcome,')).toBeInTheDocument();
  });

  test('renders without merchant name when merchant name is undefined', () => {
    // Mock the hook to return data but with undefined name
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
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
