import React from 'react';
import { Provider } from 'react-redux';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import store from 'merchant/store';
import List from 'merchant/views/Wallet/BatchActions';

import { render } from '@testing-library/react';
import { waitForLoadingToFinish } from 'test-utils';

describe('Batch List Table component', () => {
  it('should render table component', async () => {
    const { container } = render(
      <Provider store={store}>
        <BladeProvider themeTokens={bladeTheme}>
          <List store={store} />
        </BladeProvider>
      </Provider>,
    );

    await waitForLoadingToFinish();

    expect(container.querySelector('tbody')?.children.length).toBe(2);
  });
});
