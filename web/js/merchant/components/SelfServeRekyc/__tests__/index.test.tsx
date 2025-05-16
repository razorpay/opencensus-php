import React from 'react';
import { render, screen } from '@testing-library/react';
import { Provider } from 'react-redux';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import SelfServeRekycNotification from '../index';
import { useGetRekycDetails } from '../hooks/useGetRekycDetails';
import { 
  DYNAMIC_REKYC_STATUS, 
  STATUSES_TO_SHOW_TIMELINE,
  FINAL_STEPS_MAP,
  OWNER_REKYC_BANNER_INFO
} from '../constants';
import * as utils from '../utils';

jest.mock('../hooks/useGetRekycDetails');
jest.mock('../components/RekycBanner', () => ({
  __esModule: true,
  default: ({ bannerDetails, deadlineDate, daysFromDeadline, stepsInfo }) => (
    <div data-testid="rekyc-banner">
      <div data-testid="banner-details">{JSON.stringify(bannerDetails)}</div>
      <div data-testid="deadline-date">{deadlineDate}</div>
      <div data-testid="days-from-deadline">{daysFromDeadline}</div>
      {stepsInfo && <div data-testid="steps-info">{JSON.stringify(stepsInfo, (key, value) => {
        // Handle function serialization
        if (typeof value === 'function') {
          return '[Function]';
        }
        return value;
      })}</div>}
    </div>
  )
}));

jest.mock('../containers/RekycModalWrapper', () => ({
  __esModule: true,
  default: ({ modalInfo, deadlineDate, daysFromDeadline }) => (
    <div data-testid="rekyc-modal">
      <div data-testid="modal-info">{JSON.stringify(modalInfo)}</div>
      <div data-testid="modal-deadline-date">{deadlineDate}</div>
      <div data-testid="modal-days-from-deadline">{daysFromDeadline}</div>
    </div>
  )
}));

// Mock localStorage
const mockLocalStorage = {
  getItem: jest.fn(),
  removeItem: jest.fn(),
};
Object.defineProperty(window, 'localStorage', { value: mockLocalStorage });

describe('SelfServeRekycNotification', () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });

  const mockStore = {
    getState: () => ({
      session: {
        user: {
          merchant: { id: 'test-merchant-id' },
          role: 'owner'
        }
      }
    }),
    subscribe: jest.fn(),
    dispatch: jest.fn(),
  };

  const renderComponent = () => {
    return render(
      <Provider store={mockStore}>
        <QueryClientProvider client={queryClient}>
          <SelfServeRekycNotification />
        </QueryClientProvider>
      </Provider>
    );
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockLocalStorage.getItem.mockReset();
    mockLocalStorage.removeItem.mockReset();
    jest.spyOn(utils, 'shouldShowModal').mockReturnValue(true);
    jest.spyOn(utils, 'getTimelineString').mockReturnValue('firstThirtyDays');
  });

  it('renders banner when rekyc details are available', () => {
    const status = 'needsClarification';
    const mockRekycDetails = {
      status,
      deadline: Date.now() + 7 * 24 * 60 * 60 * 1000,
    };

    (useGetRekycDetails as jest.Mock).mockReturnValue({
      data: mockRekycDetails,
    });

    renderComponent();

    expect(screen.getByTestId('rekyc-banner')).toBeInTheDocument();
    const bannerDetails = JSON.parse(screen.getByTestId('banner-details').textContent || '');
    expect(bannerDetails.heading).toBe(OWNER_REKYC_BANNER_INFO.needsClarification.firstThirtyDays.heading);
  });

  it('renders modal for actionable status when shouldShowModal is true', () => {
    const status = DYNAMIC_REKYC_STATUS[0];
    const mockRekycDetails = {
      status,
      deadline: Date.now() + 7 * 24 * 60 * 60 * 1000,
    };

    (useGetRekycDetails as jest.Mock).mockReturnValue({
      data: mockRekycDetails,
    });

    renderComponent();

    expect(screen.getByTestId('rekyc-modal')).toBeInTheDocument();
  });

  it('shows timeline for status in STATUSES_TO_SHOW_TIMELINE', () => {
    const status = STATUSES_TO_SHOW_TIMELINE[0];
    const mockRekycDetails = {
      status,
      deadline: Date.now() + 7 * 24 * 60 * 60 * 1000,
    };

    (useGetRekycDetails as jest.Mock).mockReturnValue({
      data: mockRekycDetails,
    });

    renderComponent();

    expect(screen.getByTestId('rekyc-banner')).toBeInTheDocument();
    expect(screen.getByTestId('steps-info')).toBeInTheDocument();
    const stepsInfo = JSON.parse(screen.getByTestId('steps-info').textContent || '');

    const expectedStepsInfo = JSON.parse(
      JSON.stringify(
        FINAL_STEPS_MAP[status].firstThirtyDays,
        (key, value) => typeof value === 'function' ? '[Function]' : value
      )
    );

    expect(stepsInfo).toEqual(expectedStepsInfo);
  });

  it('handles non-owner role correctly', () => {
    const nonOwnerStore = {
      ...mockStore,
      getState: () => ({
        session: {
          user: {
            merchant: { id: 'test-merchant-id' },
            role: 'ops'
          }
        }
      })
    };

    const status = DYNAMIC_REKYC_STATUS[0];
    const mockRekycDetails = {
      status,
      deadline: Date.now() + 7 * 24 * 60 * 60 * 1000,
    };

    (useGetRekycDetails as jest.Mock).mockReturnValue({
      data: mockRekycDetails,
    });

    render(
      <Provider store={nonOwnerStore}>
        <QueryClientProvider client={queryClient}>
          <SelfServeRekycNotification />
        </QueryClientProvider>
      </Provider>
    );

    const bannerDetails = JSON.parse(screen.getByTestId('banner-details').textContent || '');
    expect(bannerDetails.heading).toBe('Notify the account owner to update KYC');
  });

  it('does not render banner or modal when no rekyc details', () => {
    (useGetRekycDetails as jest.Mock).mockReturnValue({
      data: null,
    });

    renderComponent();

    expect(screen.queryByTestId('rekyc-banner')).not.toBeInTheDocument();
    expect(screen.queryByTestId('rekyc-modal')).not.toBeInTheDocument();
  });
});