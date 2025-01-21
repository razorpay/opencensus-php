import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import CustomerSegmentation from '@apps/digital-bills/src/views/CustomerSegmentation';

describe('CustomerSegmentation', () => {
  test('should render CustomerSegmentation component', async () => {
    const { getByText } = renderWithWrappers(<CustomerSegmentation />);
    expect(getByText('Customer Segmentation')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    const iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/segment');
  });
});
