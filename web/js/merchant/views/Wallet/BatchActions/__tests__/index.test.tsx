import React from 'react';
import { Provider } from 'react-redux';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import store from 'merchant/store';
import List from 'merchant/views/Wallet/BatchActions';

import { render } from '@testing-library/react';
import { waitForLoadingToFinish } from 'test-utils';

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{
          abExperiments: {
            pos_sales_agent: {
              variables: {
                result: 'off',
              },
            },
          },
        }}
      />
    ),
  useSplitzService: () => ({
    abExperiments: {
      create_bulk_gift_cards: true,
      gift_cards_transfer: true,
    },
  }),
}));

describe('Batch List Table component', () => {
  it('should render table component', async () => {
    const { container } = render(
      <Provider store={store}>
        <BladeProvider themeTokens={bladeTheme}>
          <List store={store} />
        </BladeProvider>
      </Provider>,
    );

    await waitForLoadingToFinish('table-spinner');

    expect(container.querySelector('tbody')?.children.length).toBe(2);
  });
});
