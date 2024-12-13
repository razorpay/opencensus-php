import * as React from 'react';
import { act } from 'react-dom/test-utils';
import { fireEvent, render as renderMain, screen, server, waitFor, within } from 'test-utils';
import { rest } from 'msw';

import { storeWithInitialState } from 'merchant/store';

import AdvancedCODTable from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table';
import {
  shippingRules,
  paymentRules,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/table';
import { ACOD_TABLE } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { ConfirmationModalProvider } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';

const getTablePropsFor = (type: keyof typeof ACOD_TABLE) => {
  return {
    type,
    rules: type === 'shipping' ? shippingRules : paymentRules,
    createRule: jest.fn(),
    editRule: jest.fn(),
    deleteRule: jest.fn(),
  };
};

const initState = {
  config: {
    config: {
      id: 'mid',
    },
  },
};

const render = (ui, config = {}) => {
  return renderMain(<ConfirmationModalProvider>{ui}</ConfirmationModalProvider>, {
    reduxStore: storeWithInitialState({ ...initState, ...config }),
  });
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

describe('AdvancedCODTable', () => {
  it('should render', () => {
    render(<AdvancedCODTable {...getTablePropsFor('shipping')} />);
    expect(screen.getByText(ACOD_TABLE.shipping.title)).toBeInTheDocument();
  });

  it('should render table with correct column headers and rows', () => {
    const props = getTablePropsFor('shipping');
    const [firstRule] = props.rules;
    render(<AdvancedCODTable {...props} />);

    const rows = screen.getAllByRole('row');
    const rowHeader = screen.getByRole('rowheader');

    //Column Headers
    expect(within(rowHeader).getByText('Name')).toBeInTheDocument();
    expect(within(rowHeader).getByText('Description')).toBeInTheDocument();
    expect(within(rowHeader).getByText('Rule')).toBeInTheDocument();
    expect(within(rowHeader).getByText('Action')).toBeInTheDocument();

    // First Row
    expect(within(rows[0]).getByText(firstRule.name)).toBeInTheDocument();
    expect(within(rows[0]).getByText(firstRule.description)).toBeInTheDocument();
    expect(within(rows[0]).getByText('-')).toBeInTheDocument();
  });

  it('should call edit and delete callbacks with proper rule arguments', async () => {
    mockRuleDeleteCall();
    const props = getTablePropsFor('payment');
    const [firstRule] = props.rules;
    render(<AdvancedCODTable {...props} />);

    const rows = screen.getAllByRole('row');
    act(() => {
      fireEvent.click(within(rows[0]).getByRole('button', { name: /edit rule/i }));
    });
    await waitFor(() => {
      expect(props.editRule).toHaveBeenCalledWith(firstRule);
    });
    act(() => {
      fireEvent.click(within(rows[0]).getByRole('button', { name: /delete rule/i }));
    });
    act(() => {
      fireEvent.click(screen.getByText('Delete'));
    });
    await waitFor(() => {
      expect(props.deleteRule).toHaveBeenCalledWith(firstRule);
    });
  });

  it('should call create rule callback', () => {
    const props = getTablePropsFor('payment');
    render(<AdvancedCODTable {...props} />);

    fireEvent.click(
      screen.getByRole('button', { name: ACOD_TABLE.payment.newRuleCTAAccessibilityLabel }),
    );
    expect(props.createRule).toHaveBeenCalled();
  });
});
