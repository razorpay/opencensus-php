import React from 'react';

import { render, getAllByTestId, fireEvent, getByText } from '@testing-library/react';

import { CreateBatchOptions } from 'merchant/views/Wallet/BatchActions/BatchOptions';
import {
  AccountsBatchUpload,
  LoadsBatchUpload,
} from 'merchant/views/Wallet/BatchActions/BatchUpload';

describe('BatchOptions tests', () => {
  test('it should render expected options', () => {
    const { container } = render(<CreateBatchOptions openModal={jest.fn()} />);

    expect(getAllByTestId(container, 'batch-type-option')).toHaveLength(2);
  });

  test('it should call openModal with expected properties', async () => {
    const mock = jest.fn();
    const { container } = render(<CreateBatchOptions openModal={mock} />);

    await fireEvent.click(getByText(container, 'Accounts'));
    await fireEvent.click(getByText(container, 'Loads'));

    expect(mock).toHaveBeenCalledTimes(2);
    expect(mock.mock.calls[0][0]).toEqual({
      component: <AccountsBatchUpload />,
      size: 'large',
    });
    expect(mock.mock.calls[1][0]).toEqual({
      component: <LoadsBatchUpload />,
      size: 'large',
    });
  });
});
