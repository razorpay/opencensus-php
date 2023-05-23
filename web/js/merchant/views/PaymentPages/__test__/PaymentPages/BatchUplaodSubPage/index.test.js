import { screen } from 'test-utils';
import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/PaymentPages/BatchUploadSubPage/index';

describe('Batch Payment Page - Create - Batch Upload Subpage', () => {
  test('should render Batch upload subpage with upload option', () => {
    const initialState = {
      session: {
        user: { isBatchPaymentPages: true, showCustomTemplatePP: false },
      },
    };
    renderApp(initialState);
    expect(screen.getByText('Create New Payment Page (Step 2/2)')).toBeInTheDocument();
  });
});
