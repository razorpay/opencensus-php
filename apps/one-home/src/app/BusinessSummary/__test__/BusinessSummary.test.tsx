import React from 'react';

import {
  customRender,
  screen,
  waitFor,
  fireEvent,
} from '@apps/one-home/src/services/test/test-utils';
import BussinessInsights from '../BusinessSummary';
import useBusinessSummary from '../useBusinessSummary';
import {
  fullResponse,
  responseWithLocked,
  responseWithGrowth,
  responseWithNoOffline,
} from './mockData';
import { useNavigate } from 'react-router-dom';
import { EASY_ONBOARDING_URL, messages } from '../constants';
import { BalanceData, BalanceSuccessResponse, BusinessSummarySuccessResponse } from '../types';

jest.mock('../useBusinessSummary');
jest.mock('@apps/one-home/src/hooks/useOneHomeAnalytics', () => {
  return {
    __esModule: true,
    default: jest.fn(() => ({
      trackOneHomeAnalytics: jest.fn(),
    })),
  };
});

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: jest.fn(),
}));
const mockNavigate = jest.fn();

const mockedUseBusinessSummary = useBusinessSummary as jest.Mock;

const updateBalanceData = (
  response: BusinessSummarySuccessResponse,
  updateFn: (balance: BalanceData) => void,
) => {
  const modifiedResponse = JSON.parse(JSON.stringify(response)); // Deep copy to avoid modifying original data
  const balanceItem = modifiedResponse.components.find(
    (comp: BalanceSuccessResponse) => comp.alias === 'home_summary_balance_item',
  );

  if (balanceItem) {
    updateFn(balanceItem.data.one_home_data.business_summary.balance);
  }

  return modifiedResponse;
};

