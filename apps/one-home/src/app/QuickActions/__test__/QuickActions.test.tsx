import React from 'react';
import {
  customRender,
  screen,
  waitFor,
  fireEvent,
} from '@apps/one-home/src/services/test/test-utils';
import { useNavigate } from 'react-router-dom';
import { useTheme, useBreakpoint } from '@razorpay/blade/utils';
import QuickActions from '../QuickActions';
import useQuickActions from '../useQuickActions';
import mockData, { connectedProductsMock } from './mockData';
import { messages } from '../constants';

jest.mock('../useQuickActions', () => ({
  __esModule: true,
  default: jest.fn(),
}));

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: jest.fn(),
}));

jest.mock('@razorpay/blade/utils', () => ({
  useTheme: jest.fn(),
  useBreakpoint: jest.fn(),
}));

jest.mock('@federated/apps/shell/connected-navigation/hooks', () => ({
  useTopNavigationData: jest.fn(),
}));

const mockUseQuickActions = useQuickActions as jest.Mock;
const mockNavigate = jest.fn();
const mockedUseTheme = useTheme as jest.Mock;
const mockedUseBreakpoint = useBreakpoint as jest.Mock;
const mockUseTopNavigationData = require('@federated/apps/shell/connected-navigation/hooks')
  .useTopNavigationData as jest.Mock;

jest.mock('@apps/one-home/src/hooks/useOneHomeAnalytics', () => {
  return {
    __esModule: true,
    default: jest.fn(() => ({
      trackOneHomeAnalytics: jest.fn(),
    })),
  };
});

