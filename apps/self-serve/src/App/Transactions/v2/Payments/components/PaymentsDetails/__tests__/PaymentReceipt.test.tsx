import React from 'react';

import { mockFetchEncodedPaymentReceipt } from './mocks/handlers';
import {
  render,
  screen,
  fireEvent,
  waitFor,
  server,
} from 'apps/self-serve/src/services/test/test-utils';

import PaymentReceipt from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/PaymentReceipt';

const defaultProps = {
  id: 'testPaymentId123',
};

const renderApp = () => {
  return render(<PaymentReceipt {...defaultProps} />);
};

describe('PaymentReceipt component', () => {
  test('should render loader initially', () => {
    renderApp();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
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
      const linkMock = document.createElement('a');
      linkMock.click = jest.fn();
      linkMock.href = '';
      linkMock.download = '';
      jest.spyOn(document, 'createElement').mockReturnValue(linkMock);
      fireEvent.click(screen.getByText('Download'));
      expect(linkMock.download).toBe(`${defaultProps.id}.png`);
      expect(linkMock.click).toHaveBeenCalled();
    });
  });
});
