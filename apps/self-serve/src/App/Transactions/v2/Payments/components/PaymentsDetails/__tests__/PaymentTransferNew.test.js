import { mockNavigate, renderApp, fetchTransfersFnSpy } from './mocks/fixtures/PaymentTransferNew';
import { userEvent, screen } from 'apps/self-serve/src/services/test/test-utils';

describe('PaymentTransferNew', () => {
  test('should render component without errors', () => {
    renderApp();
    const newPaymentTransferText = screen.getByText('New Payment Transfer');
    expect(newPaymentTransferText).toBeInTheDocument();
  });

  test('should navigate back when close button is clicked', async () => {
    renderApp();
    const closeButton = await screen.findByRole('button', { name: 'Close' });
    await userEvent.click(closeButton);
    expect(mockNavigate).toHaveBeenCalledWith(-1);
  });

  test('should create transfer back when Create Transfer button is clicked', async () => {
    renderApp();
    const createTransferButton = await screen.findByRole('button', { name: 'Create Transfer' });
    await userEvent.click(createTransferButton);
    expect(fetchTransfersFnSpy).toHaveBeenCalledWith('paymentId');
  });
});
