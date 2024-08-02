import React from 'react';

import { queryClient } from 'common/components/Bootstrap/Wrapper';
import * as settlementActions from 'merchant/reducers/home';
import * as apiHandlers from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/__test__/mocks/odsApiHandlers';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import {
  defaultProps,
  user,
  patchKeyEvent,
} from 'merchant/views/Settlements/Settlements/components/Modals/__test__/mocks/fixtures/OndemandModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import { userEvent, render, screen, waitFor, server, delay } from 'test-utils';

const waitForConfigLoader = async () => {
  expect(
    screen.getByRole('progressbar', {
      name: /checking balance/i,
    }),
  ).toBeInTheDocument();
  await waitFor(() => {
    expect(
      screen.queryByRole('progressbar', {
        name: /checking balance/i,
      }),
    ).not.toBeInTheDocument();
  });
  await delay(); // was being used previously, else "Settlement confirmation" tests failing
};

describe('OndemandModal.js', () => {
  const closeModalsSpy = jest.spyOn(ModalActions, 'closeModal');
  const fetchCurrentBalanceSpy = jest.spyOn(settlementActions, 'fetchCurrentBalance');
  const fetchOndemandRestrictionsSpy = jest.spyOn(settlementActions, 'fetchOndemandRestrictions');

  beforeEach(() => {
    server.use(apiHandlers.odsConfigNoBreachHandler);
    closeModalsSpy.mockClear();
    fetchCurrentBalanceSpy.mockClear();
    fetchOndemandRestrictionsSpy.mockClear();
    queryClient.clear();

    window.rzpAnalytics.mockReset();
    window.rzp_user = {
      mode: 'test',
    };
  });

  beforeAll(() => {
    document.addEventListener('keydown', patchKeyEvent, { capture: true });
  });

  const state = {
    session: {
      user: {
        isOndemandSettlementEnabled: true,
        isOndemandSettlementsRestricted: false,
        merchant: { currency: 'INR' },
      },
      mode: 'live',
      org: {},
    },
  };

  const renderApp = (props, config) => {
    render(<OndemandModal {...defaultProps} user={user} {...props} />, {
      showModal: true,
      initialState: config?.initialState,
    });
  };

  test('should render the modal header', async () => {
    await renderApp();
    const header = screen.queryByText('Instant Settlements');
    await waitForConfigLoader();
    expect(header).toBeInTheDocument();
  });

  describe('Modal close', () => {
    test('should close modal & open reason modal on close click', async () => {
      renderApp();
      await waitForConfigLoader();
      const closeCTA = screen.queryByTestId('modal-header-close-btn');
      await userEvent.click(closeCTA);
      const header = screen.queryByText('Instant Settlements');
      await waitFor(() => {
        expect(header).not.toBeInTheDocument();
      });
      expect(screen.queryByText('Reason')).toBeInTheDocument();
    });

    test('should close modal on esc key press', async () => {
      const user = userEvent.setup({ document });
      renderApp();
      await waitForConfigLoader();
      await user.keyboard('{Escape}');
      expect(screen.queryByText('Reason')).toBeInTheDocument();
    });
  });

  describe('Settled amount', () => {
    test('should render settlement amount', async () => {
      renderApp();
      await waitForConfigLoader();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9900'); // from mock data
    });

    test('should update settlement amount on amount change', async () => {
      renderApp();
      await waitForConfigLoader();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9900'); // from mock data

      await userEvent.clear(amount);
      await userEvent.type(amount, '999');
      expect(amount.value).toBe('999');
    });

    test('should render minimum amount error on invalid settlement amount', async () => {
      renderApp();
      await waitForConfigLoader();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9900'); // from mock data

      await userEvent.clear(amount);
      await userEvent.type(amount, '10');
      expect(screen.queryByText(/Minimum Amount should be/)).toBeInTheDocument();
    });

    test('should render maximum amount error on invalid settlement amount', async () => {
      renderApp();
      await waitForConfigLoader();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9900'); // from mock data

      await userEvent.clear(amount);
      await userEvent.type(amount, '9990000');
      expect(screen.queryByText(/Max amount/)).toBeInTheDocument();
    });

    test('should render max settle amount error on invalid amount for es restricted merchants', async () => {
      server.use(apiHandlers.odsConfigErrorHandler);
      const initialState = {
        ...state,
        session: {
          ...state.session,
          user: {
            ...state.session.user,
            isOndemandSettlementsRestricted: true,
          },
        },
      };
      renderApp({ settlableAmount: 98560 }, { initialState });
      await waitForConfigLoader();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('985');

      await userEvent.clear(amount);
      await userEvent.type(amount, '9990000');
      expect(screen.queryByText(/You can withdraw only upto/i)).toBeInTheDocument();
      expect(screen.queryByText(/986/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /confirm/i })).toBeDisabled();
      expect(screen.getByRole('button', { name: /show breakup/i })).toBeDisabled();
      await userEvent.click(
        screen.getByRole('button', {
          name: /why\?/i,
        }),
      );
      await waitFor(() => {
        expect(
          screen.getByText(
            /you are enjoying early access to instant settlements and can settle a part of your balance/i,
          ),
        ).toBeInTheDocument();
      });
    });

    test('should render max settle amount error on invalid amount for non es restricted merchants', async () => {
      server.use(apiHandlers.odsConfigNoBreachWithLimitHandler);
      renderApp({ settlableAmount: 989560 }, { initialState: state });
      await waitForConfigLoader();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9895');

      await userEvent.clear(amount);
      await userEvent.type(amount, '99909000');
      expect(screen.queryByText(/You can withdraw only upto/i)).toBeInTheDocument();
      expect(screen.queryByText(/3k/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /confirm/i })).toBeDisabled();
      expect(screen.getByRole('button', { name: /show breakup/i })).toBeDisabled();
      await userEvent.click(
        screen.getByRole('button', {
          name: /why\?/i,
        }),
      );
      await waitFor(() => {
        expect(
          screen.getByRole('heading', {
            name: /instant settlements now come with a daily settlement limit\./i,
          }),
        ).toBeInTheDocument();
      });
    });

    test('should render settle amount info for es restricted merchants', async () => {
      server.use(apiHandlers.odsConfigNoBreachWithLimitHandler);
      const initialState = {
        ...state,
        session: {
          ...state.session,
          user: {
            ...state.session.user,
            isOndemandSettlementsRestricted: true,
          },
        },
      };
      renderApp({ settlableAmount: 99960 }, { initialState });
      await waitForConfigLoader();
      const amount = screen.queryByRole('textbox');
      expect(amount).toBeInTheDocument();

      expect(screen.queryByText(/You can withdraw only upto/i)).toBeInTheDocument();
      expect(screen.queryByText(/1k/i)).toBeInTheDocument();
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /confirm/i })).toBeEnabled();
      });
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /show breakup/i })).toBeEnabled();
      });
      await userEvent.click(
        screen.getByRole('button', {
          name: /why\?/i,
        }),
      );
      await waitFor(() => {
        expect(
          screen.getByText(
            /you are enjoying early access to instant settlements and can settle a part of your balance/i,
          ),
        ).toBeInTheDocument();
      });
    });

    test('should render max limit amount info for non es restricted merchants', async () => {
      server.use(apiHandlers.odsConfigNoBreachWithLimitHandler);
      renderApp({ settlableAmount: 20000 }, { initialState: state });
      await waitForConfigLoader();
      const amount = screen.queryByRole('textbox');
      expect(amount).toBeInTheDocument();

      expect(screen.queryByText(/You can withdraw only upto/i)).toBeInTheDocument();
      expect(screen.queryByText(/3k/i)).toBeInTheDocument();

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /confirm/i })).toBeEnabled();
      });
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /show breakup/i })).toBeEnabled();
      });
      await userEvent.click(
        screen.getByRole('button', {
          name: /why\?/i,
        }),
      );
      await waitFor(() => {
        expect(
          screen.getByRole('heading', {
            name: /instant settlements now come with a daily settlement limit\./i,
          }),
        ).toBeInTheDocument();
      });
    });
  });

  describe('Fee breakup', () => {
    test('should render fee breakup CTA', async () => {
      renderApp();
      await waitForConfigLoader();
      expect(screen.queryByText('Show Breakup')).toBeInTheDocument();
    });

    test('should render fee breakup on show fee CTA click', async () => {
      await renderApp();
      await waitForConfigLoader();
      const feeBreakupCTA = screen.queryByText('Show Breakup');
      expect(feeBreakupCTA).toBeInTheDocument();
      await userEvent.click(feeBreakupCTA);

      await waitFor(() => {
        expect(screen.queryByText('Taxes')).toBeInTheDocument();
      });
      expect(screen.queryByText('Amount to be settled')).toBeInTheDocument();
    });

    test('should hide fee breakup on hide fee CTA click', async () => {
      await renderApp();
      await waitForConfigLoader();
      const feeBreakupCTA = screen.queryByText('Show Breakup');
      expect(feeBreakupCTA).toBeInTheDocument();
      await userEvent.click(feeBreakupCTA);

      await waitFor(() => {
        expect(screen.queryByText('Hide Breakup')).toBeInTheDocument();
      });

      await userEvent.click(screen.queryByText('Hide Breakup'));
      expect(screen.queryByText('Show Breakup')).toBeInTheDocument();
    });
  });

  describe('Settlement confirmation', () => {
    test('should show confirmation modal on confirm click', async () => {
      await renderApp();
      await waitForConfigLoader();
      const confirmBtn = screen.queryByRole('button', { name: 'Confirm' });
      await userEvent.click(confirmBtn);
      expect(
        screen.queryByText('Are you sure you want to do this settlement?'),
      ).toBeInTheDocument();
      const closeBtn = screen.queryByRole('button', { name: `No, Don't` });
      const settleBtn = screen.queryByRole('button', { name: 'Yes, Settle' });
      expect(closeBtn).toBeInTheDocument();
      expect(settleBtn).toBeInTheDocument();
    });

    test('should close confirmation modal on close click', async () => {
      await renderApp();
      await waitForConfigLoader();
      const confirmBtn = screen.queryByRole('button', { name: 'Confirm' });
      await userEvent.click(confirmBtn);
      expect(
        screen.queryByText('Are you sure you want to do this settlement?'),
      ).toBeInTheDocument();
      const closeBtn = screen.queryByRole('button', { name: `No, Don't` });
      expect(closeBtn).toBeInTheDocument();
      await userEvent.click(closeBtn);
      await waitFor(() => {
        expect(
          screen.queryByText('Are you sure you want to do this settlement?'),
        ).not.toBeInTheDocument();
      });
      expect(window.rzpAnalytics).toBeCalled();
    });

    test('should call api on confirm click', async () => {
      await renderApp();
      await waitForConfigLoader();
      const confirmBtn = screen.queryByRole('button', { name: 'Confirm' });
      await userEvent.click(confirmBtn);
      expect(
        screen.queryByText('Are you sure you want to do this settlement?'),
      ).toBeInTheDocument();
      const confirmSettlementBtn = screen.queryByRole('button', { name: `Yes, Settle` });
      expect(confirmSettlementBtn).toBeInTheDocument();
      await userEvent.click(confirmSettlementBtn);
      expect(fetchCurrentBalanceSpy).toBeCalled();
      expect(fetchOndemandRestrictionsSpy).toBeCalled();
    });
  });

  describe('Post transaction', () => {
    test('should render success screen post transaction', async () => {
      await renderApp();
      await waitForConfigLoader();
      const confirmBtn = screen.queryByRole('button', { name: 'Confirm' });
      await userEvent.click(confirmBtn);
      expect(
        screen.queryByText('Are you sure you want to do this settlement?'),
      ).toBeInTheDocument();
      const confirmSettlementBtn = screen.queryByRole('button', { name: `Yes, Settle` });
      expect(confirmSettlementBtn).toBeInTheDocument();
      await userEvent.click(confirmSettlementBtn);
      await waitFor(() => {
        expect(screen.queryByText('Hurray!')).toBeInTheDocument();
      });
    });
    test('should close post transaction success screen on close click', async () => {
      await renderApp();
      await waitForConfigLoader();
      const confirmBtn = screen.queryByRole('button', { name: 'Confirm' });
      await userEvent.click(confirmBtn);
      expect(
        screen.queryByText('Are you sure you want to do this settlement?'),
      ).toBeInTheDocument();
      const confirmSettlementBtn = screen.queryByRole('button', { name: `Yes, Settle` });
      expect(confirmSettlementBtn).toBeInTheDocument();
      await userEvent.click(confirmSettlementBtn);
      await waitFor(() => {
        expect(screen.queryByText('Hurray!')).toBeInTheDocument();
      });
      const closeBtn = screen.queryByRole('button', { name: 'Close' });
      const closeCTA = screen.queryByTestId('modal-header-close-btn');
      await userEvent.click(closeCTA);
      await userEvent.click(closeBtn);
      expect(window.rzpAnalytics).toBeCalled();
    });
  });
});
