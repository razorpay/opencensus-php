import React from 'react';
import { userEvent, render, screen, waitFor, delay } from 'test-utils';
import OndemandModal from 'merchant/views/Settlements/Settlements/components/Modals/OndemandModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import {
  defaultProps,
  user,
  patchKeyEvent,
} from 'merchant/views/Settlements/Settlements/components/Modals/__test__/mocks/fixtures/OndemandModal';
import * as settlementActions from 'merchant/reducers/home';

describe('OndemandModal.js', () => {
  const closeModalsSpy = jest.spyOn(ModalActions, 'closeModal');
  const fetchCurrentBalanceSpy = jest.spyOn(settlementActions, 'fetchCurrentBalance');
  const fetchOndemandRestrictionsSpy = jest.spyOn(settlementActions, 'fetchOndemandRestrictions');

  beforeEach(() => {
    closeModalsSpy.mockClear();
    fetchCurrentBalanceSpy.mockClear();
    fetchOndemandRestrictionsSpy.mockClear();

    window.rzpAnalytics.mockReset();
    window.rzp_user = {
      mode: 'test',
    };
  });

  beforeAll(() => {
    document.addEventListener('keydown', patchKeyEvent, { capture: true });
  });

  const renderApp = async (props) => {
    render(<OndemandModal {...defaultProps} {...props} user={user} />, {
      showModal: true,
    });
    await delay();
  };

  test('should render the modal header', async () => {
    await renderApp();
    const header = screen.queryByText('Instant Settlements');
    expect(header).toBeInTheDocument();
  });

  describe('Modal close', () => {
    test('should close modal & open reason modal on close click', async () => {
      renderApp();
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
      await user.keyboard('{Escape}');
      expect(screen.queryByText('Reason')).toBeInTheDocument();
    });
  });

  describe('Settled amount', () => {
    test('should render settlement amount', () => {
      renderApp();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9900'); // from mock data
    });

    test('should update settlement amount on amount change', async () => {
      renderApp();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9900'); // from mock data

      await userEvent.clear(amount);
      await userEvent.type(amount, '999');
      expect(amount.value).toBe('999');
    });

    test('should render minimum amount error on invalid settlement amount', async () => {
      renderApp();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9900'); // from mock data

      await userEvent.clear(amount);
      await userEvent.type(amount, '10');
      expect(screen.queryByText(/Minimum Amount should be/)).toBeInTheDocument();
    });

    test('should render maximum amount error on invalid settlement amount', async () => {
      renderApp();
      const amount = screen.queryByRole('textbox');

      expect(amount).toBeInTheDocument();
      expect(amount.value).toBe('9900'); // from mock data

      await userEvent.clear(amount);
      await userEvent.type(amount, '9990000');
      expect(screen.queryByText(/Max amount/)).toBeInTheDocument();
    });
  });

  describe('Fee breakup', () => {
    test('should render fee breakup CTA', () => {
      renderApp();
      expect(screen.queryByText('Show Breakup')).toBeInTheDocument();
    });

    test('should render fee breakup on show fee CTA click', async () => {
      await renderApp();
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
      const confirmBtn = screen.queryByRole('button', { name: 'Confirm' });
      await userEvent.click(confirmBtn);
      expect(
        screen.queryByText('Are you sure you want to do this settlement?'),
      ).toBeInTheDocument();
      const confirmSettlementBtn = screen.queryByRole('button', { name: `Yes, Settle` });
      expect(confirmSettlementBtn).toBeInTheDocument();
      await userEvent.click(confirmSettlementBtn);
      await delay();
      expect(fetchCurrentBalanceSpy).toBeCalled();
      expect(fetchOndemandRestrictionsSpy).toBeCalled();
    });
  });

  describe('Post transaction', () => {
    test('should render success screen post transaction', async () => {
      await renderApp();
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
