import { screen } from 'test-utils';
import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/Wysiwyg';

describe('Payment Page Creation', () => {
  test('"Create your Own" template should be there while template selection.', () => {
    renderApp();
    expect(screen.getByText('Create your Own')).toBeInTheDocument();
  });

  test('"Create your Own" template should not be there while template selection.', () => {
    const initialState = {
      session: {
        user: {},
        org: {
          features: ['hide_create_new_tmpl_pp'],
        },
      },
    };
    renderApp(initialState);
    expect(screen.queryByText('Create your Own')).not.toBeInTheDocument();
  });
});
