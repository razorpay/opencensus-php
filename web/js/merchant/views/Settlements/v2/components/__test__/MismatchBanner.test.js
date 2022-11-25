import React from 'react';
import { MismatchBanner } from 'merchant/views/Settlements/v2/components/MismatchBanner';
import { render, screen } from 'test-utils';

jest.mock('common/ui/Amount', () => ({
  ...jest.requireActual('merchant/components/File/Upload'),
  __esModule: true,
  default: ({ value }) => <div data-testid="amount">{value / 100}</div>,
}));

const defaultProps = {
  totalAmount: 700,
  calculatedAmounts: 100,
  gatewayName: 'payu',
};
const mismatchAmount = defaultProps.totalAmount - defaultProps.calculatedAmounts;

describe('MismatchBanner', () => {
  const renderApp = () => render(<MismatchBanner {...defaultProps} />);

  test('should render banner heading', () => {
    renderApp();
    expect(screen.getByText(/Settlement mismatch of/i)).toBeInTheDocument();
    // first element is in the header
    expect(screen.getAllByTestId('amount')?.[0]).toHaveTextContent(mismatchAmount);
  });

  test('should render banner description', () => {
    renderApp();

    expect(screen.getByText(/Razorpay Optimizer has a log of/i)).toBeInTheDocument();
    expect(screen.getByText(/but, we fetched an amount of/i)).toBeInTheDocument();
    expect(screen.getByText(/Razorpay Optimizer has a log of/i)).toBeInTheDocument();
    expect(screen.getByText(/dashboard for mismatched amounts./i)).toBeInTheDocument();
    expect(screen.getByText(new RegExp(defaultProps.gatewayName, 'i'))).toBeInTheDocument();

    const amountElements = screen.getAllByTestId('amount');
    expect(amountElements[0]).toHaveTextContent(mismatchAmount);
    expect(amountElements[1]).toHaveTextContent(defaultProps.calculatedAmounts);
    expect(amountElements[2]).toHaveTextContent(defaultProps.totalAmount);
  });
});
