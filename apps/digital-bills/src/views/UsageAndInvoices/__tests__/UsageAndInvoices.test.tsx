import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import UsageAndInvoices from '@apps/digital-bills/src/views/UsageAndInvoices';

describe('UsageAndInvoices', () => {
  test('should render UsageAndInvoices component', async () => {
    const { getByText } = renderWithWrappers(<UsageAndInvoices />);
    expect(getByText('Usage And Invoices')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    const iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/usage-and-invoices');
  });
});
