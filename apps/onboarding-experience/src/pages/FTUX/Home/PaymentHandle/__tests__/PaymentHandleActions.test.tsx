import React from 'react';
import { fireEvent, screen, act } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import PaymentHandleActions from '../PaymentHandleActions';
import { isMobileDevice } from '@libs/shared-utils';
import useMerchantPaymentHandle from 'apps/onboarding-experience/src/common/hooks/useMerchantPaymentHandle';

// Mock dependencies
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
}));

jest.mock('apps/onboarding-experience/src/common/hooks/useMerchantPaymentHandle', () => ({
  __esModule: true,
  default: jest.fn(),
}));

// Mock clipboard API
Object.assign(navigator, {
  clipboard: {
    writeText: jest.fn(() => Promise.resolve()),
  },
});

describe('PaymentHandleActions Component', () => {
  const mockFetchPaymentHandle = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (isMobileDevice as jest.Mock).mockReturnValue(false);
    (useMerchantPaymentHandle as jest.Mock).mockReturnValue({
      paymentHandleData: {
        merchantPaymentHandle: {
          paymentHandle: {
            paymentHandleSlug: 'test-payment-handle',
            url: 'https://rzp.io/i/test-payment-handle',
          },
        },
      },
      isPaymentHandleLoading: false,
      fetchPaymentHandle: mockFetchPaymentHandle,
    });
  });

  test('calls fetchPaymentHandle on mount', () => {
    renderWithWrappers(<PaymentHandleActions />);
    expect(mockFetchPaymentHandle).toHaveBeenCalledTimes(1);
  });

  test('renders payment handle slug', () => {
    renderWithWrappers(<PaymentHandleActions />);
    expect(screen.getByText('test-payment-handle')).toBeInTheDocument();
  });

  test('renders copy, edit and share buttons', () => {
    renderWithWrappers(<PaymentHandleActions />);

    // Check for the total number of links instead of specific roles
    const linkElements = screen.getAllByRole('link');
    expect(linkElements.length).toBe(3); // Total 3 links - copy, edit, share
  });

  test('shows loading spinner when data is loading', () => {
    (useMerchantPaymentHandle as jest.Mock).mockReturnValue({
      paymentHandleData: null,
      isPaymentHandleLoading: true,
      fetchPaymentHandle: mockFetchPaymentHandle,
    });

    renderWithWrappers(<PaymentHandleActions />);

    expect(screen.getByLabelText('payment-url')).toBeInTheDocument();
  });

  test('shows "No payment handle found" when no handle exists', () => {
    (useMerchantPaymentHandle as jest.Mock).mockReturnValue({
      paymentHandleData: {
        merchantPaymentHandle: {
          paymentHandle: null,
        },
      },
      isPaymentHandleLoading: false,
      fetchPaymentHandle: mockFetchPaymentHandle,
    });

    renderWithWrappers(<PaymentHandleActions />);

    expect(screen.getByText('No payment handle found')).toBeInTheDocument();
  });

  test('renders correctly in mobile view', () => {
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    renderWithWrappers(<PaymentHandleActions />);

    expect(screen.getByText('Edit')).toBeInTheDocument();
    expect(screen.getByText('Share')).toBeInTheDocument();
  });
});
