import React from 'react';
import * as graphqlUtils from '@federated/apps/shell/graphql';
import * as ReactQuery from '@tanstack/react-query';
import { render, screen, waitFor, within, fireEvent } from 'apps/pos/src/services/test/test-utils';
import useOnboardingStore from 'apps/pos/src/bootstrap/Store';
import SalesDashboard from '../SalesDashboard';
import { SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE } from './mocks/fixtures';

jest.mock('apps/pos/src/bootstrap/Store', () => {
  return { __esModule: true, default: jest.fn() };
});

jest.mock('@federated/apps/shell/graphql', () => {
  return {
    __esModule: true,
    ...jest.requireActual('@federated/apps/shell/graphql'),
  };
});

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

// Mock React Query hooks
jest.mock('@tanstack/react-query', () => {
  const actual = jest.requireActual('@tanstack/react-query');
  return {
    ...actual,
    useInfiniteQuery: jest.fn().mockImplementation(() => ({
      data: { pages: [] },
      fetchNextPage: jest.fn(),
      isLoading: false,
      isFetching: false,
    })),
    useQueryClient: jest.fn().mockImplementation(() => ({
      removeQueries: jest.fn(),
    })),
  };
});

jest.setTimeout(30000);

const renderApp = (props = {}): void => {
  render(<SalesDashboard {...props} />);
};

describe('<SalesDashboard/>', () => {
  let mockFetchNextPage: jest.Mock;
  let mockRemoveQueries: jest.Mock;
  let setFiltersMock: jest.Mock;

  beforeEach(() => {
    mockFetchNextPage = jest.fn();
    mockRemoveQueries = jest.fn();
    setFiltersMock = jest.fn();

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
      setFilters: setFiltersMock,
    });

    (ReactQuery.useInfiniteQuery as jest.Mock).mockImplementation(() => ({
      data: {
        pages: [SUCCESS_SALES_MAPPED_MERCHANTS_RESPONSE.salesOnboardedMerchants],
      },
      fetchNextPage: mockFetchNextPage,
      isLoading: false,
      isFetching: false,
    }));

    (ReactQuery.useQueryClient as jest.Mock).mockImplementation(() => ({
      removeQueries: mockRemoveQueries,
    }));
  });

  afterEach(() => {
    jest.clearAllMocks();
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

  test('should render EmptyScreen when there is no data', async () => {
    (ReactQuery.useInfiniteQuery as jest.Mock).mockImplementation(() => ({
      data: {
        pages: [],
      },
      fetchNextPage: mockFetchNextPage,
      isLoading: false,
      isFetching: false,
    }));
    renderApp();

    await waitFor(() => {
      expect(screen.getByText(/We couldn't find any merchant details/i)).toBeInTheDocument();
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
    expect(
      within(NeedsClarificationChip).getByText('KYC Needs Clarification - 7'),
    ).toBeInTheDocument();
    expect(
      within(pricingNeedsClarificationChip).getByText('Pricing Needs Clarification - 1'),
    ).toBeInTheDocument();
  });

  test('should render date range filter', async () => {
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
  test('should render add merchant button', async () => {
    renderApp();
    const addMerchantButton = screen.getByText('Add Merchant');
    expect(addMerchantButton).toBeInTheDocument();
    fireEvent.click(addMerchantButton);
    expect(mockNavigate).toHaveBeenCalledWith('onboarding/new');
  });

  test('resetAllFilters: should reset all filters to default values', async () => {
    renderApp();
    const clearFiltersLink = await screen.findByText('Clear Filters');
    fireEvent.click(clearFiltersLink);

    expect(setFiltersMock).toHaveBeenCalledWith(
      expect.objectContaining({
        dateRange: expect.any(Object),
        activationStatus: 'all',
      }),
    );
    expect(mockRemoveQueries).toHaveBeenCalled();
    expect(mockFetchNextPage).toHaveBeenCalledWith({ pageParam: 0 });
  });

  test('onDateApplyHandler: should apply date filter when date is selected', async () => {
    const setFiltersMock = jest.fn();
    (useOnboardingStore as unknown as jest.Mock).mockReturnValue({
      filters: {
        dateRange: {
          startDate: 1234567890,
          endDate: 1234657890,
        },
        activationStatus: 'all',
      },
      setFilters: setFiltersMock,
    });

    const mockRemoveQueries = jest.fn();
    (ReactQuery.useQueryClient as jest.Mock).mockImplementation(() => ({
      removeQueries: mockRemoveQueries,
    }));

    renderApp();

    const datePicker = screen.getByRole('combobox', { name: /Start Date/i });
    fireEvent.click(datePicker);

    const applyButton = await screen.findByRole('button', { name: /Apply/i });
    fireEvent.click(applyButton);

    await waitFor(() => {
      expect(setFiltersMock).toHaveBeenCalled();
      expect(mockRemoveQueries).toHaveBeenCalled();
    });
  });
});
