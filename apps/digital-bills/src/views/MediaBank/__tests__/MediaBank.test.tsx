import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import MediaBank from '@apps/digital-bills/src/views/MediaBank';

describe('MediaBank', () => {
  test('should render MediaBank component', async () => {
    const { getByText } = renderWithWrappers(<MediaBank />);
    expect(getByText('Media Bank')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    const iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/auto-engage/uploadedData');
  });
});
