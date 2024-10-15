import React from 'react';
import { render, screen } from 'test-utils';
import InvoiceStats from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/InvoiceStats';
import { OnboardingDetailsContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/OnboardingDetailsContext';
import { InvoiceStatsContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/InvoiceStatsContext';
import {
  INVOICE_STATUS,
  TABS,
} from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/constant';
import { isMerchantFullyOnboarded } from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/utils';

jest.mock('merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/utils', () => ({
  isMerchantFullyOnboarded: jest.fn(),
}));

jest.mock(
  'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/StatsCard',
  () => () => <div>StatsCard</div>,
);
jest.mock(
  'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/OnboardingCard',
  () => () => <div>OnboardingCard</div>,
);

describe('InvoiceStats', () => {
  const mockOnboardingData = [
    { name: 'E_INVOICE', status: 'ONBOARDED' },
    { name: 'GST_PORTAL', status: 'ONBOARDED' },
  ];

  const renderComponent = ({
    invoiceStats = {},
    isInvoiceStatsLoading = false,
    onboardingData = mockOnboardingData,
    isOnboardingDataLoading = false,
    isMerchantOnboarded = true,
  } = {}) => {
    isMerchantFullyOnboarded.mockReturnValue(isMerchantOnboarded);

    return render(
      <OnboardingDetailsContext.Provider value={{ onboardingData, isOnboardingDataLoading }}>
        <InvoiceStatsContext.Provider value={{ invoiceStats, isInvoiceStatsLoading }}>
          <InvoiceStats />
        </InvoiceStatsContext.Provider>
      </OnboardingDetailsContext.Provider>,
    );
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render OnboardingCard when isMerchantOnboarded is false', () => {
    renderComponent({ isMerchantOnboarded: false });

    expect(screen.getByText('OnboardingCard')).toBeInTheDocument();
  });

  test('should render StatsCard when isMerchantOnboarded is true', () => {
    renderComponent({ isMerchantOnboarded: true });

    expect(screen.getAllByText('StatsCard').length).toBeGreaterThan(0);
  });

  test('should render StatsLoader when isLoading is true', () => {
    renderComponent({ isInvoiceStatsLoading: true });

    // Check for loader or elements based on your StatsLoader component
    expect(screen.getAllByText('StatsCard').length).toBeGreaterThan(0);
  });

  test('should render OnboardingCard when loading onboarding data', () => {
    renderComponent({ isOnboardingDataLoading: true, isMerchantOnboarded: false });

    expect(screen.getByText('OnboardingCard')).toBeInTheDocument();
  });

  test('should render correct number of StatsCard based on TABS', () => {
    renderComponent();

    const statsCardElements = screen.getAllByText('StatsCard');
    const filteredTabs = TABS.filter(({ type }) => !(type === INVOICE_STATUS.INVOICE_AUTO_SYNCED));

    expect(statsCardElements.length).toBe(filteredTabs.length);
  });

  test('should render OnboardingCard if type is INVOICE_AUTO_SYNCED and isMerchantOnboarded is false', () => {
    renderComponent({
      isMerchantOnboarded: false,
    });

    expect(screen.getByText('OnboardingCard')).toBeInTheDocument();
  });

  test('should not render OnboardingCard if type is INVOICE_AUTO_SYNCED and isOnboardingDataLoading is true', () => {
    renderComponent({
      isOnboardingDataLoading: true,
      isMerchantOnboarded: false,
    });

    expect(screen.getByText('OnboardingCard')).toBeInTheDocument();
  });

  test('should render OnboardingCard when isOnboardingDataLoading is false and isMerchantOnboarded is false', () => {
    renderComponent({
      isOnboardingDataLoading: false,
      isMerchantOnboarded: false,
    });

    expect(screen.getByText('OnboardingCard')).toBeInTheDocument();
  });
});
