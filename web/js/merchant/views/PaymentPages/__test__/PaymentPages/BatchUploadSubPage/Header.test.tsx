import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import Header from 'merchant/views/PaymentPages/PaymentPages/BatchUploadSubPage/Header';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';

describe('Header', () => {
  test('should navigate to batch payment success page when clicked on publish', async () => {
    const batchPageId = 'pl_test_id';
    const { history } = render(<Header id={batchPageId} />);

    await userEvent.click(screen.getByTestId('bpp-publish-btn'));

    expect(history.location.pathname).toEqual(
      `${BATCH_PAYMENT_PAGES_BASE_URL}/${batchPageId}/success`,
    );
  });
});
