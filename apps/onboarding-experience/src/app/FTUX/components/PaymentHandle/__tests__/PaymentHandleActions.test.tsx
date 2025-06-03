import React from 'react';
import {
  fireEvent,
  screen,
  act,
  within,
  cleanup,
} from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import PaymentHandleActions from '../PaymentHandleActions';
import { isMobileDevice } from '@libs/shared-utils';
import useMerchantPaymentHandle from '@OnboardingExperienceCommons/hooks/useMerchantPaymentHandle';

// Mock dependencies
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
  copyToClipboard: jest.fn().mockImplementation(() => {}),
}));

jest.mock('@OnboardingExperienceCommons/hooks/useMerchantPaymentHandle', () => ({
  __esModule: true,
  default: jest.fn(),
}));

jest.mock('@federated/apps/shell/commonStore', () => ({
  useStore: jest.fn().mockImplementation((fn) => fn({ showNotification: jest.fn() })),
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

  afterEach(() => {
    cleanup();
  });

  test('calls fetchPaymentHandle on mount', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandleActions />);
    });
    expect(mockFetchPaymentHandle).toHaveBeenCalledTimes(1);
  });

  test('renders payment handle slug', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandleActions />);
    });
    expect(screen.getByText('rzp.io/i/test-payment-handle')).toBeInTheDocument();
  });

  test('shows loading spinner when data is loading', async () => {
    (useMerchantPaymentHandle as jest.Mock).mockReturnValue({
      paymentHandleData: null,
      isPaymentHandleLoading: true,
      fetchPaymentHandle: mockFetchPaymentHandle,
    });

    await act(async () => {
      renderWithWrappers(<PaymentHandleActions />);
    });

    expect(screen.getByLabelText('payment-url')).toBeInTheDocument();
  });

  test('shows "No payment handle found" when no handle exists', async () => {
    (useMerchantPaymentHandle as jest.Mock).mockReturnValue({
      paymentHandleData: {
        merchantPaymentHandle: {
          paymentHandle: null,
        },
      },
      isPaymentHandleLoading: false,
      fetchPaymentHandle: mockFetchPaymentHandle,
    });

    await act(async () => {
      renderWithWrappers(<PaymentHandleActions />);
    });

    expect(screen.getByText('No payment handle found')).toBeInTheDocument();
  });

  test('renders correctly in mobile view', async () => {
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    await act(async () => {
      renderWithWrappers(<PaymentHandleActions />);
    });

    expect(screen.getByText('Edit')).toBeInTheDocument();
    expect(screen.getByText('Share')).toBeInTheDocument();
  });
});
