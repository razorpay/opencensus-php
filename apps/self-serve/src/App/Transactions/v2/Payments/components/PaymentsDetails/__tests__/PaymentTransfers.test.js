import { useMobile } from '@dashboard/shared-ui/hooks';
import {
  renderApp,
  defaultPaymentDetails,
  fetchTransfersFnSpy,
  mockNavigate,
} from './mocks/fixtures/PaymentTransfers';
import { userEvent, screen } from 'apps/self-serve/src/services/test/test-utils';

describe('PaymentTransfers', () => {
  test('should render transfer list when transfers are available', async () => {
    renderApp();
    const transferText = screen.getByText('Transfer');
    expect(transferText).toBeInTheDocument();
    const transferLink = await screen.findByRole('link', { name: 'trf_JMMPL4TnlzG30O' });
    expect(transferLink).toBeInTheDocument();
  });

  test('should navigate when transfer ID link is clicked', async () => {
    renderApp();
    const transferLink = await screen.findByRole('link', { name: 'trf_JMMPL4TnlzG30O' });
    await userEvent.click(transferLink);
    expect(mockNavigate).toHaveBeenCalledWith('/route/transfers/trf_JMMPL4TnlzG30O');
  });

  test('should render create transfer button when conditions are met', () => {
    renderApp();
    const createTransferButton = screen.getByRole('button', { name: 'Create transfer' });
    expect(createTransferButton).toBeInTheDocument();
  });

  test('should render disabled create transfer button when conditions are not met', () => {
    renderApp({
      paymentDetails: {
        ...defaultPaymentDetails,
        amount_transferred: 10000,
      },
    });
    const createTransferButton = screen.getByRole('button', { name: 'Create transfer' });
    expect(createTransferButton).toBeDisabled();
  });

  test('should call fetchTransfersAction when component mounts', () => {
    renderApp({
      paymentDetails: {
        ...defaultPaymentDetails,
        status: 'authorized',
      },
    });
    expect(fetchTransfersFnSpy).not.toHaveBeenCalledWith(defaultPaymentDetails.id);
  });

  test('should navigate to the new transfer page when the create transfer button is clicked', async () => {
    renderApp();
    const createTransferButton = screen.getByRole('button', { name: 'Create transfer' });
    expect(createTransferButton).toBeEnabled();
    await userEvent.click(createTransferButton);
    expect(mockNavigate).toHaveBeenCalledWith(
      `/payments/${defaultPaymentDetails.id}/v2/transfers/new`,
    );
  });

  test('should not call fetchTransfersAction when component mounts & when payment status is authorized', () => {
    renderApp();
    expect(fetchTransfersFnSpy).toHaveBeenCalledWith(defaultPaymentDetails.id);
  });

  describe('Mobile view', () => {
    beforeEach(() => {
      useMobile.mockReturnValue(true);
    });
    test('should render create transfer button when conditions are met on Mobile', () => {
      renderApp();
      const createTransferButton = screen.getByRole('button', { name: 'Create' });
      expect(createTransferButton).toBeInTheDocument();
    });
  });
});
