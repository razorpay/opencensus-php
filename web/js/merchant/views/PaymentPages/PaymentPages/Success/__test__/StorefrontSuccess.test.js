import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitForLoadingToFinish, server } from 'test-utils';

import StorefrontSuccessPage from 'merchant/views/PaymentPages/PaymentPages/Success/Storefront';

import { paymentPagesErrorHandlers } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/handlers';

import { store } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/fixtures/storefront';

jest.mock('common/ui/Clipboard/Custom', () => ({ children }) => (
  <>
    <div>Custom Clipboard</div>
    <div>{children}</div>
  </>
));

describe('Success Page', () => {
  test('should render basic details and CTAs in success scenario', async () => {
    render(<StorefrontSuccessPage />);

    await waitForLoadingToFinish();

    expect(screen.getByText('EDIT PAGE')).toBeInTheDocument();

    expect(screen.getByText('Your page is now live!')).toBeInTheDocument();
    expect(screen.getByText(store.title)).toBeInTheDocument();
    expect(
      screen.getAllByRole('button', {
        name: /share/i,
      })[0],
    ).toBeInTheDocument();
    expect(
      screen.getAllByRole('button', {
        name: /(go to page)|copy/i,
      })[0],
    ).toBeInTheDocument();
    expect(screen.getByDisplayValue(store.short_url)).toBeInTheDocument();

    expect(screen.getByText('My Products')).toBeInTheDocument();
    expect(screen.getByText('Page Settings')).toBeInTheDocument();
  });

  test('should show proper error message in failure scenario', async () => {
    server.use(paymentPagesErrorHandlers.storefrontFetch());
    render(<StorefrontSuccessPage />);

    await waitForLoadingToFinish();

    expect(screen.getByText('Something went wrong')).toBeInTheDocument();
  });
});
