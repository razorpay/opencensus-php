import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import * as graphqlUtils from '@federated/apps/shell/graphql';
import SalesDashboard from '../SalesDashboard';
import {
  SUCCESS_SALES_MAPPED_MERCHANTS_EMPTY_RESPONSE,
  SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE,
} from './mocks/fixtures';
import {
  render,
  screen,
  waitFor,
  within,
} from 'apps/pos/src/services/test/test-utils';
import useOnboardingStore from 'apps/pos/src/bootstrap/Store';

jest.mock('apps/pos/src/bootstrap/Store', () => {
  return { __esModule: true, default: jest.fn() };
});

jest.mock('@federated/apps/shell/graphql', () => {
  return {
    __esModule: true,
    ...jest.requireActual('@federated/apps/shell/graphql'),
  };
});

jest.setTimeout(30000);

const queryClient = new QueryClient();

const renderApp = () => {
  render(
    <QueryClientProvider client={queryClient}>
      <SalesDashboard />
    </QueryClientProvider>,
  );
};

describe('<SalesDashboard/>', () => {
  beforeEach(() => {
    queryClient.clear();
  });

  afterEach(() => {
    jest.clearAllMocks();
    queryClient.clear();
  });

  (useOnboardingStore as unknown as jest.Mock).mockReturnValue({
    workflowProduct: 'assisted_onboarding',
    isPosEkycAgent: false,
    pwaPrompt: null,
    filters: {
      dateRange: {
        startDate: 1738348200, // Mocked timestamps
        endDate: 1740767399,
      },
      activationStatus: 'all',
    },
    setWorkflowProduct: jest.fn(),
    setIsPosEkycAgent: jest.fn(),
    setPwaPrompt: jest.fn(),
    setFilters: jest.fn(),
  });

  test('should render sales dashboard on screen', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    graphqlRequestSpy.mockReturnValue(Promise.resolve(SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE));
    renderApp();
    const salesTable = screen.getByTestId('sales-table');
    await waitFor(() => {
      expect(within(salesTable).getByText('OLvMDMRFFdl9TU')).toBeInTheDocument();
    });
  });


  test('should render status counts on screen', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    graphqlRequestSpy.mockReturnValue(Promise.resolve(SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE));
    renderApp();
    const activatedChip = screen.getByTestId('activated');
    const rejectedChip = screen.getByTestId('rejected');
    const pendingChip = screen.getByTestId('pending');
    const kycQualifiedChip = screen.getByTestId('kycQualifiedStb');
    const NeedsClarificationChip = screen.getByTestId('needsClarification');
    const underReviewChip = screen.getByTestId('underReview');
    const pricingNeedsClarificationChip = screen.getByTestId('pricingNeedsClarification');
    const salesTable = screen.getByTestId('sales-table');
    await waitFor(() => {
      expect(within(salesTable).getByText('OLvMDMRFFdl9TU')).toBeInTheDocument();
    });
    expect(within(activatedChip).getByText('Activated - 20')).toBeInTheDocument();
    expect(within(rejectedChip).getByText('Rejected - 21')).toBeInTheDocument();
    expect(within(kycQualifiedChip).getByText('KYC Qualified - 35')).toBeInTheDocument();
    expect(within(pendingChip).getByText('Pending - 10')).toBeInTheDocument();
    expect(within(underReviewChip).getByText('Under Review - 1')).toBeInTheDocument();
    expect(within(NeedsClarificationChip).getByText('KYC Needs Clarification - 7')).toBeInTheDocument();
    expect(within(pricingNeedsClarificationChip).getByText('Pricing Needs Clarification - 1')).toBeInTheDocument();
  });

  test('should show empty screen if no merchants are available', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    graphqlRequestSpy.mockReturnValue(
      Promise.resolve(SUCCESS_SALES_MAPPED_MERCHANTS_EMPTY_RESPONSE),
    );
    renderApp();
    await waitFor(() => {
      expect(
        screen.getByText(`We couldn't find any merchant details associated with your requests`),
      ).toBeInTheDocument();
    });
  });
  test('show render date range filter', async () => {
    const graphqlRequestSpy = jest.spyOn(graphqlUtils, 'graphqlRequest');
    graphqlRequestSpy.mockReturnValue(Promise.resolve(SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE));
    renderApp();
      const startInput = screen.getByRole('combobox', { name: /Start Date/i });
      const endInput = screen.getByRole('combobox', { name: /End Date/i });
    await waitFor(() => {
      expect(startInput).toBeInTheDocument();
      expect(endInput).toBeInTheDocument();
    });
  });
});
