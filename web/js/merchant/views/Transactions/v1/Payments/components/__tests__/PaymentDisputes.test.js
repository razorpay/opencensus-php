import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentDisputes from 'merchant/views/Transactions/v1/Payments/components/PaymentDisputes';
import { render, screen, fireEvent } from 'test-utils';

describe('PaymentDisputes', () => {
  const openDisputeId = 'qw1efe3dwf';
  const defaultProps = {
    disputes: [
      {
        id: openDisputeId,
        status: 'open',
        phase: 'dispute',
      },
      {
        id: 'er2efe3dwf',
        status: 'closed',
      },
    ],
  };

  const App = (props) => {
    return <PaymentDisputes {...defaultProps} {...props} />;
  };

  test('should render disputes raised', () => {
    render(<App />);
    expect(screen.getByText(/Disputes raised/)).toBeInTheDocument();
  });

  test('should render open disputes', () => {
    render(<App />);
    expect(
      screen.getByText(
        /Your customer has raised a dispute on this payment. To avoid losing the dispute/,
      ),
    ).toBeInTheDocument();
  });

  test('should call onDisputeClick when dispute Id is clicked', () => {
    const onDisputeClick = jest.fn();
    render(<App onDisputeClick={onDisputeClick} />);
    fireEvent.click(screen.getByText(openDisputeId));
    expect(onDisputeClick).toHaveBeenCalledWith(`disputes/${openDisputeId}`);
  });
});
