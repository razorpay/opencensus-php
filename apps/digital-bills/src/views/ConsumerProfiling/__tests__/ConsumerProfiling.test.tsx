import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import ConsumerProfiling from '@apps/digital-bills/src/views/ConsumerProfiling';

describe('ConsumerProfiling', () => {
  test('should render ConsumerProfiling component', async () => {
    const { getByText } = renderWithWrappers(<ConsumerProfiling />);
    expect(getByText('Consumer Profiling')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    const iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/consumer-profiling');
  });
});
