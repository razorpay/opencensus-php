import React from 'react';
import { render, screen, userEvent, waitForLoadingToFinish } from 'common/services/test/test-utils';
import Loads from 'merchant/views/Wallet/Loads';
import Filters from 'merchant/views/Wallet/Loads/Filters';

const renderLoads = () => {
  render(<Loads />);
};

describe('Wallet > Loads', () => {
  test('Should show all filters', async () => {
    render(<Filters onSubmit={jest.fn} />);

    const accountIdLabel = await screen.queryByText(/Account Id/i);
    const loadIdLabel = await screen.queryByText(/Load Id/i);
    const durationLabel = await screen.getByText(/Duration/i);

    expect(loadIdLabel).toBeInTheDocument();
    expect(durationLabel).toBeInTheDocument();
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

    expect(mock).toBeCalledWith({ account_id: input, from: '', to: '' });
  });

  test('Should receive load id in callback when id is entered', async () => {
    const input = 'iload_abcdef12345678';
    const mock = jest.fn(() => {});

    render(<Filters onSubmit={mock} />);

    await userEvent.type(screen.getByTestId('loadId'), input);
    await userEvent.click(screen.getByText('Search'));

    expect(mock).toBeCalledWith({ id: input, from: '', to: '' });
  });

  test('Should display loads table with expected rows', () => {
    renderLoads();

    expect(screen.getAllByRole('rowgroup')?.[0]?.children.length).toBe(1);
  });

  test('Should render table with expected columns and data', async () => {
    render(<Loads />);

    await waitForLoadingToFinish();

    expect(screen.getByText('iload_qwerty87654321')).toBeInTheDocument();
    expect(screen.getByText('50')).toBeInTheDocument();
    expect(screen.getByText('User')).toBeInTheDocument();
    expect(screen.getByText('Success')).toBeInTheDocument();
    expect(screen.getByText('Description for this load')).toBeInTheDocument();
  });
});
