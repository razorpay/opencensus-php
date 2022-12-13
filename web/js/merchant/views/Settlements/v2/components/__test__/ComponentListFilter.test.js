import React from 'react';
import ComponentListFilter from 'merchant/views/Settlements/v2/components/ComponentListFilter';
import { userEvent, render, screen } from 'test-utils';

describe('ComponentListFilter', () => {
  const defaultProps = {
    submit: jest.fn(),
    clear: jest.fn(),
    count: 10,
    type: 'debit',
    activeTab: 'adjustment',
  };

  const appRef = {};

  const renderApp = () => {
    return render(<ComponentListFilter {...defaultProps} ref={appRef} />);
  };

  const getAdjustmentIdField = () =>
    screen.getByRole('textbox', { name: new RegExp(defaultProps.activeTab, 'i') });

  test('should render input fields, search button and clear text', () => {
    renderApp();
    expect(getAdjustmentIdField()).toBeInTheDocument();
    const countInput = screen.getByRole('spinbutton', { name: 'Count' });
    expect(countInput).toBeInTheDocument();
    expect(countInput).toHaveValue(defaultProps.count);
    expect(screen.getByRole('button', { name: 'Search' })).toBeInTheDocument();
    expect(screen.getByText('Clear')).toBeInTheDocument();
  });

  test('should call submit callback on clicking Search', async () => {
    renderApp();
    await userEvent.click(screen.getByRole('button', { name: 'Search' }));
    expect(defaultProps.submit).toHaveBeenCalled();
  });

  test('should call clear callback on clicking Clear', async () => {
    renderApp();
    const clearText = screen.getByText('Clear');
    await userEvent.click(clearText);
    expect(defaultProps.clear).toHaveBeenCalled();
  });

  test('should call reset form values on calling reset through ref', async () => {
    renderApp();
    await userEvent.type(getAdjustmentIdField(), 'test');
    expect(getAdjustmentIdField()).toHaveValue('test');
    appRef.current.reset();
    expect(getAdjustmentIdField()).toHaveValue('');
  });
});
