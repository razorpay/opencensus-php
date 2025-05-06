import React from 'react';
import ErrorState from '@apps/one-home/src/components/ErrorBoundary/ErrorState';
import {
  customRender,
  fireEvent,
  screen,
  waitFor,
} from '@apps/one-home/src/services/test/test-utils';
import { ErrorBoundary } from '@libs/shared-ui';
import { useBreakpoint, useTheme } from '@razorpay/blade/utils';
import { useNavigate } from 'react-router-dom';
import useOneHomeAnalytics from '../../../hooks/useOneHomeAnalytics';
import { criticalSectionMessages } from '../constants';
import CriticalActionCard from '../CriticalActionCard';
import CriticalActions from '../CriticalActions';
import useCriticalActionLists from '../hooks/useCriticalActionLists';
import useCriticalActions from '../hooks/useCriticalActions';
import { criticalActionsMockData } from './mockData';

// Mock the hooks
jest.mock('../hooks/useCriticalActions');
jest.mock('../hooks/useCriticalActionLists');
jest.mock('../../../hooks/useOneHomeAnalytics');
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: jest.fn(),
}));
jest.mock('@razorpay/blade/utils', () => {
  const actualUtils = jest.requireActual('@razorpay/blade/utils');
  return {
    ...actualUtils,
    useTheme: jest.fn(),
    useBreakpoint: jest.fn(),
  };
});
jest.mock('@apps/shell/src/client/store/commonStore', () => ({
  useStore: jest.fn().mockReturnValue({ user: { name: 'Test User', product: 'Test Product' } }),
}));

const mockUseCriticalActions = useCriticalActions as jest.MockedFunction<typeof useCriticalActions>;
const mockUseCriticalActionLists = useCriticalActionLists as jest.MockedFunction<
  typeof useCriticalActionLists
>;
const mockUseOneHomeAnalytics = useOneHomeAnalytics as jest.MockedFunction<
  typeof useOneHomeAnalytics
>;
const mockUseBreakpoint = useBreakpoint as jest.MockedFunction<typeof useBreakpoint>;
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

const mockedNavigate = jest.fn();

