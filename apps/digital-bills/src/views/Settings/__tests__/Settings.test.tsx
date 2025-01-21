import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import Settings from '@apps/digital-bills/src/views/Settings';

describe('Settings', () => {
  test('should render Settings component', async () => {
    const { getByText } = renderWithWrappers(<Settings />);
    expect(getByText('Settings')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    const iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/settings');
  });
});
