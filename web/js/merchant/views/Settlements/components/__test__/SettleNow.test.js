import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SettleNow from 'merchant/views/Settlements/components/SettleNow';
import { render, screen, waitFor } from 'test-utils';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import * as modals from 'merchant_common/reducers/modals';
import * as fetchRestriction from 'merchant/reducers/home';
import * as gaEvents from 'merchant/views/Settlements/Settlements/ga';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';

jest.mock('merchant/views/Capital/CashAdvanceNudges/components/LocRepaymentTooltip', () => () => (
  <div>Loc Repayment Tooltip</div>
));

const state = {
  session: {
    user: {
      isFeatureEnabled: () => false,
    },
    mode: 'live',
    org: {},
  },
  home: {
    current_balance: {
      data: {
        id: 'G8q6Bh9LimvyMq',
        balance: 0,
      },
      loading: false,
    },
    ondemand_restrictions: {
      data: {},
    },
  },
  instantSettlements: {
    items: [],
  },
};

describe('SettleNow', () => {
  const modalsSpy = jest.spyOn(modals, 'openModal');
  const fetchRestrictionSpy = jest.spyOn(fetchRestriction, 'fetchOndemandRestrictions');
  const gaEventSpy = jest.spyOn(gaEvents.trackOndemand, 'trackSettleNow');

  const App = ({ initialState = state, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <SettleNow {...rest} />
      </Provider>
    );
  };

  beforeEach(() => {
    modalsSpy.mockClear();
    fetchRestrictionSpy.mockClear();
    gaEventSpy.mockClear();
  });

  test('should call fetchOndemandRestrictions on mount', async () => {
    const initialState = {
      ...state,
      session: {
        user: {
          isFeatureEnabled: () => true,
        },
      },
    };
    render(<App initialState={initialState} />);
    await waitFor(() => {
      expect(fetchRestrictionSpy).toHaveBeenCalledTimes(1);
    });
  });

  describe('Settle Now button', () => {
    test('should render settle now button and thunder icon', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isFeatureEnabled: () => true,
          },
          mode: 'live',
        },
        home: {
          ...state.home,
          ondemand_restrictions: {
            data: {
              attempts_left: true,
              settlable_amount: true,
            },
          },
        },
      };
      render(<App initialState={initialState} />);
      expect(screen.getByText('Settle Now')).toBeInTheDocument();
      expect(screen.getByText('Settle Now').parentElement).toHaveClass('box-left-pad10-inline');
      expect(screen.getByRole('img')).toHaveAttribute('alt', 'settle-now-thunder');
      expect(screen.getByRole('img')).toHaveAttribute('class', 'settlement-icon-thunder');
    });

    test('should call open ondemand modal and trackSettleNow on settle now click', async () => {
      const initialState = {
        ...state,
        home: {
          current_balance: {
            data: {
              id: 'G8q6Bh9LimvyMq',
              balance: -150,
            },
            loading: false,
          },
          ondemand_restrictions: {
            data: {},
          },
        },
      };
      render(<App initialState={initialState} checkIfFirstEverSettlement={() => {}} />);
      userEvent.click(screen.getByText('Settle Now'));
      await waitFor(() => {
        expect(gaEventSpy).toHaveBeenCalledTimes(1);
        expect(gaEventSpy).toHaveBeenCalledWith('Settlements');
        expect(modalsSpy).toHaveBeenCalledTimes(1);
      });
    });

    test('should render settle now without border', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isFeatureEnabled: () => true,
          },
          mode: 'live',
        },
        home: {
          ...state.home,
          ondemand_restrictions: {
            data: {
              attempts_left: true,
              settlable_amount: true,
            },
          },
        },
      };
      render(<App initialState={initialState} showLeftBorder={false} />);
      expect(screen.getByText('Settle Now')).toBeInTheDocument();
      expect(screen.getByText('Settle Now').parentElement).not.toHaveClass('box-left-pad10-inline');
      expect(screen.getByRole('img')).toHaveAttribute('alt', 'settle-now-thunder');
      expect(screen.getByRole('img')).toHaveAttribute('class', 'settlement-icon-thunder');
    });

    test('should disable CTA when global limit breached', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isFeatureEnabled: () => true,
          },
          mode: 'live',
        },
        settlement: {
          settleNowButtonDisabled: {
            loading: false,
            data: { disable: true },
          },
        },
      };
      render(<App initialState={initialState} showLeftBorder={false} />);
      expect(screen.getByRole('button', { name: /settle now/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /settle now/i })).toBeDisabled();
      expect(
        screen.getByText(
          /On-demand Settlements are being limited due to high usage. Please try again the next working day./i,
        ),
      ).toBeInTheDocument();
    });
  });

  describe('Restriction Message', () => {
    const restriction = {
      home: {
        ...state.home,
        ondemand_restrictions: {
          data: {
            attempts_left: true,
            settlable_amount: true,
          },
        },
      },
    };

    test('should render repayment completion message ', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isFeatureEnabled: () => true,
          },
          mode: 'live',
        },
        ...restriction,
      };
      const { rerender } = render(<App initialState={initialState} />);
      expect(
        screen.getByText(
          'On-demand Instant Settlements have been disabled because you have delayed the repayments on',
        ),
      ).toBeInTheDocument();

      initialState.session.user.isFeatureEnabled = (feature) =>
        feature === 'disable_ondemand_for_loan';
      rerender(<App initialState={initialState} />);
      expect(screen.getByText('Loan.')).toBeInTheDocument();
      expect(
        screen.getByText(
          'On-demand Instant Settlements have been disabled because you have delayed the repayments on',
        ),
      ).toBeInTheDocument();
    });

    test('should render Loc repayment tooltip', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isFeatureEnabled: (feature) => {
              return feature === 'disable_ondemand_for_loc';
            },
          },
          mode: 'live',
        },
        ...restriction,
      };
      render(<App initialState={initialState} />);
      expect(screen.getByText('Loc Repayment Tooltip')).toBeInTheDocument();
    });

    test('should render temporary offline message when isNodalAccountLowBalanceBlocked is true', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isFeatureEnabled: () => false,
          },
          mode: 'live',
        },
        ...restriction,
      };
      render(<App initialState={initialState} isNodalAccountLowBalanceBlocked={true} />);
      expect(
        screen.getByText('We are temporarily offline. Will be back soon!'),
      ).toBeInTheDocument();
    });

    test('should render maximum allowed limit based on attempts_left and settlable_amount', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isFeatureEnabled: () => false,
            isOndemandSettlementsRestricted: true,
          },
          mode: 'live',
        },
        home: {
          ...state.home,
          ondemand_restrictions: {
            data: {
              attempts_left: false,
              settlable_amount: false,
              max_amount_limit: 500,
            },
          },
        },
      };
      const { rerender } = render(<App initialState={initialState} />);
      expect(
        screen.getByText(
          `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
            initialState.home.ondemand_restrictions.data.max_amount_limit,
            true,
          )} for the day.`,
        ),
      ).toBeInTheDocument();

      initialState.home.ondemand_restrictions.data = {
        attempts_left: false,
        settlable_amount: true,
        settlements_count_limit: 500,
      };
      rerender(<App initialState={initialState} />);
      expect(
        screen.getByText(
          `You've already settled your maximum allowed limit of ${initialState.home.ondemand_restrictions.data.settlements_count_limit} times for the day.`,
        ),
      ).toBeInTheDocument();

      initialState.home.ondemand_restrictions.data = {
        attempts_left: true,
        settlable_amount: true,
      };
      rerender(<App initialState={initialState} />);
      expect(
        screen.queryByText(
          'On-demand Instant Settlements have been disabled because you have delayed the repayments on',
        ),
      ).not.toBeInTheDocument();

      initialState.home.ondemand_restrictions.data = {
        attempts_left: true,
        settlable_amount: false,
        max_amount_limit: 500,
      };
      rerender(<App initialState={initialState} />);
      expect(
        screen.getByText(
          `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
            initialState.home.ondemand_restrictions.data.max_amount_limit,
            true,
          )} for the day.`,
        ),
      ).toBeInTheDocument();
    });
  });
});
