import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import AutoEngagement from '@apps/digital-bills/src/views/AutoEngagement';

describe('AutoEngagement', () => {
  test('should render AutoEngagement component', () => {
    const { getByText } = renderWithWrappers(<AutoEngagement />);
    expect(getByText('Auto Engagement')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    const iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/journey');
  });
});
