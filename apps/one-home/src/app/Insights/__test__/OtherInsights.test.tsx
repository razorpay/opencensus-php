import React from 'react';
import {
  customRender,
  screen,
  waitFor,
  fireEvent,
} from '@apps/one-home/src/services/test/test-utils';
import OtherInsights from '../OtherInsights';
import useInsights from '../useInsights';
import {
  fullResponse,
  bankingDisabledResponse,
  lockedResponse,
  emptyResponse,
} from './otherInsightMockData';
import { staticContent, insightCardsStaticData, nonInsightCardsStaticData } from '../constants';
import { useTheme, useBreakpoint } from '@razorpay/blade/utils';
import { useNavigate } from 'react-router-dom';

jest.mock('../useInsights');
jest.mock('@razorpay/blade/utils', () => ({
  useTheme: jest.fn(),
  useBreakpoint: jest.fn(),
}));
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: jest.fn(),
}));
jest.mock('@apps/one-home/src/hooks/useOneHomeAnalytics', () => {
  return {
    __esModule: true,
    default: jest.fn(() => ({
      trackOneHomeAnalytics: jest.fn(),
    })),
  };
});

const mockedNavigate = jest.fn();

const mockedUseInsights = useInsights as jest.Mock;
const mockedUseTheme = useTheme as jest.Mock;
const mockedUseBreakpoint = useBreakpoint as jest.Mock;

describe('OtherInsights Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();

    mockedUseTheme.mockReturnValue({
      theme: {
        breakpoints: {
          base: 0,
          xs: 320,
          s: 480,
          m: 768,
          l: 1024,
          xl: 1200,
        },
      },
    });

    mockedUseBreakpoint.mockReturnValue({
      matchedDeviceType: 'desktop',
    });
  });

  it('renders SectionHeader with badges when data is available', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<OtherInsights />);

    await waitFor(() => {
      expect(screen.getByText('Other insights')).toBeInTheDocument();
      expect(screen.getByText(/updated/i)).toBeInTheDocument();
    });
  });

  it('renders fallback UI in ErrorBoundary when an error occurs', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: null,
      isError: true,
      error: new Error('Failed to fetch data'),
      isLoading: false,
      isFetching: false,
    });

    customRender(<OtherInsights />);

    await waitFor(() => {
      expect(screen.getByText(staticContent.errorText)).toBeInTheDocument();
    });
  });

  it('renders loading skeleton when data is loading', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: null,
      isError: false,
      error: null,
      isLoading: true,
      isFetching: false,
    });

    customRender(<OtherInsights />);

    await waitFor(() => {
      expect(screen.getAllByTestId('onehome-other-insight-skeleton').length).toBeGreaterThan(0);
    });
  });

  it('calls handleChangeFilter when DateFilter option is changed', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    const { getByRole } = customRender(<OtherInsights />);

    const dateFilter = getByRole('combobox');
    fireEvent.change(dateFilter, { target: { value: 'last_30_days' } });

    await waitFor(() => {
      expect(dateFilter).toHaveValue('last_30_days');
    });
  });

  it('render correct UI for mobile', async () => {
    mockedUseBreakpoint.mockReturnValue({
      matchedDeviceType: 'mobile',
    });

    mockedUseInsights.mockReturnValue({
      insightsData: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<OtherInsights />);

    await waitFor(() => {
      expect(
        screen.queryByLabelText(staticContent.seeDetailedInsightsText),
      ).not.toBeInTheDocument();
      expect(screen.queryAllByLabelText(staticContent.mobileBtnLabel)).toHaveLength(1);
    });
  });

  it('renders InsightCard with Data', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<OtherInsights />);

    await waitFor(() => {
      // Payouts Collected Card
      expect(screen.getByText(insightCardsStaticData.payout.title)).toBeInTheDocument();
      expect(screen.getByText(/biggest expense/i)).toBeInTheDocument();

      // Payroll Card
      expect(screen.getByText(nonInsightCardsStaticData.payroll.title)).toBeInTheDocument();
      expect(screen.queryByLabelText(staticContent.nonInsightBtnLabel)).toBeInTheDocument();

      // Customers Card
      expect(screen.queryAllByText(/customers/i).length).toBeGreaterThan(0);
      expect(screen.getByText(staticContent.comingSoonText)).toBeInTheDocument();

      // Check for Ray Insight Badge text
      expect(screen.getByText(staticContent.rayInsightBadgeText)).toBeInTheDocument();
    });
  });

  it('should render View Details link with the correct URL', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    (useNavigate as jest.Mock).mockReturnValue(mockedNavigate);
    mockedNavigate.mockClear();
    customRender(<OtherInsights />);

    const links = screen.queryAllByLabelText(staticContent.insightsLinkLabel);
    expect(links.length).toBeGreaterThan(0);

    const expectedPaths = [insightCardsStaticData.payout.redirectionUrl];

    links.forEach(async (link, index) => {
      fireEvent.click(link);
      expect(mockedNavigate).toHaveBeenCalledWith(expectedPaths[index]);
    });
  });

  it('should render Get Started button with the correct URL', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    (useNavigate as jest.Mock).mockReturnValue(mockedNavigate);
    customRender(<OtherInsights />);

    const getStartedBtn = screen.queryByLabelText(staticContent.nonInsightBtnLabel);

    expect(getStartedBtn).toBeInTheDocument();
    fireEvent.click(getStartedBtn!);
    expect(mockedNavigate).toHaveBeenCalledWith('/payroll');
  });

  it('should render the Growth page when banking is disabled', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: bankingDisabledResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<OtherInsights />);

    await waitFor(() => {
      expect(screen.getByText(nonInsightCardsStaticData.payout.title)).toBeInTheDocument();
      expect(screen.queryAllByLabelText(staticContent.nonInsightBtnLabel)).toHaveLength(2);
    });
  });

  it('handles response with locked payment and renders locked UI', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: lockedResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<OtherInsights />);

    await waitFor(() => {
      expect(screen.queryAllByText(insightCardsStaticData.payout.lockCardDescription)).toHaveLength(
        1,
      );
    });
  });

  it('handles response with no data and renders empty UI', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: emptyResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<OtherInsights />);

    await waitFor(() => {
      expect(
        screen.queryAllByText(insightCardsStaticData.payout.emptyCardDescription),
      ).toHaveLength(1);
    });
  });
});
