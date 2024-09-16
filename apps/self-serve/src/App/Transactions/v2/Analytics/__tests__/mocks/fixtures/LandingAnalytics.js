import React from 'react';
import { render, waitFor } from 'apps/self-serve/src/services/test/test-utils';
import LandingAnalytics from 'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics';

jest.mock('@dashboard/shared-ui/hooks', () => ({
  useMobile: jest.fn(() => false),
}));

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics/TopOverview/index.ts',
  () => ({
    __esModule: true,
    default: (props) => {
      const { isPaymentsDataLoading } = props;
      return (
        <div>
          <h1>Top overview</h1>
          {isPaymentsDataLoading ? (
            <p data-testid="payments-data-failed">Payments Refresh</p>
          ) : null}
        </div>
      );
    },
  }),
);

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics/BottomOverview/index.ts',
  () => ({
    __esModule: true,
    default: (props) => {
      const {
        data: {
          refund: { failed: isRefundsDataFailed },
          disputes: { failed: isDisputesDataFailed },
          failed: { failed: isFailedPaymentsDataFailed },
        },
      } = props;
      return (
        <div>
          <h1>Bottom overview</h1>
          {isRefundsDataFailed ? <p data-testid="refunds-data-failed">Refunds Refresh</p> : null}
          {isDisputesDataFailed ? <p data-testid="disputes-data-failed">Disputes Refresh</p> : null}
          {isFailedPaymentsDataFailed ? (
            <p data-testid="failedPayments-data-failed">Failed Payments Refresh</p>
          ) : null}
        </div>
      );
    },
  }),
);

let mockIsPaymentsDataLoading = true;
let mockIsDisputesDataLoading = true;
let mockIsFailedPaymentsDataLoading = true;
let mockIsSuccessRateDataLoading = true;

let mockIsPaymentsDataFailed = false;
let mockIsDisputesDataFailed = false;
let mockIsFailedPaymentsDataFailed = false;
let mockIsSuccessRateDataFailed = false;

const mockFetchPaymentsData = jest.fn();
const mockFetchDisputesData = jest.fn();
const mockFetchFailedPaymentsData = jest.fn();
const mockFetchSuccessRateData = jest.fn();

jest.mock('apps/self-serve/src/App/Transactions/v2/Analytics/hooks', () => ({
  usePaymentsData: () => ({
    paymentsData: {
      paymentCapturedCount: 100,
      paymentCapturedAmount: 2321342,
      refundCount: 12,
      refundAmount: 12343,
      paymentByMethod: [
        {
          label: 'UPI',
          value: 1243214,
        },
        {
          label: 'COD',
          value: 14534,
        },
      ],
    },
    fetchPaymentData: mockFetchPaymentsData,
    loading: mockIsPaymentsDataLoading,
    failed: mockIsPaymentsDataFailed,
  }),
  useDisputesData: () => ({
    disputeData: {
      openDisputes: { count: 2, amount: 100 },
      underReviewDisputes: { count: 3, amount: 4 },
      wonDisputes: { count: 4, amount: 5 },
      lostDisputes: { count: 21, amount: 30 },
      totalDisputeAmount: 234,
    },
    fetchDisputesData: mockFetchDisputesData,
    loading: mockIsDisputesDataLoading,
    failed: mockIsDisputesDataFailed,
  }),
  useFailedPaymentsData: () => ({
    failedPaymentsData: 234,
    fetchFailedPaymentsData: mockFetchFailedPaymentsData,
    loading: mockIsFailedPaymentsDataLoading,
    failed: mockIsFailedPaymentsDataFailed,
  }),
  useSuccessRateData: () => ({
    successRateData: 99,
    fetchSuccessRateData: mockFetchSuccessRateData,
    loading: mockIsSuccessRateDataLoading,
    failed: mockIsSuccessRateDataFailed,
  }),
}));

const renderApp = ({ session, mode = 'live' } = {}) => {
  const renderOutput = render(<LandingAnalytics />, {
    initialState: {
      session: {
        mode,
        user: {
          isTransactionsV2Enabled: true,
          merchant: {
            currency: 'INR',
          },
          findTag: () => false,
          isAllowedView: () => false,
        },
        ...session,
      },
    },
  });
  return renderOutput;
};

function modifyData({ type = 'all', kind = 'loading', value = true }) {
  switch (type) {
    case 'payments':
    case 'refunds':
      if (kind === 'loading') {
        mockIsPaymentsDataLoading = value;
      } else {
        mockIsPaymentsDataFailed = value;
      }
      break;
    case 'disputes':
      if (kind === 'loading') {
        mockIsDisputesDataLoading = value;
      } else {
        mockIsDisputesDataFailed = value;
      }
      break;
    case 'failedPayments':
      if (kind === 'loading') {
        mockIsFailedPaymentsDataLoading = value;
      } else {
        mockIsFailedPaymentsDataFailed = value;
      }
      break;
    case 'successRate':
      if (kind === 'loading') {
        mockIsSuccessRateDataLoading = value;
      } else {
        mockIsSuccessRateDataFailed = value;
      }
      break;
    case 'all':
    default:
      if (kind === 'loading') {
        mockIsPaymentsDataLoading = value;
        mockIsDisputesDataLoading = value;
        mockIsFailedPaymentsDataLoading = value;
        mockIsSuccessRateDataLoading = value;
      } else {
        mockIsPaymentsDataFailed = value;
        mockIsDisputesDataFailed = value;
        mockIsFailedPaymentsDataFailed = value;
        mockIsSuccessRateDataFailed = value;
      }
      break;
  }
}

const assertRefetchData = async (type) => {
  await waitFor(() => {
    switch (type) {
      case 'payments':
      case 'refunds':
        expect(mockFetchPaymentsData).toHaveBeenCalled();
        break;
      case 'disputes':
        expect(mockFetchDisputesData).toHaveBeenCalled();
        break;
      case 'failedPayments':
        expect(mockFetchFailedPaymentsData).toHaveBeenCalled();
        break;
      case 'successRate':
        expect(mockFetchSuccessRateData).toHaveBeenCalled();
        break;
      case 'all':
      default:
        expect(mockFetchPaymentsData).toHaveBeenCalled();
        expect(mockFetchDisputesData).toHaveBeenCalled();
        expect(mockFetchFailedPaymentsData).toHaveBeenCalled();
        break;
    }
  });
};

const assertNoRefetchData = async (type) => {
  await waitFor(() => {
    switch (type) {
      case 'payments':
      case 'refunds':
        expect(mockFetchPaymentsData).not.toHaveBeenCalled();
        break;
      case 'disputes':
        expect(mockFetchDisputesData).not.toHaveBeenCalled();
        break;
      case 'failedPayments':
        expect(mockFetchFailedPaymentsData).not.toHaveBeenCalled();
        break;
      case 'successRate':
        expect(mockFetchSuccessRateData).not.toHaveBeenCalled();
        break;
      case 'all':
      default:
        expect(mockFetchPaymentsData).not.toHaveBeenCalled();
        expect(mockFetchDisputesData).not.toHaveBeenCalled();
        expect(mockFetchFailedPaymentsData).not.toHaveBeenCalled();
        break;
    }
  });
};

export { renderApp, modifyData, assertRefetchData, assertNoRefetchData };
