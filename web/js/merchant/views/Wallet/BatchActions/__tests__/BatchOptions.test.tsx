import React from 'react';

import { render, getAllByTestId, fireEvent, screen } from '@testing-library/react';

import {
  LoadsBatchUpload,
  AccountsBatchUpload,
  ReversalsBatchUpload,
} from 'merchant/views/Wallet/BatchActions/BatchUpload';
import { CreateBatchOptions } from 'merchant/views/Wallet/BatchActions/BatchOptions';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

jest.mock('common/splitz/utils', () => ({
  isExperimentEnabled: () => true,
}));

const storeState = {
  session: {
    user: {
      user: {
        contact_mobile: '9999999999',
      },
      merchant: {
        country_code: 'IN',
      },
    },
  },
};

describe('BatchOptions tests', () => {
  test('it should render expected options', () => {
    const { container } = render(
      <CreateBatchOptions
        batches={{
          items: [
            { type: 'create_wallet_accounts', status: 'processed' },
            { type: 'create_wallet_accounts', status: 'processing' },
            { type: 'create_wallet_loads', status: 'processing' },
          ],
        }}
        openModal={jest.fn()}
      />,
    );

    expect(getAllByTestId(container, 'batch-type-option')).toHaveLength(5);
  });

  test('it should call openModal with expected properties', async () => {
    const mock = jest.fn();
    render(
      <CreateBatchOptions
        batches={{
          items: [
            { type: 'create_wallet_accounts', status: 'processed' },
            { type: 'create_wallet_accounts', status: 'processing' },
            { type: 'create_wallet_loads', status: 'processing' },
          ],
        }}
        openModal={mock}
      />,
    );

    await fireEvent.click(screen.getByText('Accounts'));
    await fireEvent.click(screen.getByText('Loads'));
    await fireEvent.click(screen.getByText('Reversals'));

    expect(mock).toHaveBeenCalledTimes(3);
    expect(mock.mock.calls[0][0]).toEqual({
      component: <AccountsBatchUpload />,
      size: 'large',
    });
    expect(mock.mock.calls[1][0]).toEqual({
      component: <LoadsBatchUpload />,
      size: 'large',
    });
    expect(mock.mock.calls[2][0]).toEqual({
      component: <ReversalsBatchUpload />,
      size: 'large',
    });
  });

  test('it should display a dropdown to select load type', async () => {
    render(
      <Provider store={storeWithInitialState(storeState)}>
        <BladeProvider themeTokens={bladeTheme}>
          <LoadsBatchUpload />
        </BladeProvider>
      </Provider>,
    );

    expect(screen.getByText('Load Type')).toBeInTheDocument();
    await fireEvent.click(screen.getByTestId('test-load-dropdown'));
    const containerLoadOption = screen.getByText('Container Load');
    expect(containerLoadOption).toBeInTheDocument();
    await fireEvent.click(containerLoadOption);
    expect(containerLoadOption).toBeInTheDocument();
  });

  test('it should display create batch reversals modal', () => {
    render(
      <Provider store={storeWithInitialState(storeState)}>
        <BladeProvider themeTokens={bladeTheme}>
          <ReversalsBatchUpload />
        </BladeProvider>
      </Provider>,
    );

    expect(screen.getByText('Create Batch Reversals')).toBeInTheDocument();
  });
});
