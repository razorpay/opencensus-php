import { screen, fireEvent, within } from '@testing-library/react';

import { AdvancedCODTable } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table';
import {
  shippingRules,
  paymentRules,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/table';
import { ACOD_TABLE } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { lazyRenderComponent } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/test-helpers';

const getTablePropsFor = (type: keyof typeof ACOD_TABLE) => {
  return {
    type,
    rules: type === 'shipping' ? shippingRules : paymentRules,
    createRule: jest.fn(),
    editRule: jest.fn(),
    deleteRule: jest.fn(),
  };
};

const renderWithProps = lazyRenderComponent(AdvancedCODTable);

describe('AdvancedCODTable', () => {
  it('should render', () => {
    renderWithProps(getTablePropsFor('shipping'));
    expect(screen.getByText(ACOD_TABLE.shipping.title)).toBeInTheDocument();
  });

  it('should render table with correct column headers and rows', () => {
    const props = getTablePropsFor('shipping');
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
    expect(within(rows[0]).getByText('-')).toBeInTheDocument();
  });

  it('should call edit and delete callbacks with proper rule arguments', () => {
    const props = getTablePropsFor('payment');
    const [firstRule] = props.rules;
    renderWithProps(props);

    const rows = screen.getAllByRole('row');

    fireEvent.click(within(rows[0]).getByRole('button', { name: /delete rule/i }));
    expect(props.deleteRule).toHaveBeenCalledWith(firstRule);
    fireEvent.click(within(rows[0]).getByRole('button', { name: /edit rule/i }));
    expect(props.editRule).toHaveBeenCalledWith(firstRule);
  });

  it('should call create rule callback', () => {
    const props = getTablePropsFor('payment');
    renderWithProps(props);

    fireEvent.click(
      screen.getByRole('button', { name: ACOD_TABLE.payment.newRuleCTAAccessibilityLabel }),
    );
    expect(props.createRule).toHaveBeenCalled();
  });
});
