import React from 'react';

import DowntimeBanner from 'merchant/views/Transactions/v2/Analytics/LandingAnalytics/DowntimeBanner';
import { screen, render } from 'test-utils';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      enable_downtime_banner: {
        variables: {
          result: 'on',
        },
      },
    },
  }),
}));

jest.mock('merchant/views/EcosystemDowntimes/context', () => ({
  ...jest.requireActual('merchant/views/EcosystemDowntimes/context'),
  EcosystemDowntimeProvider: ({ children }) => (
    <div>
      <p>
        We are currently experiencing downtime on Bank Of India that may impact your card payments.
        We are working with the bank(s) to resolve this at the earliest
      </p>
      <div>{children}</div>
    </div>
  ),
}));

const renderApp = () => {
  render(<DowntimeBanner />);
};

describe('DowntimeBanner', () => {
  test('Render downtime banner', () => {
    renderApp();
    const message = screen.getByText(
      'We are currently experiencing downtime on Bank Of India that may impact your card payments. We are working with the bank(s) to resolve this at the earliest',
    );
    expect(message).toBeInTheDocument();
  });
});
