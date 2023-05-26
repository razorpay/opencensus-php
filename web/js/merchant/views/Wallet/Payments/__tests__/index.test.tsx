import React from 'react';
import { render, screen, userEvent, waitForLoadingToFinish } from 'common/services/test/test-utils';
import Filters from 'merchant/views/Wallet/Payments/Filters';
import Payments from 'merchant/views/Wallet/Payments';

const renderPayments = () => {
  render(<Payments />);
};

describe('Wallet > Payments', () => {
  test('Should show filters for account id', async () => {
    renderPayments();

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

  test('Should display Payments table with expected rows', () => {
    renderPayments();

    expect(screen.getAllByRole('rowgroup')?.[0]?.children.length).toBe(1);
  });

  test('Should render table with expected columns and data', async () => {
    renderPayments();

    await waitForLoadingToFinish();

    expect(screen.getByText('Payment Id')).toBeInTheDocument();
    expect(screen.getByText('ipayment_qwerty87654321')).toBeInTheDocument();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('50')).toBeInTheDocument();
    expect(screen.getByText('Created At')).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Success')).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByText('Description for this payment')).toBeInTheDocument();
  });
});
