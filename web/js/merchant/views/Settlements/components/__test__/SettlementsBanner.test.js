import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SettlementsBanner from 'merchant/views/Settlements/components/SettlementsBanner';
import { render, screen, waitFor } from 'test-utils';
import userEvent from '@testing-library/user-event';
import { Router } from 'react-router-dom';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { createMemoryHistory } from 'history';
import * as modals from 'merchant_common/reducers/modals';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

jest.mock(
  'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage',
  () => () => <div>SettlementMessage</div>,
);

const state = {
  session: {
    user: {},
    mode: 'live',
  },
  home: {
    current_balance: { data: {} },
    settlement_amount: { data: {} },
  },
  settlement: {
    holidayList: { data: {} },
    config: { data: {} },
  },
  config: {
    config: {},
  },
  payments: {
    items: [],
  },
  profile: {
    bankAccountChangeStatus: false,
  },
};

describe('SettlementsBanner', () => {
  const modalsSpy = jest.spyOn(modals, 'closeModal');

  const history = createMemoryHistory();
  history.push = jest.fn();
  window.open = jest.fn();

  const App = ({ initialState = state, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <Router history={history}>
          <SettlementsBanner {...rest} />
        </Router>
      </Provider>
    );
  };

  beforeEach(() => {
    modalsSpy.mockClear();
  });

  test('should render banner when in live mode and user KYC has not been submitted or is under review', async () => {
    let initialState = {
      ...state,
      session: {
        ...state.session,
        user: {
          isActivated: true,
        },
      },
    };
    const { rerender } = render(<App initialState={initialState} />);
    expect(
      screen.getByText('Your settlements are currently not being processed'),
    ).toBeInTheDocument();
    expect(screen.getByLabelText('settlement-banner')).toHaveClass('highlight-warning');
    expect(
      screen.getByText('Settlements will be processed. Once your KYC is submitted and approved.'),
    ).toBeInTheDocument();

    initialState = {
      ...state,
      session: {
        ...state.session,
        user: {
          isActivated: true,
          activation_status: 'under_review',
          isActivationFormFullView: false,
        },
      },
    };
    rerender(<App initialState={initialState} />);
    expect(screen.getByText('from the first transaction')).toBeInTheDocument();
    userEvent.click(screen.getByText(/Complete KYC/i));
    await waitFor(() => {
      expect(history.push).toHaveBeenCalledTimes(1);
      expect(history.push).toHaveBeenCalledWith('/activation');
    });

    initialState = {
      ...state,
      session: {
        ...state.session,
        user: {
          isActivated: true,
          activation_status: 'under_review',
          isActivationFormFullView: true,
        },
      },
    };
    rerender(<App initialState={initialState} />);
    userEvent.click(screen.getByText(/Complete KYC/));
    await waitFor(() => {
      expect(history.push).toHaveBeenCalledTimes(2);
      expect(history.push).toHaveBeenCalledWith('/kyc');
    });

    initialState = {
      ...state,
      session: {
        ...state.session,
        user: {
          isActivated: true,
          activation_status: 'under_review',
          isActivationFormFullView: true,
          user: {
            ...state.session.user.user,
            signup_campaign: EASY_ONBOARDING,
          },
        },
      },
    };
    rerender(<App initialState={initialState} />);
    userEvent.click(screen.getByText(/Complete KYC/));
    await waitFor(() => {
      expect(history.push).toHaveBeenCalledWith('');
      expect(window.open).toHaveBeenCalledWith(window.EASY_ONBOARDING_URL, '_self', 'noopener');
    });
  });

  test('should render nothing when in test mode and user KYC has not been submitted or is under review', () => {
    const initialState = {
      ...state,
      session: {
        mode: 'test',
        user: {
          isActivated: true,
        },
      },
    };
    render(<App initialState={initialState} />);
    expect(screen.queryByLabelText('settlement-banner')).not.toBeInTheDocument();
  });

  test('should render banner when user is put on funds on hold', () => {
    const initialState = {
      ...state,
      home: {
        ...state.home,
        settlement_amount: {
          data: {
            no_settlement: { on_hold: true },
          },
        },
      },
    };
    render(<App initialState={initialState} />);
    expect(screen.getByText('Your settlements are under review')).toBeInTheDocument();
    expect(screen.getByLabelText('settlement-banner')).toHaveClass('highlight-error');
    expect(
      screen.getByText(
        'Your settlements are currently not being processed due to some risk issues with your payments or with your razorpay account.',
      ),
    ).toBeInTheDocument();
  });

  test("should call close modal, rzpAnalytics and createTicketEmitter when user's funds on hold", async () => {
    window.rzpTicketSystem = true;
    const initialState = {
      ...state,
      home: {
        ...state.home,
        settlement_amount: {
          data: {
            no_settlement: { on_hold: true },
          },
        },
      },
    };
    render(<App initialState={initialState} />);
    userEvent.click(screen.getByText('Contact support'));
    await waitFor(() => {
      expect(modalsSpy).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledTimes(1);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventCategory: 'Settlement Revamp',
        eventAction: 'Contact Support',
        eventLabel: `Settlements`,
      });
      expect(CreateTicketEmitter.emit).toHaveBeenCalledTimes(1);
      expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
    });
  });

  test('should render banner when user is put on NSS hold funds', () => {
    const initialState = {
      ...state,
      settlement: {
        config: {
          data: {
            config: {
              features: {
                hold: {
                  status: true,
                },
              },
            },
          },
        },
      },
    };
    const { rerender } = render(<App initialState={initialState} />);
    expect(
      screen.getByText('Your settlements have been put on temporary hold'),
    ).toBeInTheDocument();
    expect(screen.getByLabelText('settlement-banner')).toHaveClass('highlight-error');
    expect(
      screen.getByText(
        'Your settlements are currently not being processed due to some issues with your bank account. We will not be able to process further settlements until the bank account details are updated from your end.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Update Bank Account Details')).toBeInTheDocument();

    initialState.profile.bankAccountChangeStatus = true;
    rerender(<App initialState={initialState} />);
    expect(screen.getByText('bank account details is under review.')).toBeInTheDocument();
    expect(screen.getByText('View Bank Account Details')).toBeInTheDocument();
  });

  test('should not call createTicketEmitter when rzpTicketSystem is false', async () => {
    window.rzpTicketSystem = false;
    const initialState = {
      ...state,
      home: {
        ...state.home,
        settlement_amount: {
          data: {
            no_settlement: { on_hold: true },
          },
        },
      },
    };
    render(<App initialState={initialState} />);
    userEvent.click(screen.getByText('Contact support'));
    await waitFor(() => {
      expect(modalsSpy).toHaveBeenCalledTimes(1);
      expect(CreateTicketEmitter.emit).toHaveBeenCalledTimes(0);
    });
  });

  test('should render settlement message when ondemand settlement enabled', () => {
    const initialState = {
      ...state,
      session: {
        user: {
          isOndemandSettlementEnabled: true,
        },
      },
    };
    render(<App initialState={initialState} />);
    expect(screen.getByText('SettlementMessage')).toBeInTheDocument();
  });
});
