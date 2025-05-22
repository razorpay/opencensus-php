import React from 'react';
import {
  fireEvent,
  screen,
  act,
  within,
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

  test('calls fetchPaymentHandle on mount', () => {
    renderWithWrappers(<PaymentHandleActions />);
    expect(mockFetchPaymentHandle).toHaveBeenCalledTimes(1);
  });

  test('renders payment handle slug', () => {
    renderWithWrappers(<PaymentHandleActions />);
    expect(screen.getByText('rzp.io/i/test-payment-handle')).toBeInTheDocument();
  });

  test('renders copy, edit and share buttons', () => {
    const { container } = renderWithWrappers(<PaymentHandleActions />);

    // Find the parent container for payment handle
    const handleContainer = screen
      .getByText('rzp.io/i/test-payment-handle')
      .closest('[data-blade-component="box"]');
    expect(handleContainer).not.toBeNull();

    // Find the copy button within this container
    const copyButton = within(handleContainer as HTMLElement).getByRole('link');
    expect(copyButton).toBeInTheDocument();

    // Find the buttons container
    const buttonsContainer = container.querySelector(
      '[data-blade-component="box"][class*="cVIyYz"]',
    );
    expect(buttonsContainer).not.toBeNull();

    // Find edit button
    const editButton = within(buttonsContainer as HTMLElement).getByRole('button');
    expect(editButton).toBeInTheDocument();

    // Find share button
    const shareButton = within(buttonsContainer as HTMLElement).getByRole('link');
    expect(shareButton).toBeInTheDocument();
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
