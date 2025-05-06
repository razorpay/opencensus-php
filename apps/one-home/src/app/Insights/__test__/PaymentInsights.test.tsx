import React from 'react';
import {
  customRender,
  screen,
  waitFor,
  fireEvent,
} from '@apps/one-home/src/services/test/test-utils';
import PaymentInsights from '../PaymentInsights';
import useInsights from '../useInsights';
import {
  fullResponse,
  nonPGResponse,
  lockedResponse,
  emptyResponse,
} from './paymentInsightMockData';
import { staticContent, insightCardsStaticData } from '../constants';
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

const mockedUseInsights = useInsights as jest.Mock;
const mockedUseTheme = useTheme as jest.Mock;
const mockedUseBreakpoint = useBreakpoint as jest.Mock;
const mockedNavigate = jest.fn();

describe('PaymentInsights Component', () => {
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

    customRender(<PaymentInsights />);

    await waitFor(() => {
      expect(screen.getByText('Payment insights')).toBeInTheDocument();
      expect(screen.queryByLabelText(staticContent.insightsBtnLabel)).toBeInTheDocument();
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

    customRender(<PaymentInsights />);

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

    customRender(<PaymentInsights />);

    await waitFor(() => {
      expect(screen.getAllByTestId('onehome-payment-insight-skeleton').length).toBeGreaterThan(0);
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

    const { getByRole } = customRender(<PaymentInsights />);

    const dateFilter = getByRole('combobox');
    fireEvent.change(dateFilter, { target: { value: 'last_30_days' } });

    await waitFor(() => {
      expect(dateFilter).toHaveValue('last_30_days');
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

    customRender(<PaymentInsights />);

    await waitFor(() => {
      // Payments Collected Card
      expect(screen.getByText(insightCardsStaticData.payment.title)).toBeInTheDocument();
      expect(screen.getByText(/top payment method/i)).toBeInTheDocument();

      // Success Rate Card
      expect(screen.getByText(insightCardsStaticData.success_rate.title)).toBeInTheDocument();
      expect(screen.getByText(/Among known error reasons/i)).toBeInTheDocument();

      // Refunds Card
      expect(screen.getByText(insightCardsStaticData.refund.title)).toBeInTheDocument();
      expect(screen.getByText(/Your refunds accounted/i)).toBeInTheDocument();

      // Check for Ray Insight Badge text
      expect(screen.queryAllByText(staticContent.rayInsightBadgeText).length).toBeGreaterThan(0);
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

    customRender(<PaymentInsights />);

    await waitFor(() => {
      expect(screen.queryByLabelText(staticContent.insightsLinkLabel)).not.toBeInTheDocument();
      expect(screen.queryAllByLabelText(staticContent.mobileBtnLabel).length).toBeGreaterThan(0);
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
    customRender(<PaymentInsights />);

    const links = screen.queryAllByLabelText(staticContent.insightsLinkLabel);
    expect(links.length).toBeGreaterThan(0);

    const expectedPaths = [
      insightCardsStaticData.payment.redirectionUrl,
      insightCardsStaticData.success_rate.redirectionUrl,
      insightCardsStaticData.refund.redirectionUrl,
    ];

    links.forEach(async (link, index) => {
      fireEvent.click(link);
      expect(mockedNavigate).toHaveBeenCalledWith(expectedPaths[index]);
    });
  });

  it('should render See detailed insights button with the correct URL', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: fullResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    (useNavigate as jest.Mock).mockReturnValue(mockedNavigate);
    customRender(<PaymentInsights />);

    const detailedInsightsBtn = screen.queryByLabelText(staticContent.insightsBtnLabel);
    expect(detailedInsightsBtn).toBeInTheDocument();
    fireEvent.click(detailedInsightsBtn!);
    expect(mockedNavigate).toHaveBeenCalledWith(staticContent.dashboardUrl);
  });

  it('should hide payment insight section for non pg user', async () => {
    mockedUseInsights.mockReturnValue({
      insightsData: nonPGResponse,
      isError: false,
      error: null,
      isLoading: false,
      isFetching: false,
    });

    customRender(<PaymentInsights />);

    await waitFor(() => {
      expect(screen.queryByText('Payment insights')).not.toBeInTheDocument();
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

    customRender(<PaymentInsights />);

    await waitFor(() => {
      expect(
        screen.queryAllByText(insightCardsStaticData.payment.lockCardDescription),
      ).toHaveLength(3);
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

    customRender(<PaymentInsights />);

    await waitFor(() => {
      expect(
        screen.queryAllByText(insightCardsStaticData.payment.emptyCardDescription),
      ).toHaveLength(1);
    });
  });
});
