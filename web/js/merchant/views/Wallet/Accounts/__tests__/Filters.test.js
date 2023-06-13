import Filters from 'merchant/views/Wallet/Accounts/Filters';

import { render, screen, waitFor, userEvent } from 'test-utils';

describe('Wallet > Accounts > Filters', () => {
  test('Should render all filters', async () => {
    render(<Filters />);
    await waitFor(() => {
      // Ensure all the filters fields exist
      expect(screen.getByText('Contact')).toBeInTheDocument();
      expect(screen.getByText('Account Id')).toBeInTheDocument();
      expect(screen.getByText('User Id')).toBeInTheDocument();
      expect(screen.getByText('Status')).toBeInTheDocument();

      // Ensure all the search/clear buttons exist
      expect(screen.getByText('Search')).toBeInTheDocument();
      expect(screen.getByText('Clear')).toBeInTheDocument();
    });
  });

  test('Should receive contact in callback when input entered', async () => {
    const input = '9999999999';
    const mock = jest.fn(() => {});

    render(<Filters onSubmit={mock} />);

    await userEvent.type(screen.getByTestId('contact'), input);
    await userEvent.click(screen.getByText('Search'));

    expect(mock.mock.calls[0][0]).toMatchObject({ contact: window.btoa(input) });
  });

  test('Should receive status in callback when status selected', async () => {
    const status = 'active';
    const mock = jest.fn(() => {});

    render(<Filters onSubmit={mock} />);

    await userEvent.selectOptions(screen.getByTestId('status'), 'Active');
    await userEvent.click(screen.getByText('Search'));

    expect(mock.mock.calls[0][0]).toMatchObject({ status });
  });

  test('Should receive id in callback when input entered', async () => {
    const input = 'iacc_Ly9Ey7bZEttXUL';
    const mock = jest.fn(() => {});

    render(<Filters onSubmit={mock} />);

    await userEvent.type(screen.getByTestId('account_id'), input);
    await userEvent.click(screen.getByText('Search'));

    expect(mock.mock.calls[0][0]).toMatchObject({ issuing_account_id: input });
  });

  test('Should receive user_id in callback when input entered', async () => {
    const input = 'iuser_Ly9Ey7bZEttXUL';
    const mock = jest.fn(() => {});

    render(<Filters onSubmit={mock} />);

    await userEvent.type(screen.getByTestId('user_id'), input);
    await userEvent.click(screen.getByText('Search'));

    expect(mock.mock.calls[0][0]).toMatchObject({ user_id: input });
  });
});
