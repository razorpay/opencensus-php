import { screen, userEvent } from 'test-utils';
import { renderApp } from 'merchant/views/Settings/PaymentMethods/components/__tests__/mocks/fixtures/PaytmWallet';

describe('PaytmWallet', () => {
  describe('When paytm_production_status is ACCOUNT_LINKABLE', () => {
    test('should render Loading text when Paytm Wallet is loading', () => {
      renderApp();
      expect(screen.getByText('Loading..')).toBeInTheDocument();
    });

    test('should render Link Account button when Paytm Wallet has loaded', () => {
      renderApp({ props: { loading: false } });
      const linkAccountButton = screen.getByRole('button', {
        name: 'Link Account',
      });
      expect(linkAccountButton).toBeInTheDocument();
    });

    test('should call handlePaytmWalletIntegration when Link Account button is clicked', async () => {
      const handlePaytmWalletIntegration = jest.fn();
      renderApp({ props: { handlePaytmWalletIntegration, loading: false } });
      const linkAccountButton = screen.getByRole('button', {
        name: 'Link Account',
      });
      await userEvent.click(linkAccountButton);
      expect(handlePaytmWalletIntegration).toBeCalled();
    });
  });

  describe('When paytm_production_status is activated', () => {
    test('should render activated status', () => {
      renderApp({ props: { paytm_production_status: 'activated' } });
      expect(screen.getByText('Activated')).toBeInTheDocument();
      expect(
        screen.getByText(
          'Paytm Wallet is enabled on Live Mode, customers can transact with Paytm wallet. To view/edit your Paytm Production API Credentials, click here.',
        ),
      ).toBeInTheDocument();
    });
  });
});
