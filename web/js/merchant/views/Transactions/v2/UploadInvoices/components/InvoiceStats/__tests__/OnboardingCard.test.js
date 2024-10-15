import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import OnboardingCard from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/OnboardingCard';
import { PopupContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/PopupContext';
import { MODAL_TYPES } from 'merchant/views/Transactions/v2/UploadInvoices/constants';
import { getOnboardingCardDetails } from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/utils';

jest.mock('merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/utils', () => ({
  getOnboardingCardDetails: jest.fn(),
}));

jest.mock(
  'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/StatsLoader',
  () => () => <div>Loading...</div>,
);

describe('Test Cases for OnboardingCard', () => {
  const mockOpenPopup = jest.fn();

  const defaultProps = {
    onboardingDetails: [],
    isLoading: false,
  };

  const renderComponent = (props = {}) => {
    return render(
      <PopupContext.Provider value={{ openPopup: mockOpenPopup }}>
        <OnboardingCard {...defaultProps} {...props} />
      </PopupContext.Provider>,
    );
  };

  beforeEach(() => {
    jest.clearAllMocks();
    getOnboardingCardDetails.mockReturnValue({
      bannerText: 'Complete just 1 more step to auto fetch e-invoices!',
      buttonText: 'Link your NIC portal now',
      partner: 'E_INVOICE',
      status: undefined,
    });
  });

  test('Should render the banner text and button text', () => {
    renderComponent();

    expect(
      screen.getByText('Complete just 1 more step to auto fetch e-invoices!'),
    ).toBeInTheDocument();
    expect(screen.getByText('Link your NIC portal now')).toBeInTheDocument();
  });

  test('Should render StatsLoader when isLoading is true', () => {
    renderComponent({ isLoading: true });
    expect(screen.getByText('Loading...')).toBeInTheDocument();
  });

  test('Should call openPopup with correct arguments when button is clicked and status is undefined', () => {
    renderComponent();

    const button = screen.getByRole('button', { name: /Link your NIC portal now/i });
    fireEvent.click(button);

    expect(mockOpenPopup).toHaveBeenCalledWith(MODAL_TYPES.ONBOARDING, {
      partner: 'E_INVOICE',
      status: undefined,
    });
  });

  test('Should call openPopup with correct arguments when button is clicked and status is defined', () => {
    getOnboardingCardDetails.mockReturnValue({
      bannerText: 'Complete just 1 more step to auto fetch e-invoices!',
      buttonText: 'Resume linking of GST portal',
      partner: 'GST_PORTAL',
      status: 'EXPIRED',
    });

    renderComponent();

    const button = screen.getByRole('button', { name: /Resume linking of GST portal/i });
    fireEvent.click(button);

    expect(mockOpenPopup).toHaveBeenCalledWith(MODAL_TYPES.LOGIN, {
      partner: 'GST_PORTAL',
      status: 'EXPIRED',
    });
  });

  test('Should render Razorpay, GST, and NIC portal logos', () => {
    renderComponent();

    expect(screen.getAllByRole('img')).toHaveLength(3); // RazorpayLogo, GstPortal, NicPortal
  });

  test('Should render the "Learn More" link', () => {
    renderComponent();

    expect(screen.getByText('Learn More')).toBeInTheDocument();
  });
});
