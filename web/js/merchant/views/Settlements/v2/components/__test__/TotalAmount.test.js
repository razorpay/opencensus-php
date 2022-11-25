import React from 'react';
import TotalAmount from 'merchant/views/Settlements/v2/components/TotalAmount';
import { render, screen } from 'test-utils';

jest.mock('common/ui/Amount', () => ({
  ...jest.requireActual('merchant/components/File/Upload'),
  __esModule: true,
  default: ({ value }) => <div data-testid="amount">{value}</div>,
}));

const defaultProps = {
  isNew: false,
  type: 'credit',
  value: 1000,
  infoText: 'test-info-text',
};

describe('Total Amount', () => {
  const renderApp = (props) => render(<TotalAmount {...defaultProps} {...props} />);

  test('should render credit amount', () => {
    renderApp();
    expect(screen.getByText(new RegExp(`Total ${defaultProps.type} amount`))).toBeInTheDocument();
    expect(screen.getByTestId('amount')).toHaveTextContent(defaultProps.value);
  });

  test('should render debit amount when type is debit and isNew', () => {
    renderApp({ type: 'debit', isNew: true });
    expect(screen.getByText(new RegExp(`Total debit amount`))).toBeInTheDocument();
    expect(screen.getByTestId('amount')).toHaveTextContent(-defaultProps.value);
  });

  test('should render infoComp text if it exists ', () => {
    const { rerender } = renderApp();
    expect(screen.getByText(defaultProps.infoText)).toBeInTheDocument();

    expect(screen.getByTestId('total-amount-popover')).toHaveTextContent(defaultProps.infoText);

    const infoCompText = 'test-info-comp-text';
    rerender(<TotalAmount {...defaultProps} infoComp={infoCompText} />);
    expect(screen.getByTestId('total-amount-popover')).toHaveTextContent(infoCompText);
  });
});
