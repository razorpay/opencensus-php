import { screen, waitForElementToBeRemoved } from 'test-utils';

import { renderApp } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/PaymentPages/BatchUploadSubPage/index';
import { BATCH_UPLOAD_POINTS } from 'merchant/views/PaymentPages/PaymentPages/constants';

describe('Batch Payment Page - Create - Batch Upload Subpage', () => {
  test('should render Batch upload subpage with upload option', async () => {
    const initialState = {
      session: {
        user: { showCustomTemplatePP: false },
      },
    };

    renderApp(initialState, {
      isBatchPaymentPages: true,
      id: 'pl_validid',
    });

    expect(screen.getByText('Create New Payment Page (Step 2/2)')).toBeInTheDocument();
    expect(screen.getByLabelText('Loading...')).toBeInTheDocument();

    await waitForElementToBeRemoved(() => screen.getByLabelText('Loading...'));

    for (let i = 0; i < BATCH_UPLOAD_POINTS.length; i++) {
      expect(screen.getByText(BATCH_UPLOAD_POINTS[i])).toBeInTheDocument();
    }
  });
});
