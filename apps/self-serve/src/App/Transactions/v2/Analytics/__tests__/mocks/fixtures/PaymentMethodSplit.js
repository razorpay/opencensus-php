import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import PaymentMethodSplit from 'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics/TopOverview/PaymentMethodSplit';

jest.mock('react-chartjs-2', () => ({
  Doughnut: ({
    options: {
      tooltips: {
        callbacks: { title, label },
      },
    },
  }) => (
    <div>
      <button
        onMouseOver={() => {
          title([
            {
              index: 0,
            },
          ]);
          label({
            index: 0,
          });
        }}
        onFocus={() => {
          title([
            {
              index: 0,
            },
          ]);
          label({
            index: 0,
          });
        }}
      >
        Doughnut chart
      </button>
    </div>
  ),
}));

export const props = {
  paymentByMethod: [
    {
      label: 'upi',
      expectedLabel: 'UPI',
      value: 50,
    },
    {
      label: 'card',
      expectedLabel: 'Card',
      value: 35,
    },
    {
      label: 'cod',
      expectedLabel: 'Cash on Delivery',
      value: 10,
    },
    {
      label: 'not in map',
      expectedLabel: 'Not In Map',
      value: 5,
    },
  ],
  isMobile: false,
  shouldShowSrBanner: false,
  successRateData: 99,
};

export const renderApp = (extraProps = {}) => {
  render(<PaymentMethodSplit {...props} {...extraProps} />);
};
