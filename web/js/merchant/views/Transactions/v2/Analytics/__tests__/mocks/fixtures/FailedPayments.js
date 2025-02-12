import { screen, render, waitFor } from 'test-utils';
import Failed from 'merchant/views/Transactions/v2/Analytics/EntityAnalytics/FailedPayments';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

const mockFetchFaileData = jest.fn();
let mockLoading = false;
let mockFailed = false;

const mockFetchSuccessRateData = jest.fn();
const mockSuccessRateLoading = false;
const mockSuccessRateFailed = false;

const failedPaymentsData = 234;
const failureInfo = {
  customer: { value: 10, failure_types: [] },
  bank: { value: 5, failure_types: [] },
  business: { value: 4, failure_types: [] },
  others: { value: 3, failure_types: [] },
};

jest.mock('merchant/views/Transactions/v2/Analytics/hooks', () => ({
  useFailedPaymentsData: () => ({
    failedPaymentsData,
    failureInfo,
    fetchFailedPaymentsData: mockFetchFaileData,
    loading: mockLoading,
    failed: mockFailed,
  }),
  useSuccessRateData: () => ({
    successRateData: 99,
    fetchSuccessRateData: mockFetchSuccessRateData,
    loading: mockSuccessRateLoading,
    failed: mockSuccessRateFailed,
  }),
}));

export function modifyData({ type = 'failed', kind = 'loading', value = true }) {
  if (kind === 'loading') {
    mockLoading = value;
  } else {
    mockFailed = value;
  }
  if (type === 'all') {
    if (kind === 'loading') {
      mockLoading = value;
    } else {
      mockFailed = value;
    }
  }
}

export const renderApp = ({ session } = {}) => {
  const renderOutput = render(<Failed />, {
    initialState: {
      session: {
        user: {
          merchant: {
            currency: 'INR',
          },
        },
        ...session,
      },
    },
  });
  return renderOutput;
};

export const assertRefetchData = async ({ type = 'failed', isNegate = false } = {}) => {
  await waitFor(() => {
    if (isNegate) {
      expect(mockFetchFaileData).not.toHaveBeenCalled();
    } else {
      expect(mockFetchFaileData).toHaveBeenCalled();
    }
    if (type === 'all') {
      if (isNegate) {
        expect(mockFetchSuccessRateData).not.toHaveBeenCalled();
      } else {
        expect(mockFetchSuccessRateData).toHaveBeenCalled();
      }
    }
  });
};

export const assertHeadings = async () => {
  await waitFor(() => {
    expect(screen.queryAllByText('Failed')[0]).toBeInTheDocument();
    expect(screen.queryAllByText('Bank-Related')[0]).toBeInTheDocument();
    expect(screen.queryAllByText('Customer drop-offs')[0]).toBeInTheDocument();
    expect(screen.queryAllByText('Business failures or others')[0]).toBeInTheDocument();
  });
};

export const assertFailureData = async () => {
  await assertHeadings();
  await waitFor(() => {
    expect(screen.queryByText(failedPaymentsData)).toBeInTheDocument();
    expect(screen.queryByText(failureInfo.bank.value)).toBeInTheDocument();
    expect(screen.queryByText(failureInfo.customer.value)).toBeInTheDocument();
    expect(
      screen.queryByText(failureInfo.others.value + failureInfo.business.value),
    ).toBeInTheDocument();
  });
};
