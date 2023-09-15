import React from 'react';
import { render, screen } from 'test-utils';
import DemoVideo from 'merchant/views/MagicCheckout/AnalyticsSettings/common/DemoVideo';

describe('demo video component', () => {
  test('component should render properly', () => {
    render(
      <DemoVideo
        infoText="follow the GA4 dashboard guidelines"
        demoVideoLink="https://cdn.razorpay.com/static/assets/magic-checkout/shiprocket-demo-step-1.mp4"
      />,
    );

    expect(screen.getByText('follow the GA4 dashboard guidelines')).toBeInTheDocument();
  });
});
