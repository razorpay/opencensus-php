import { screen, userEvent } from 'test-utils';
import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/Wysiwyg';

describe('Payment Page Creation', () => {
  test('"Create your Own" template should be there while template selection.', () => {
    renderApp();
    expect(screen.getByText('Create your Own')).toBeInTheDocument();
  });

  test('"Create your Own" template should not be there while template selection.', () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: false, showCustomTemplatePP: false },
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
        user: { isPaymentPageFileUploadEnabled: false, showCustomTemplatePP: true },
      },
    };
    renderApp(initialState);
    expect(screen.getByText('Create your Own')).toBeInTheDocument();
  });

  test('"Create your Own" template should not be there while template selection.', () => {
    const initialState = {
      session: {
        user: { isPaymentPageFileUploadEnabled: true, showCustomTemplatePP: true },
      },
      wysiwyg: { isBatchPaymentPages: true },
    };

    renderApp(initialState);
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
});
