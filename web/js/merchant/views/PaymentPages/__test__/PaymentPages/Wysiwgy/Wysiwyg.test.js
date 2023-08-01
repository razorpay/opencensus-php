import { screen, userEvent } from 'test-utils';
import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/Wysiwyg';
import { userState } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/AddAmountButton';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';

describe('Payment Page Creation', () => {
  test('"Create your Own" template should be there while template selection.', () => {
    renderApp();
    expect(screen.getByText('Create your Own')).toBeInTheDocument();
  });

  test('"Create your Own" template should not be there while template selection.', () => {
    const initialState = {
      session: {
        user: {
          ...userState,
          isPaymentPageFileUploadEnabled: false,
          showCustomTemplatePP: false,
        },
        org: {
          features: ['hide_create_new_tmpl_pp'],
        },
      },
    };
    renderApp(initialState);
    expect(screen.queryByText('Create your Own')).not.toBeInTheDocument();
  });
});

describe('Batch Payment Page Creation', () => {
  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      paymentPages: () => ({
        interaction: jest.fn(),
      }),
    };
  });
  test('"Create your Own" template should be there while template selection.', () => {
    const initialState = {
      session: {
        user: {
          ...userState,
          isPaymentPageFileUploadEnabled: false,
          showCustomTemplatePP: true,
        },
      },
    };
    renderApp(initialState);
    expect(screen.getByText('Create your Own')).toBeInTheDocument();
  });

  test('"Create your Own" template should not be there while template selection.', () => {
    const initialState = {
      session: {
        user: {
          ...userState,
          isPaymentPageFileUploadEnabled: true,
          showCustomTemplatePP: true,
        },
      },
    };

    renderApp(initialState, { isBatchPaymentPages: true });
    expect(screen.queryByText('Create your Own')).not.toBeInTheDocument();
    expect(screen.getByText('Create New Payment Page (Step 1/2)')).toBeInTheDocument();
  });

  test('should able to create "Create your Own" template', () => {
    renderApp();
    const createYourOwnTemplate = screen.getByText('Create your Own');
    expect(createYourOwnTemplate).toBeInTheDocument();
    userEvent.click(createYourOwnTemplate);
    expect(screen.getByText('Create New Payment Page'));
  });

  test('should be able to go back to batch payment pages tab when close button is clicked', async () => {
    const { history } = renderApp({}, { isBatchPaymentPages: true });

    // Click on close button.
    await userEvent.click(screen.getByTestId('close-btn'));
    // Opens a confirm modal and click on yes button to back to batch payment pages tab.
    await userEvent.click(screen.getByRole('button', { name: 'Yes' }));

    expect(history.location.pathname).toEqual(BATCH_PAYMENT_PAGES_BASE_URL);
  });
});
