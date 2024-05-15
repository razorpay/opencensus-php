import React from 'react';

import fileDownload from 'common/utils/file-download';
import PaymentReceipt from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentReceipt';
import { render, screen, fireEvent, waitFor, server } from 'test-utils';

import { mockFetchEncodedPaymentReceipt } from './mocks/handlers';

jest.mock('common/utils/file-download', () => jest.fn());

const defaultProps = {
  id: 'testPaymentId123',
};

const renderApp = () => {
  return render(<PaymentReceipt {...defaultProps} />);
};

describe('PaymentReceipt component', () => {
  test('should render loader initially', () => {
    renderApp();
    expect(screen.getByRole('loader')).toBeInTheDocument();
  });

  test('should fetch and render the receipt image', async () => {
    server.use(mockFetchEncodedPaymentReceipt(defaultProps.id));
    renderApp();
    await waitFor(() => {
      expect(screen.getByAltText('receipt')).toBeInTheDocument();
    });
  });

  test('should handle error when fetching receipt image fails', async () => {
    server.use(mockFetchEncodedPaymentReceipt());
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('No Charge Slip found.')).toBeInTheDocument();
    });
  });

  test('should call download function on Download button click', async () => {
    server.use(mockFetchEncodedPaymentReceipt(defaultProps.id));
    renderApp();
    await waitFor(() => {
      fireEvent.click(screen.getByText('Download'));
      expect(fileDownload).toHaveBeenCalled();
    });
  });
});