describe('CriticalActions Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders loading state', () => {
    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: undefined,
      isLoading: true,
      error: null,
      criticalActionDataLength: 0,
    });
    mockUseTheme.mockReturnValue({
      theme: { breakpoints: { base: 0, m: 768, l: 1024, xl: 1280 } },
    });
    mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });
    mockUseOneHomeAnalytics.mockReturnValue({
      trackOneHomeAnalytics: ({ objectName, actionName, properties }) => {},
      isError: false,
      error: false,
    });
    mockUseCriticalActionLists.mockReturnValue({
      criticalActionsList: [],
      criticalActionsToShow: [],
      drawerCriticalActions: [],
    });

    customRender(<CriticalActions />);

    expect(screen.getByTestId('loading-shimmer')).toBeInTheDocument();
  });

  it('renders error state', () => {
    const error = new Error('Failed to fetch data');
    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: undefined,
      isLoading: false,
      error,
      criticalActionDataLength: 0,
    });
    mockUseTheme.mockReturnValue({
      theme: { breakpoints: { base: 0, m: 768, l: 1024, xl: 1280 } },
    });
    mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

    customRender(<CriticalActions />);

    expect(
      screen.getByText(
        "We couldn't load the critical actions due to a technical issue. Please try again or check back later.",
      ),
    ).toBeInTheDocument();
  });

  it('renders error state on CriticalActionCard component', () => {
    const mockData = {
      id: '',
      type: '',
      title: '',
      description: '',
      actions: [],
      alias: '',
      error: new Error('Failed to fetch data'),
    };

    customRender(
      <ErrorBoundary
        FallbackComponent={() => (
          <ErrorState
            size="medium"
            title={criticalSectionMessages.error.title}
            borderRadius="large"
          />
        )}
      >
        <CriticalActionCard {...mockData} />
      </ErrorBoundary>,
    );

    expect(
      screen.getByText(
        "We couldn't load the critical actions due to a technical issue. Please try again or check back later.",
      ),
    ).toBeInTheDocument();
  });

  it('renders no action alert', () => {
    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: { components: [] },
      isLoading: false,
      error: null,
      criticalActionDataLength: 0,
    });
    mockUseTheme.mockReturnValue({
      theme: { breakpoints: { base: 0, m: 768, l: 1024, xl: 1280 } },
    });
    mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

    customRender(<CriticalActions />);

    expect(
      screen.getByText('There are no actions that need your attention on Razorpay today.'),
    ).toBeInTheDocument();
  });

  it('renders critical actions content', () => {
    const mockData = criticalActionsMockData;
    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: mockData,
      isLoading: false,
      error: null,
      criticalActionDataLength: mockData.components.length,
    });
    mockUseCriticalActionLists.mockReturnValue({
      criticalActionsList: mockData.components,
      criticalActionsToShow: mockData.components,
      drawerCriticalActions: [],
    });
    mockUseOneHomeAnalytics.mockReturnValue({
      trackOneHomeAnalytics: jest.fn(),
      isError: false,
      error: null,
    });
    mockUseTheme.mockReturnValue({
      theme: { breakpoints: { base: 0, m: 768, l: 1024, xl: 1280 } },
    });
    mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

    customRender(<CriticalActions />);

    expect(screen.getByText('Critical Actions for you')).toBeInTheDocument();
    expect(screen.getByText('International Enablement Request')).toBeInTheDocument();
    expect(
      screen.getByText('International Enablement Request Requires Additional Information'),
    ).toBeInTheDocument();
  });

  it('handles show all click', async () => {
    const mockData = criticalActionsMockData;
    const trackShowAllClick = jest.fn();

    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: mockData,
      isLoading: false,
      error: null,
      criticalActionDataLength: mockData.components.length,
    });
    mockUseCriticalActionLists.mockReturnValue({
      criticalActionsList: mockData.components,
      criticalActionsToShow: mockData.components,
      drawerCriticalActions: mockData.components,
    });
    mockUseOneHomeAnalytics.mockReturnValue({
      trackOneHomeAnalytics: trackShowAllClick,
      isError: false,
      error: null,
    });
    mockUseTheme.mockReturnValue({
      theme: { breakpoints: { base: 0, m: 768, l: 1024, xl: 1280 } },
    });
    mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

    customRender(<CriticalActions />);

    fireEvent.click(screen.getByText('Show all'));

    await waitFor(() => {
      expect(trackShowAllClick).toHaveBeenCalled();
    });
  });

  it('handles CTA click', async () => {
    const mockData = criticalActionsMockData;
    const trackCTA = jest.fn();

    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: mockData,
      isLoading: false,
      error: null,
      criticalActionDataLength: mockData.components.length,
    });
    mockUseCriticalActionLists.mockReturnValue({
      criticalActionsList: mockData.components,
      criticalActionsToShow: mockData.components,
      drawerCriticalActions: [],
    });
    mockUseOneHomeAnalytics.mockReturnValue({
      trackOneHomeAnalytics: trackCTA,
      isError: false,
      error: null,
    });
    mockUseTheme.mockReturnValue({
      theme: { breakpoints: { base: 0, m: 768, l: 1024, xl: 1280 } },
    });
    mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

    (useNavigate as jest.Mock).mockReturnValue(mockedNavigate);

    customRender(<CriticalActions />);

    fireEvent.click(screen.getByText('Add money here'));

    await waitFor(() => {
      expect(trackCTA).toHaveBeenCalled();
      expect(mockedNavigate).toHaveBeenCalledWith('/settings/payouts');
    });
  });

  it('should open the drawer on click of "Show All" button, and should close the drawer on click of "Close" button', async () => {
    const mockData = criticalActionsMockData;
    const trackShowAllClick = jest.fn();

    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: mockData,
      isLoading: false,
      error: null,
      criticalActionDataLength: mockData.components.length,
    });
    mockUseCriticalActionLists.mockReturnValue({
      criticalActionsList: mockData.components,
      criticalActionsToShow: mockData.components,
      drawerCriticalActions: mockData.components,
    });
    mockUseOneHomeAnalytics.mockReturnValue({
      trackOneHomeAnalytics: trackShowAllClick,
      isError: false,
      error: null,
    });
    mockUseTheme.mockReturnValue({
      theme: { breakpoints: { base: 0, m: 768, l: 1024, xl: 1280 } },
    });
    mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

    customRender(<CriticalActions />);

    // Click to open the drawer
    fireEvent.click(screen.getByText('Show all'));

    await waitFor(() => {
      expect(screen.getByText('Critical actions')).toBeInTheDocument();
    });

    // Click to close the drawer
    fireEvent.click(screen.getByLabelText('Close'));

    await waitFor(() => {
      expect(screen.queryByText('Critical actions')).not.toBeInTheDocument();
    });
  });

  it('renders different states based on the presence of critical actions', () => {
    let mockData = criticalActionsMockData;
    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: mockData,
      isLoading: false,
      error: null,
      criticalActionDataLength: mockData.components.length,
    });
    mockUseCriticalActionLists.mockReturnValue({
      criticalActionsList: mockData.components,
      criticalActionsToShow: mockData.components,
      drawerCriticalActions: [],
    });
    mockUseOneHomeAnalytics.mockReturnValue({
      trackOneHomeAnalytics: jest.fn(),
      isError: false,
      error: null,
    });
    mockUseTheme.mockReturnValue({
      theme: { breakpoints: { base: 0, m: 768, l: 1024, xl: 1280 } },
    });
    mockUseBreakpoint.mockReturnValue({ matchedDeviceType: 'desktop', matchedBreakpoint: 'xl' });

    customRender(<CriticalActions />);

    expect(screen.getByText('Critical Actions for you')).toBeInTheDocument();
    expect(screen.getByText('Funds on hold')).toBeInTheDocument();
  });

  it('renders different states based on the absence of critical actions', () => {
    let mockData = { ...criticalActionsMockData, components: [] };
    mockUseCriticalActions.mockReturnValue({
      criticalActionsData: mockData,
      isLoading: false,
      error: null,
      criticalActionDataLength: 0,
    });
    mockUseCriticalActionLists.mockReturnValue({
      criticalActionsList: [],
      criticalActionsToShow: [],
      drawerCriticalActions: [],
    });

    customRender(<CriticalActions />);

    expect(
      screen.getByText('There are no actions that need your attention on Razorpay today.'),
    ).toBeInTheDocument();
  });
});
