import React from 'react';
import { render, screen, userEvent, waitForLoadingToFinish } from 'common/services/test/test-utils';
import Loads from 'merchant/views/Wallet/Loads';
import Filters from 'merchant/views/Wallet/Loads/Filters';

const renderLoads = () => {
  render(<Loads />);
};

describe('Wallet > Loads', () => {
  test('Should show filters for account id', async () => {
    renderLoads();

    const accountIdLabel = await screen.findByText(/Account Id/i);
    expect(accountIdLabel).toBeInTheDocument();
    expect(screen.getByText('Search')).toBeInTheDocument();
    expect(screen.getByText('Clear')).toBeInTheDocument();
  });

  test('Should receive account id in callback when id is entered', async () => {
    const input = 'iacc_abcdef12345678';
    const mock = jest.fn(() => {});

    render(<Filters onSubmit={mock} />);

    await userEvent.type(screen.getByTestId('accountId'), input);
    await userEvent.click(screen.getByText('Search'));

    expect(mock).toBeCalledWith({ accountId: input });
  });

  test('Should display loads table with expected rows', () => {
    renderLoads();

    expect(screen.getAllByRole('rowgroup')?.[0]?.children.length).toBe(1);
  });

  test('Should render table with expected columns and data', async () => {
    render(<Loads />);

    await waitForLoadingToFinish();

    expect(screen.getByText('Load Id')).toBeInTheDocument();
    expect(screen.getByText('iload_qwerty87654321')).toBeInTheDocument();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('50')).toBeInTheDocument();
    expect(screen.getByText('Type')).toBeInTheDocument();
    expect(screen.getByText('User')).toBeInTheDocument();
    expect(screen.getByText('Created At')).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Success')).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByText('Description for this load')).toBeInTheDocument();
  });
});