describe('BussinessInsights Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (useNavigate as jest.Mock).mockReturnValue(mockNavigate);
    window.open = jest.fn();
  });

  it('renders Skeleton loaders for Earnings, Balance and Spends Card during initial loading', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: null,
      isError: false,
      error: null,
      isLoading: true,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      expect(screen.getByTestId('business-insights-earnings-card-loader')).toBeInTheDocument();
      expect(screen.getByTestId('business-insights-balance-card-loader')).toBeInTheDocument();
      expect(screen.getByTestId('business-insights-spends-card-loader')).toBeInTheDocument();
    });
  });

  it('renders SectionHeader with badges when data is available', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      expect(screen.getByText('Your business with Razorpay')).toBeInTheDocument();
      expect(screen.getByText(/updated/i)).toBeInTheDocument();
    });
  });

  it('renders fallback UI in ErrorBoundary when an error occurs', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: null,
      isError: true,
      error: new Error('Failed to fetch data'),
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      expect(screen.getByText(messages.businessSummarySection.errorMessage)).toBeInTheDocument();
    });
  });

  it('calls handleChangeFilter when DateFilter option is changed', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    const { getByRole } = customRender(<BussinessInsights />);

    const dateFilter = getByRole('combobox');
    fireEvent.change(dateFilter, { target: { value: 'last_7_days' } });

    await waitFor(() => {
      expect(dateFilter).toHaveValue('last_7_days');
    });
  });

  it('renders EarningsCard, BalanceCard, and SpendsCard with data', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      /**Earnings Card */
      expect(screen.getByText('EARNINGS')).toBeInTheDocument();
      expect(screen.getByText('Online')).toBeInTheDocument();
      expect(screen.getByText('International')).toBeInTheDocument();
      expect(screen.getByText('Offline | Razorpay POS')).toBeInTheDocument();

      /**Balance Card */
      expect(screen.getByText('TOTAL BALANCE')).toBeInTheDocument();
      expect(screen.getByText('Current A/c')).toBeInTheDocument();
      expect(screen.getByText('Settlement A/c')).toBeInTheDocument();
      //   expect(screen.getByText('9 % vs This Week')).toBeInTheDocument();

      /**Spends Card */
      expect(screen.getByText('SPENDS')).toBeInTheDocument();
      expect(screen.getByText('S2P | Vendor Payouts')).toBeInTheDocument();
      expect(screen.getByText('API and Bulk Payouts')).toBeInTheDocument();
    });
  });

  it('handles response with locked payment and renders locked UI', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: responseWithLocked,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      expect(screen.getByText(messages.earningsCard.lockedCardDescription)).toBeInTheDocument();
      expect(screen.getByText(messages.spendsCard.lockedCardDescription)).toBeInTheDocument();
    });
  });

  it('renders no offline payments UI with responseWithNoOffline', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: responseWithNoOffline,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      expect(screen.getByText('Offline payments with POS')).toBeInTheDocument();
    });
  });

  it("renders 'Growth' UI with responseWithGrowth for Earnings and spends Card", async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: responseWithGrowth,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      expect(screen.getByText(messages.earningsCard.growthCardTitle)).toBeInTheDocument();
      expect(screen.getByText(messages.earningsCard.growthCardDescription)).toBeInTheDocument();
      expect(screen.getByText(messages.spendsCard.growthCardTitle)).toBeInTheDocument();
      expect(screen.getByText(messages.spendsCard.growthCardDescription)).toBeInTheDocument();
    });
  });

  it("should open 'Easy Onboarding' link when 'Unlock Payments' button is clicked", async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: responseWithGrowth,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      const unlockPaymentsButton = screen.getByText(messages.earningsCard.growthCardCtaLabel);
      fireEvent.click(unlockPaymentsButton);
    });

    expect(window.open).toHaveBeenCalledWith(EASY_ONBOARDING_URL, '_blank');
  });

  it("should navigate to /banking path when 'Unlock Banking+' button is clicked", async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: responseWithGrowth,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    await waitFor(() => {
      const unlockBankingButton = screen.getByText(messages.spendsCard.growthCardCtaLabel);
      fireEvent.click(unlockBankingButton);
    });

    expect(mockNavigate).toHaveBeenCalledWith('/banking');
  });

  it('should navigate to /vendor-payouts path when clicked on Automate account payables with S2P when vendor_payouts is not enabled ', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: responseWithNoOffline,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);
    await waitFor(() => {
      const vendorPayoutsButton = screen.getByText('Automate account payables with S2P');
      fireEvent.click(vendorPayoutsButton);
    });

    expect(window.open).toHaveBeenCalledWith(
      'https://x.razorpay.com/vendor-payouts?utm_source=r1_dashboard&utm_content=home_business_summary',
      '_blank',
    );
  });

  it('should redirect to payroll url login when click on "Pay salaries with Razorpay Payroll" when payroll is not enabled', async () => {
    mockedUseBusinessSummary.mockReturnValue({
      data: responseWithNoOffline,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);
    await waitFor(() => {
      const bulkPayoutsButton = screen.getByText('Pay salaries with Razorpay Payroll');
      fireEvent.click(bulkPayoutsButton);
    });

    expect(window.open).toHaveBeenCalledWith(
      'https://payroll.razorpay.com/login?utm_source=r1_dashboard&utm_content=home_business_summary',
      '_blank',
    );
  });

  describe('Balance Card', () => {
    it('renders Ideal Case (Both accounts present & unlocked)', async () => {
      mockedUseBusinessSummary.mockReturnValue({
        data: fullResponse,
        isError: false,
        error: null,
        isLoading: false,
        isFetching: false,
      });

      customRender(<BussinessInsights />);

      expect(screen.getByText('TOTAL BALANCE')).toBeInTheDocument();
      expect(screen.getByText('Current A/c')).toBeInTheDocument();
      expect(screen.getByText('Settlement A/c')).toBeInTheDocument();
      expect(screen.queryByText('****')).not.toBeInTheDocument(); // No masked balance
      expect(screen.queryByRole('button')).not.toBeInTheDocument(); // No "Create Account" button
    });

    test('renders Locked Current Account', () => {
      const testData = updateBalanceData(fullResponse, (balance) => {
        balance.locked_current_account = true;
      });

      mockedUseBusinessSummary.mockReturnValue({
        data: testData,
        isError: false,
        error: null,
        isLoading: false,
        isFetching: false,
      });

      customRender(<BussinessInsights />);
      expect(screen.getByText('TOTAL BALANCE')).toBeInTheDocument();
      expect(screen.getByText('Settlement A/c')).toBeInTheDocument();
      expect(screen.getByText('******')).toBeInTheDocument(); // Current account is masked
      expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    test('renders Settlement A/C Balance and should show Banking Growth Button', () => {
      const testData = updateBalanceData(fullResponse, (balance) => {
        balance.current_account_present = false;
      });

      mockedUseBusinessSummary.mockReturnValue({
        data: testData,
        isError: false,
        error: null,
        isLoading: false,
        isFetching: false,
      });

      customRender(<BussinessInsights />);

      expect(screen.getByText('SETTLEMENT A/c BALANCE')).toBeInTheDocument();
      expect(screen.queryByText('Current A/c')).not.toBeInTheDocument();
      expect(screen.queryByText('Settlement A/c')).not.toBeInTheDocument();
      expect(screen.getByRole('button')).toBeInTheDocument();
    });

    test('renders X Only, Settlement A/C Balance, Total Balance and Settlement Account Locked (Settlement Account Locked, Current Account Present & Unlocked)', () => {
      const testData = updateBalanceData(fullResponse, (balance) => {
        balance.locked_settlement_account = true;
      });

      mockedUseBusinessSummary.mockReturnValue({
        data: testData,
        isError: false,
        error: null,
        isLoading: false,
        isFetching: false,
      });

      customRender(<BussinessInsights />);

      expect(screen.getByText('TOTAL BALANCE')).toBeInTheDocument();
      expect(screen.getByText('Current A/c')).toBeInTheDocument();
      expect(screen.getByText('******')).toBeInTheDocument();
      expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
  });

  test('renders X Only (Only Current Account Present & Unlocked)', () => {
    const testData = updateBalanceData(fullResponse, (balance) => {
      balance.settlement_account_present = false;
    });

    mockedUseBusinessSummary.mockReturnValue({
      data: testData,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    expect(screen.getByText('CURRENT A/c BALANCE')).toBeInTheDocument();
    expect(screen.queryByText('Settlement A/c')).not.toBeInTheDocument();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  test('renders PG Only Hidden (Only Settlement Account Present & Locked)', () => {
    const testData = updateBalanceData(fullResponse, (balance) => {
      balance.locked_settlement_account = true;
      balance.current_account_present = false;
    });

    mockedUseBusinessSummary.mockReturnValue({
      data: testData,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    expect(screen.getByText('SETTLEMENT A/c BALANCE')).toBeInTheDocument();
    expect(screen.getByText('******')).toBeInTheDocument();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  test('renders Both Accounts Locked (PG Locked, X Locked)', () => {
    const testData = updateBalanceData(fullResponse, (balance) => {
      balance.locked_current_account = true;
      balance.locked_settlement_account = true;
    });

    mockedUseBusinessSummary.mockReturnValue({
      data: testData,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<BussinessInsights />);

    expect(screen.getByText('TOTAL BALANCE')).toBeInTheDocument();
    expect(screen.getByText('Current A/c')).toBeInTheDocument();
    expect(screen.getByText('Settlement A/c')).toBeInTheDocument();
    expect(screen.getAllByText('******')).toHaveLength(3);
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  //This case wont appear in the UI
  // test('renders No Accounts Present', () => {
  //   const testData = updateBalanceData(fullResponse, (balance) => {
  //     balance.current_account_present = false;
  //     balance.settlement_account_present = false;
  //   });

  //   mockedUseBusinessSummary.mockReturnValue({
  //     data: testData,
  //     isError: false,
  //     error: null,
  //     isLoading: false,
  //     isFetching: false,
  //   });

  //   customRender(<BussinessInsights />);

  //   expect(screen.queryByText('TOTAL BALANCE')).not.toBeInTheDocument();
  //   expect(screen.queryByText('Current A/c')).not.toBeInTheDocument();
  //   expect(screen.queryByText('Settlement A/c')).not.toBeInTheDocument();
  //   expect(screen.queryByRole('button')).not.toBeInTheDocument();
  // });
});
