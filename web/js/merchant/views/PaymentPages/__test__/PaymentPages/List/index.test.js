import { screen, userEvent, waitFor, server, getByText } from 'test-utils';

import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/List/index';
import { paymentPagesErrorHandlers } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/handlers';

describe('Payment Pages -> List (file_upload_pp merchant feature flag of)', () => {
  test('should render payment page list screen', () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: false, showCustomTemplatePP: true },
      },
    };
    renderApp(initialState);
    const paymentPagesLink = screen.getByRole('link', {
      name: 'Payment Pages',
    });
    expect(paymentPagesLink).toBeInTheDocument();
    expect(screen.queryByText('Batch Payment Pages')).not.toBeInTheDocument();
  });
});

describe('Payment Pages -> List (file_upload_pp merchant feature flag on)', () => {
  test('should render payment page list screen along with the tab "Batch Payment Pages"', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, showCustomTemplatePP: true },
      },
    };

    renderApp(initialState, { isBatchPaymentPages: true });
    const paymentPagesLink = screen.getByRole('link', {
      name: 'Payment Pages',
    });
    const batchPaymentPagesLink = screen.getByRole('link', {
      name: 'Batch Payment Pages',
    });
    await userEvent.click(batchPaymentPagesLink);
    expect(batchPaymentPagesLink).toBeInTheDocument();
    await userEvent.click(paymentPagesLink);
    expect(paymentPagesLink).toBeInTheDocument();
  });

  test('should render payment page list screen along with filter & table headers', async () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, showCustomTemplatePP: true },
      },
    };

    renderApp(initialState, { isBatchPaymentPages: true });
    const batchPaymentPagesLink = screen.getByRole('link', {
      name: 'Batch Payment Pages',
    });
    await userEvent.click(batchPaymentPagesLink);
    expect(batchPaymentPagesLink).toBeInTheDocument();
    const searchButton = screen.getByRole('button', { name: 'Search' });
    const clearButton = screen.getByRole('button', { name: 'Clear' });
    await waitFor(() => {
      expect(screen.getByText('Count')).toBeInTheDocument();
      expect(searchButton).toBeInTheDocument();
      expect(clearButton).toBeInTheDocument();
      expect(screen.queryAllByText('Title')[0]).toBeInTheDocument();
      expect(screen.getByText('Total Sales')).toBeInTheDocument();
      expect(screen.getByText('Page Url')).toBeInTheDocument();
    });
  });

  test('should not notify of empty error message when the list api fails', async () => {
    const errorMessage = 'Test Error';

    server.use(paymentPagesErrorHandlers.fetchBatchPagesWithErrors([errorMessage]));

    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, showCustomTemplatePP: true },
      },
    };

    renderApp(initialState, { isBatchPaymentPages: true });

    await waitFor(() => {
      const notificationErrorEl = screen.getByTestId('Notification--error');

      expect(notificationErrorEl).toBeInTheDocument();
      expect(getByText(notificationErrorEl, errorMessage)).toBeInTheDocument();
    });
  });
});
