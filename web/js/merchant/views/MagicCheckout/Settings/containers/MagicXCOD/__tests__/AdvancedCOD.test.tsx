import React from 'react';
import { act } from 'react-dom/test-utils';
import { fireEvent, render as renderMain, screen, server, waitFor, within } from 'test-utils';
import { rest } from 'msw';

import { storeWithInitialState } from 'merchant/store';
import AdvancedCOD from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Containers/AdvancedCOD';
import { ConfirmationModalProvider } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';

import {
  shippingRules,
  paymentRules,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/table';

const initState = {
  magicxACODRules: {
    isLoading: { rules: false, ruleFacts: false },
    ruleLimits: { shipping: 0, payment: 0 },
    rules: [],
    ruleFacts: [],
  },
};

const render = (ui, config = {}) => {
  return renderMain(<ConfirmationModalProvider>{ui}</ConfirmationModalProvider>, {
    reduxStore: storeWithInitialState({ ...initState, ...config }),
  });
};

const mockedNotify = jest.fn();

const mockRulesFetchCall = () => {
  server.use(
    rest.get('*/magic/sopc/customisations/rules', (_, res, ctx) => {
      return res(
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            rules: [...shippingRules, ...paymentRules],
          },
        }),
      );
    }),
  );
};

const mockRuleDeleteCall = () => {
  server.use(
    rest.delete('*/magic/sopc/customisations/rules/*', (_, res, ctx) => {
      return res(
        ctx.json({
          status_code: 204,
          success: true,
        }),
      );
    }),
  );
};

describe('AdvancedCOD', () => {
  it('should render loader initially', () => {
    render(<AdvancedCOD />);
    expect(screen.getByTestId('rules-loading-spinner')).toBeInTheDocument();
  });

  it('should fetch rules on initial load', async () => {
    mockRulesFetchCall();
    render(<AdvancedCOD />);

    await waitFor(() => {
      const rows = screen.getAllByRole('row');
      expect(rows.length).toEqual(4);
    });
  });

  it('should remove a rule from store on delete action', async () => {
    mockRulesFetchCall();
    mockRuleDeleteCall();
    render(<AdvancedCOD notify={mockedNotify} />);

    await waitFor(() => {
      const rows = screen.getAllByRole('row');
      expect(rows.length).toEqual(4);
    });
    act(() => {
      const rows = screen.getAllByRole('row');
      fireEvent.click(within(rows[3]).getByRole('button', { name: /delete rule/i }));
    });
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeInTheDocument(); // Checks if the modal opens
    });
    act(() => {
      fireEvent.click(screen.getByText('Delete'));
    });
    await waitFor(() => {
      expect(mockedNotify).toBeCalled();
    });
  });
});
