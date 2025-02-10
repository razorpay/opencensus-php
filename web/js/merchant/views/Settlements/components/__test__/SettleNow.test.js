import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';

import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import * as fetchRestriction from 'merchant/reducers/home';
import { storeWithInitialState } from 'merchant/store';
import { getOdsValidateHandler } from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/handlers';
import * as apiHandlers from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import * as gaEvents from 'merchant/views/Settlements/Settlements/ga';
import SettleNow from 'merchant/views/Settlements/components/SettleNow';
import * as modals from 'merchant_common/reducers/modals';
import { render, screen, server, waitFor, queryClient } from 'test-utils';

const waitForODSConfigLoading = async () => {
  await waitFor(() => {
    expect(screen.queryByText('Loading...')).not.toBeInTheDocument();
  });
};

jest.mock('merchant/views/Capital/CashAdvanceNudges/components/LocRepaymentTooltip', () => () => (
  <div>Loc Repayment Tooltip</div>
));

const state = {
  session: {
    user: {
      isFeatureEnabled: () => false,
      merchant: {
        currency: 'INR',
      },
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
    queryClient.clear();
  });

  test('should call fetchOndemandRestrictions on mount', async () => {
    const initialState = {
      ...state,
      session: {
        user: {
          ...state.session.user,
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
            ...state.session.user,
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
      server.use(apiHandlers.odsConfigNoBreachHandler);
      render(<App initialState={initialState} checkIfFirstEverSettlement={() => {}} />);
      await waitForODSConfigLoading();
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
            ...state.session.user,
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
  });

  describe('Restriction Message', () => {
    beforeEach(() => {
      server.use(apiHandlers.odsConfigNoBreachHandler);
    });
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

    test('should render repayment completion message ', async () => {
      const initialState = {
        ...state,
        session: {
          user: {
            ...state.session.user,
            isFeatureEnabled: () => true,
          },
          mode: 'live',
        },
        ...restriction,
      };
      const { rerender } = render(<App initialState={initialState} />);
      await waitForODSConfigLoading();
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

    test('should render Loc repayment tooltip', async () => {
      const initialState = {
        ...state,
        session: {
          user: {
            ...state.session.user,
            isFeatureEnabled: (feature) => {
              return feature === 'disable_ondemand_for_loc';
            },
          },
          mode: 'live',
        },
        ...restriction,
      };
      render(<App initialState={initialState} />);
      await waitForODSConfigLoading();
      expect(screen.getByText('Loc Repayment Tooltip')).toBeInTheDocument();
    });

    test('should render temporary offline message when isNodalAccountLowBalanceBlocked is true', async () => {
      const initialState = {
        ...state,
        session: {
          user: {
            ...state.session.user,
            isFeatureEnabled: () => false,
          },
          mode: 'live',
        },
        ...restriction,
      };

      server.use(apiHandlers.odsConfigBlockedHandler);
      render(<App initialState={initialState} />);
      await waitForODSConfigLoading();
      expect(
        screen.getByText('We are temporarily offline. Will be back soon!'),
      ).toBeInTheDocument();
    });

    describe('based on attempts_left and settlable_amount', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            ...state.session.user,
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
      test('should render maximum allowed limit ', async () => {
        server.use(getOdsValidateHandler(initialState.home.ondemand_restrictions.data));
        render(<App initialState={initialState} />);
        await waitForODSConfigLoading();
        expect(
          screen.getByText(
            `You’ve already settled your maximum allowed limit of ${getFormattedAmountNew(
              initialState.home.ondemand_restrictions.data.max_amount_limit,
              true,
            )} for the day.`,
          ),
        ).toBeInTheDocument();
      });

      test('should render maximum allowed attempts count ', async () => {
        initialState.home.ondemand_restrictions.data = {
          attempts_left: false,
          settlable_amount: true,
          settlements_count_limit: 500,
        };
        server.use(getOdsValidateHandler(initialState.home.ondemand_restrictions.data));
        render(<App initialState={initialState} />);
        await waitForODSConfigLoading();
        expect(
          screen.getByText(
            `You've already settled your maximum allowed limit of ${initialState.home.ondemand_restrictions.data.settlements_count_limit} times for the day.`,
          ),
        ).toBeInTheDocument();
      });

      test('repayments in capital products', async () => {
        initialState.home.ondemand_restrictions.data = {
          attempts_left: true,
          settlable_amount: true,
        };
        server.use(getOdsValidateHandler(initialState.home.ondemand_restrictions.data));
        render(<App initialState={initialState} />);
        await waitForODSConfigLoading();
        expect(
          screen.queryByText(
            'On-demand Instant Settlements have been disabled because you have delayed the repayments on',
          ),
        ).not.toBeInTheDocument();
      });

      test('should render maximum allowed limit ', async () => {
        initialState.home.ondemand_restrictions.data = {
          attempts_left: true,
          settlable_amount: false,
          max_amount_limit: 500,
        };
        server.use(getOdsValidateHandler(initialState.home.ondemand_restrictions.data));
        render(<App initialState={initialState} />);
        await waitForODSConfigLoading();
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

    test('should disable CTA when global limit breached', async () => {
      const initialState = {
        ...state,
        session: {
          user: {
            ...state.session.user,
            isFeatureEnabled: () => true,
          },
          mode: 'live',
        },
      };
      server.use(apiHandlers.odsConfigGlobalLimitBreachedHandler);
      render(<App initialState={initialState} showLeftBorder={false} />, {
        includeQueryProvider: true,
      });
      expect(screen.getByRole('button', { name: /settle now/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /settle now/i })).toBeDisabled();
      await waitForODSConfigLoading();
      expect(
        screen.getByText(
          /On-demand Settlements are being limited due to high usage. Please try again the next working day./i,
        ),
      ).toBeInTheDocument();
    });

    test('should disable CTA when merchant limit breached for non es restricted merchants', async () => {
      const initialState = {
        ...state,
        session: {
          user: {
            ...state.session.user,
            isOndemandSettlementsRestricted: false,
          },
          mode: 'live',
        },
      };
      server.use(apiHandlers.odsConfigMerchantLimitBreachedHandler);
      render(<App initialState={initialState} showLeftBorder={false} />, {
        includeQueryProvider: true,
      });
      expect(screen.getByRole('button', { name: /settle now/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /settle now/i })).toBeDisabled();
      await waitForODSConfigLoading();
      expect(
        screen.getByText(/You’ve already settled your maximum allowed limit of./i),
      ).toBeInTheDocument();
      expect(screen.getByText(/100/i)).toBeInTheDocument();
      expect(screen.getByText(/for the day./i)).toBeInTheDocument();
    });

    test('Edge case: should disable CTA and render es restricted tooltip for es restricted merchants with mid limit breached', async () => {
      const initialState = {
        ...state,
        session: {
          user: {
            ...state.session.user,
            isFeatureEnabled: () => false,
            isOndemandSettlementsRestricted: true,
          },
          mode: 'live',
        },
        home: {
          ...state.home,
          ondemand_restrictions: {
            data: {
              attempts_left: 10,
              settlable_amount: 0,
              max_amount_limit: 567098,
            },
          },
        },
      };
      server.use(getOdsValidateHandler(initialState.home.ondemand_restrictions.data));
      server.use(apiHandlers.odsConfigMerchantLimitBreachedHandler);
      render(<App initialState={initialState} />);
      await waitForODSConfigLoading();
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
