import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { useBreakpoint } from '@razorpay/blade/utils';

import FailedPaymentsRetryCalculator from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/components/FailedPaymentsRetry';
import { render, screen, userEvent, waitFor } from 'test-utils';

jest.mock('@razorpay/blade/utils', () => ({
  ...jest.requireActual('@razorpay/blade/utils'),
  useBreakpoint: jest.fn(),
}));

describe('Failed Payment Retry Calculator', () => {
  const App = ({ props }) => {
    return <FailedPaymentsRetryCalculator {...props} />;
  };

  beforeEach(() => {
    useBreakpoint.mockReturnValue({
      matchedBreakpoint: 'xl',
    });
  });

  test('should render calculator modal link', () => {
    render(<App />);
    expect(screen.getByText('To view the FPR calculator, click here')).toBeInTheDocument();
  });

  test('should render calculator modal on link click', async () => {
    render(<App />);
    const viewLink = screen.getByRole('button', { name: 'To view the FPR calculator, click here' });
    expect(viewLink).toBeInTheDocument();
    userEvent.click(viewLink);

    await waitFor(() => {
      expect(screen.getByText('Calculate the recovered GMV')).toBeInTheDocument();
    });
    expect(
      screen.getByText(
        'To assess the recovery potential for your business, enter the values to calculate the monthly revenue you could have earned from your failed payments',
      ),
    ).toBeInTheDocument();
  });

  test('should calculate failed payment recovery savings', async () => {
    render(<App />);
    const viewLink = screen.getByRole('button', { name: 'To view the FPR calculator, click here' });
    expect(viewLink).toBeInTheDocument();
    userEvent.click(viewLink);

    await waitFor(() => {
      expect(screen.getByText('Total recovered GMV:')).toBeInTheDocument();
    });

    const failedOrderInput = screen.getByRole('textbox', { name: 'Number of failed orders' });
    await userEvent.type(failedOrderInput, '100');
    const orderValueInput = screen.getByRole('textbox', { name: 'Average order value' });
    await userEvent.type(orderValueInput, '500');

    await waitFor(() => {
      expect(screen.getByText('1,500.00')).toBeInTheDocument();
    });
  });

  test('should close failed payment recovery modal', async () => {
    render(<App />);
    const viewLink = screen.getByRole('button', { name: 'To view the FPR calculator, click here' });
    expect(viewLink).toBeInTheDocument();
    userEvent.click(viewLink);

    await waitFor(() => {
      expect(screen.getByText('Calculate the recovered GMV')).toBeInTheDocument();
    });
    const closeCta = screen.getByRole('button', { name: 'Close' });
    expect(closeCta).toBeInTheDocument();
    userEvent.click(closeCta);

    await waitFor(() => {
      expect(screen.getByText('To view the FPR calculator, click here')).toBeInTheDocument();
    });
  });
});
