import { screen, waitForLoadingToFinish } from 'test-utils';
import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/PaymentPages/Success/index';

describe('Batch Payment Page - Create - Success Page', () => {
  test('should render "Success Page" without "Batch Upload" option', async () => {
    const initialState = {
      wysiwyg: { isBatchPaymentPages: false },
    };
    const props = { id: 'pl_LW9jwogAkEXqFp' };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    expect(screen.getByText('Page Published')).toBeInTheDocument();
    expect(screen.getByText('Back to Dashboard')).toBeInTheDocument();
    expect(screen.queryByText('Batch Upload')).not.toBeInTheDocument();
  });

  test('should render "Success Page" with "Batch Upload" option', async () => {
    const initialState = {
      wysiwyg: { isBatchPaymentPages: true },
    };
    const props = {
      id: 'pl_LW9jwogAkEXqFp',
    };
    renderApp(initialState, props);
    await waitForLoadingToFinish();
    expect(screen.getByText('Page Published')).toBeInTheDocument();
    expect(screen.getByText('Back to Dashboard')).toBeInTheDocument();
  });
});
