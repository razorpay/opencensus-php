import { act } from 'react-dom/test-utils';
import { fireEvent, screen, waitFor, within } from 'test-utils';

import AdvancedCODTable from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table';
import { useACODTable } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table/hooks';
import { ACOD_TABLE } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { lazyRenderACODComponent } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/test-helpers/acod';
import { rulesToTableNodes } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/test-helpers/util';

import {
  shippingRules,
  paymentRules,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/table';

jest.mock(
  'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table/hooks',
  () => ({
    useACODTable: jest.fn(),
  }),
);

const createRule = jest.fn();
const editRule = jest.fn();
const deleteRule = jest.fn();

const mockTableHookForProps = (props: any) => {
  (useACODTable as jest.Mock).mockReturnValueOnce({
    createRule,
    editRule,
    deleteRule,
    nodes: rulesToTableNodes(props.rules),
  });
};

const getTablePropsFor = (type: keyof typeof ACOD_TABLE) => {
  return {
    type,
    rules: type === 'shipping' ? shippingRules : paymentRules,
    ruleLimits: {
      shipping: 3,
      payment: 3,
    },
  };
};

const renderWithProps = lazyRenderACODComponent(AdvancedCODTable);

describe('AdvancedCODTable', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });

  it('should render', () => {
    const tableProps = getTablePropsFor('shipping');
    mockTableHookForProps(tableProps);

    renderWithProps(tableProps);
    expect(screen.getByText(ACOD_TABLE.shipping.title)).toBeInTheDocument();
  });

  it('should render table with correct column headers and rows', () => {
    const tableProps = getTablePropsFor('shipping');
    mockTableHookForProps(tableProps);

    const props = tableProps;
    const [firstRule] = props.rules;
    renderWithProps(props);

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
  });

  it('should call edit and delete callbacks with proper rule arguments', async () => {
    const props = getTablePropsFor('payment');
    mockTableHookForProps(props);
    const [firstRule] = props.rules;
    renderWithProps(props);

    const rows = screen.getAllByRole('row');
    act(() => {
      fireEvent.click(within(rows[0]).getByRole('button', { name: /edit rule/i }));
    });
    await waitFor(() => {
      expect(editRule).toHaveBeenCalledWith(firstRule);
    });
    act(() => {
      fireEvent.click(within(rows[0]).getByRole('button', { name: /delete rule/i }));
    });
    await waitFor(() => {
      expect(deleteRule).toHaveBeenCalledWith(firstRule);
    });
  });

  it('should call create rule callback', () => {
    const props = getTablePropsFor('payment');
    mockTableHookForProps(props);
    renderWithProps(props);

    fireEvent.click(
      screen.getByRole('button', { name: ACOD_TABLE.payment.newRuleCTAAccessibilityLabel }),
    );
    expect(createRule).toHaveBeenCalled();
  });
});
