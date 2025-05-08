import React from 'react';
import { fireEvent, screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import PaymentHandle from '../index';
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

jest.mock('@FTUX/modals/SettlementsGuideModal', () => ({
  __esModule: true,
  default: jest.fn(({ onDismiss }) => (
    <div data-testid="settlements-guide-modal">
      Settlements Guide Modal
      <button onClick={onDismiss}>Close</button>
    </div>
  )),
}));

describe('PaymentHandle Component', () => {
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
      fetchPaymentHandle: jest.fn(),
    });
  });

  test('renders the component with correct heading', () => {
    renderWithWrappers(<PaymentHandle />);

    expect(screen.getByText('Accept payments with payment handle')).toBeInTheDocument();
  });

  test('renders description text', () => {
    renderWithWrappers(<PaymentHandle />);

    expect(
      screen.getByText(
        'Use this personalised link to accept payments instantly from your customers.',
      ),
    ).toBeInTheDocument();
  });

  test('renders settlement info with link', () => {
    renderWithWrappers(<PaymentHandle />);

    expect(
      screen.getByText(/By default, settlement cycles are 2 days/, { exact: false }),
    ).toBeInTheDocument();
    expect(screen.getByText('here.')).toBeInTheDocument();
  });

  test('opens SettlementsGuideModal when link is clicked', () => {
    renderWithWrappers(<PaymentHandle />);

    // Modal should not be visible initially
    expect(screen.queryByTestId('settlements-guide-modal')).not.toBeInTheDocument();

    // Click the link
    fireEvent.click(screen.getByText('here.'));

    // Modal should be visible now
    expect(screen.getByTestId('settlements-guide-modal')).toBeInTheDocument();
  });

  test('closes SettlementsGuideModal when dismiss is called', () => {
    renderWithWrappers(<PaymentHandle />);

    // Open the modal
    fireEvent.click(screen.getByText('here.'));
    expect(screen.getByTestId('settlements-guide-modal')).toBeInTheDocument();

    // Close the modal
    fireEvent.click(screen.getByText('Close'));

    // Modal should not be visible anymore
    expect(screen.queryByTestId('settlements-guide-modal')).not.toBeInTheDocument();
  });

  test('renders correctly in mobile view', () => {
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    renderWithWrappers(<PaymentHandle />);

    // Component should still render in mobile view
    expect(screen.getByText('Accept payments with payment handle')).toBeInTheDocument();
  });

  test('renders payment handle actions', () => {
    renderWithWrappers(<PaymentHandle />);

    // Check if PaymentHandleActions is rendered
    expect(screen.getByText('test-payment-handle')).toBeInTheDocument();
  });
});
