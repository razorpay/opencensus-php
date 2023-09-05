import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { OptimizerDetails } from 'merchant/views/Transactions/v1/Payments/components/OptimizerDetails';
import { render, screen } from 'test-utils';

describe('OptimizerDetails', () => {
  const defaultProps = {
    scrolledToBottom: false,
    payment: {
      transaction: {
        optimizer_provider: 'xyz',
      },
    },
  };

  const App = (props) => {
    return <OptimizerDetails {...defaultProps} {...props} />;
  };

  test('should render Optimizer details', () => {
    render(<App />);
    expect(screen.getByText('Optimizer details')).toBeInTheDocument();
  });

  test('should render Optimizer details when scrolledToBottom is true', () => {
    render(<App scrolledToBottom />);
    expect(screen.getByText('Optimizer details')).toBeInTheDocument();
  });
});
