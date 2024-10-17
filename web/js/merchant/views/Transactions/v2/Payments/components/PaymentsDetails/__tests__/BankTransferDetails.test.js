import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import BankTransferDetails from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/BankTransferDetails';
import { mockBankTransferDetails } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/handlers';

const initProps = {
  paymentID: 'test_payment_id',
};

const renderApp = ({ ...props } = {}) => {
  return render(<BankTransferDetails {...initProps} {...props} />);
};

describe('testing bank transfer details component', () => {
  beforeEach(() => {
    mockBankTransferDetails();
  });

  test('component should render properly', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/bank transfer/i)).toBeInTheDocument();
      expect(screen.queryByText('CREDIT CARD OPERATIONS')).not.toBeInTheDocument();
    });
  });

  test('should to be able to collapse bank transfer details', async () => {
    renderApp();

    await waitFor(() => {
      expect(screen.getByText(/bank transfer/i)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText(/bank transfer/i));
    await waitFor(() => {
      expect(screen.getByText('CREDIT CARD OPERATIONS')).toBeInTheDocument();
    });
  });

  test('should be able to click on the virtual account id', async () => {
    const { history } = renderApp();

    await waitFor(() => {
      expect(screen.getByText(/bank transfer/i)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText(/bank transfer/i));

    await userEvent.click(screen.getByText('va_La2OoAmcN48Bq0'));

    await waitFor(() => {
      expect(history.location.pathname).toBe('/virtualaccounts/va_La2OoAmcN48Bq0');
    });
  });
});
