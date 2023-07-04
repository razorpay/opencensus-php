import React from 'react';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { render, screen } from 'test-utils';
import RiskLevelOrderSplitCumulative from 'merchant/views/MagicCheckout/RTOAnalytics/common/RiskLevelOrderSplitCumulative';

const INIT_STATE = {
  magicRTOAnalytics: {
    manual_risk_order_split_cumulative: {
      data: [
        {
          total_order: 50,
          high_risk_order: 25,
          medium_risk_order: 0,
        },
      ],
    },
  },
};
const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <RiskLevelOrderSplitCumulative {...props} />
    </Provider>,
  );
};

const HEADER_OBJECT = [
  {
    label: 'Total orders',
    value: 50,
  },
  {
    label: 'High risk orders',
    value: 25,
  },
  {
    label: 'Medium risk orders',
    value: 0,
  },
  {
    label: 'Low risk orders',
    value: '--',
  },
];

describe('testing manual review cumultative order component', () => {
  test.each([HEADER_OBJECT])('should render properly', (item) => {
    renderApp();
    expect(screen.getByText(new RegExp(item.label, 'i'))).toBeInTheDocument();
    expect(screen.getByText(item.value)).toBeInTheDocument();
  });
});
