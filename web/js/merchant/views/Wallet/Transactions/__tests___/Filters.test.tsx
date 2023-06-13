import React from 'react';
import Filters from 'merchant/views/Wallet/Transactions/Filters';

import { render, screen, waitFor, userEvent } from 'test-utils';

import { TransactionFilterParams } from 'merchant/views/Wallet/types';

describe('Wallet > Accounts > Filters', () => {
  test('Should render all filters', async () => {
    render(<Filters onSubmit={jest.fn()} />);
    await waitFor(() => {
      // Ensure all the filters fields exist
      expect(screen.getByText('Transaction Id')).toBeInTheDocument();
      expect(screen.getByText('Duration')).toBeInTheDocument();

      // Ensure all the search/clear buttons exist
      expect(screen.getByText('Search')).toBeInTheDocument();
      expect(screen.getByText('Clear')).toBeInTheDocument();
    });
  });

  test('Should receive id in callback when input entered', async () => {
    const id = 'I9eCvXfHx7nzZF';
    const mock = jest.fn<void, TransactionFilterParams[]>(() => {});

    render(<Filters onSubmit={mock} />);

    await userEvent.type(screen.getByTestId('id'), id);
    await userEvent.click(screen.getByText('Search'));

    expect(mock.mock.calls[0][0]).toMatchObject({ id });
  });
});