describe('QuickActions Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (useNavigate as jest.Mock).mockReturnValue(mockNavigate);

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
      matchedDeviceType: 'mobile',
    });

    mockUseQuickActions.mockReset();
    mockUseTopNavigationData.mockReset();
  });

  //   it('renders skeletons while data is loading', () => {
  //     mockUseQuickActions.mockReturnValue({
  //       isFetching: true,
  //       data: undefined,
  //       error: null,
  //     });

  //     render(
  //           <QuickActions />
  //     );

  //     expect(screen.getAllByTestId('skeleton-loader')).toHaveLength(2);
  //   });

  it('renders Quick Actions with fetched data for payments and banking', async () => {
    mockUseQuickActions.mockImplementation((entity) => {
      if (entity === 'payments_quick_action_item') {
        return {
          isFetching: false,
          data: mockData.paymentsQuickActionMock,
          error: null,
        };
      } else if (entity === 'banking_quick_action_item') {
        return {
          isFetching: false,
          data: mockData.bankingQuickActionMock,
          error: null,
        };
      } else {
        return { isFetching: false, data: null, error: null };
      }
    });

    mockUseTopNavigationData.mockReturnValue({
      products: connectedProductsMock,
      isLoading: false,
      error: null,
    });

    customRender(<QuickActions />);

    await waitFor(() => {
      expect(screen.getByText(messages.quickActionsPayments.title)).toBeInTheDocument();
      expect(screen.getByText(messages.quickActionsBanking.title)).toBeInTheDocument();
      expect(screen.getByText(messages.quickActionsConnectedProducts.title)).toBeInTheDocument();
    });
  });

  it('renders the error fallback for payments when quick actions data for payment fetch fails', async () => {
    mockUseQuickActions.mockImplementation((entity) => {
      return entity === 'payments_quick_action_item'
        ? {
            isFetching: false,
            data: mockData.paymentsQuickActionMock,
            error: new Error(),
          }
        : {
            isFetching: false,
            data: mockData.bankingQuickActionMock,
            error: null,
          };
    });

    mockUseTopNavigationData.mockReturnValue({
      products: connectedProductsMock,
      isLoading: false,
      error: null,
    });

    customRender(<QuickActions />);

    await waitFor(() => {
      const errorElements = screen.getAllByText(messages.quickActionsSection.errorMessage!);
      expect(errorElements.length).toBe(1);
      expect(screen.getByText(messages.quickActionsBanking.title)).toBeInTheDocument();
      expect(screen.getByText(messages.quickActionsConnectedProducts.title)).toBeInTheDocument();
    });
  });

  it('renders the Cards and navigates on click', async () => {
    mockUseQuickActions.mockImplementation((entity) => {
      return {
        isFetching: false,
        data:
          entity === 'payments_quick_action_item'
            ? mockData.paymentsQuickActionMock
            : mockData.bankingQuickActionMock,
        error: null,
      };
    });

    mockUseTopNavigationData.mockReturnValue({
      products: connectedProductsMock,
      isLoading: false,
      error: null,
    });

    const { getByLabelText } = customRender(<QuickActions />);

    expect(screen.getByText('Home')).toBeInTheDocument();
    expect(screen.getByText('Transactions')).toBeInTheDocument();
    expect(screen.getByText('Settlements')).toBeInTheDocument();

    fireEvent.click(getByLabelText(/Home/i));

    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith('/settings/home');
    });

    fireEvent.click(getByLabelText(/Transactions/i));

    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith('/settings/transactions');
    });
  });

  it('should not render connected products quick actions in desktop mode', () => {
    mockedUseBreakpoint.mockReturnValue({
      matchedDeviceType: 'desktop',
    });
    mockUseQuickActions.mockImplementation((entity) => {
      return {
        isFetching: false,
        data:
          entity === 'payments_quick_action_item'
            ? mockData.paymentsQuickActionMock
            : mockData.bankingQuickActionMock,
        error: null,
      };
    });

    mockUseTopNavigationData.mockReturnValue({
      products: connectedProductsMock,
      isLoading: false,
      error: null,
    });

    customRender(<QuickActions />);

    expect(
      screen.queryByText(messages.quickActionsConnectedProducts.title),
    ).not.toBeInTheDocument();
  });

  describe('connected products', () => {
    it('should be visible only in case of mobile', () => {
      mockUseQuickActions.mockImplementation((entity) => {
        return {
          isFetching: false,
          data:
            entity === 'payments_quick_action_item'
              ? mockData.paymentsQuickActionMock
              : mockData.bankingQuickActionMock,
          error: null,
        };
      });

      mockUseTopNavigationData.mockReturnValue({
        products: connectedProductsMock,
        isLoading: false,
        error: null,
      });

      customRender(<QuickActions />);

      expect(screen.getByText(messages.quickActionsConnectedProducts.title)).toBeInTheDocument();
      expect(screen.getByText('Payments')).toBeInTheDocument();
      expect(screen.getByText('Partners')).toBeInTheDocument();
      expect(screen.getByText('Banking')).toBeInTheDocument();
      expect(screen.getByText('Payroll')).toBeInTheDocument();
    });

    it('should navigate to /dashboard path when clicked on Payments', async () => {
      mockUseQuickActions.mockImplementation((entity) => {
        return {
          isFetching: false,
          data:
            entity === 'payments_quick_action_item'
              ? mockData.paymentsQuickActionMock
              : mockData.bankingQuickActionMock,
          error: null,
        };
      });

      mockUseTopNavigationData.mockReturnValue({
        products: connectedProductsMock,
        isLoading: false,
        error: null,
      });

      const { getByLabelText } = customRender(<QuickActions />);

      expect(screen.getByText(messages.quickActionsConnectedProducts.title)).toBeInTheDocument();
      expect(screen.getByText('Payments')).toBeInTheDocument();

      fireEvent.click(getByLabelText('Payments'));
      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/dashboard');
      });
    });

    it('should navigate to /partners path when clicked on Partners', async () => {
      mockUseQuickActions.mockImplementation((entity) => {
        return {
          isFetching: false,
          data:
            entity === 'payments_quick_action_item'
              ? mockData.paymentsQuickActionMock
              : mockData.bankingQuickActionMock,
          error: null,
        };
      });

      mockUseTopNavigationData.mockReturnValue({
        products: connectedProductsMock,
        isLoading: false,
        error: null,
      });

      const { getByLabelText } = customRender(<QuickActions />);

      expect(screen.getByText(messages.quickActionsConnectedProducts.title)).toBeInTheDocument();
      expect(screen.getByText('Partners')).toBeInTheDocument();

      fireEvent.click(getByLabelText('Partners'));
      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/partners');
      });
    });

    it('should navigate to /banking path when clicked on Banking', async () => {
      mockUseQuickActions.mockImplementation((entity) => {
        return {
          isFetching: false,
          data:
            entity === 'payments_quick_action_item'
              ? mockData.paymentsQuickActionMock
              : mockData.bankingQuickActionMock,
          error: null,
        };
      });

      mockUseTopNavigationData.mockReturnValue({
        products: connectedProductsMock,
        isLoading: false,
        error: null,
      });

      const { getByLabelText } = customRender(<QuickActions />);

      expect(screen.getByText(messages.quickActionsConnectedProducts.title)).toBeInTheDocument();

      fireEvent.click(getByLabelText('Banking'));
      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/banking');
      });
    });

    it('should navigate to /payroll path when clicked on Payroll', async () => {
      mockUseQuickActions.mockImplementation((entity) => {
        return {
          isFetching: false,
          data:
            entity === 'payments_quick_action_item'
              ? mockData.paymentsQuickActionMock
              : mockData.bankingQuickActionMock,
          error: null,
        };
      });

      mockUseTopNavigationData.mockReturnValue({
        products: connectedProductsMock,
        isLoading: false,
        error: null,
      });

      const { getByLabelText } = customRender(<QuickActions />);

      expect(screen.getByText(messages.quickActionsConnectedProducts.title)).toBeInTheDocument();

      fireEvent.click(getByLabelText('Payroll'));
      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/payroll');
      });
    });
  });
});
