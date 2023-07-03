import { screen, waitForLoadingToFinish, userEvent } from 'test-utils';
import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/PaymentPages/Success/index';

import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';

describe('Batch Payment Page - Create - Success Page', () => {
  const batchPageProps = { id: 'pl_validid', isBatchPaymentPages: true };

  test('should render "Success Page" without "Upload Batch" option', async () => {
    const props = { ...batchPageProps, isBatchPaymentPages: false };

    renderApp({}, props);

    await waitForLoadingToFinish();
    expect(screen.getByText('Page Published')).toBeInTheDocument();
    expect(screen.getByText('Back to Dashboard')).toBeInTheDocument();
    expect(screen.queryByText('Upload Batch')).not.toBeInTheDocument();
  });

  test('should render "Success Page" with "Upload Batch" option', async () => {
    renderApp({}, batchPageProps);

    await waitForLoadingToFinish();
    expect(screen.getByText('Page Published')).toBeInTheDocument();
    expect(screen.getByText('Back to Dashboard')).toBeInTheDocument();
    expect(screen.getByText('Upload Batch')).toBeInTheDocument();
  });

  test('should go back to batch pages edit screen when clicked on EDIT PAGE', async () => {
    const { history } = renderApp({}, batchPageProps);

    await waitForLoadingToFinish();
    await userEvent.click(screen.getByText('EDIT PAGE'));

    expect(history.location.pathname).toEqual(`${BATCH_PAYMENT_PAGES_BASE_URL}/pl_validid/edit`);
  });
});
