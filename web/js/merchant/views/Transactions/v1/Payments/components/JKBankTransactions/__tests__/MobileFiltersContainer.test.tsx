import React from 'react';

import { screen, render, waitFor, act, userEvent } from 'common/services/test/test-utils';
import MobileFilterContainer from 'merchant/views/Transactions/v1/Payments/components/JKBankTransactions/MobileFilterContainer';

const App = (props) => <MobileFilterContainer {...props} />;

jest.setTimeout(240 * 1000);
describe('<MobileFilterContainer/>', () => {
  test('Should render correctly', () => {
    render(<App />, {});
    expect(screen.getByText(/All transactions/i)).toBeInTheDocument();
  });

  test('Should render bottm sheet on filter click', async () => {
    render(<App />, {});
    const filterBtn = screen.getByRole('button', {
      name: /Filters/i,
    });
    expect(filterBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(filterBtn);
    });
    await waitFor(() => {
      expect(screen.getByText(/Payment Id/i)).toBeInTheDocument();
    });
  });

  test('Should refresh transactions if refresh button is clicked', async () => {
    const submitMock = jest.fn();
    render(<App onSubmit={submitMock} />, {});
    const refreshBtn = screen.getByTestId('refresh-btn');
    expect(refreshBtn).toBeInTheDocument();

    await act(async () => {
      await userEvent.click(refreshBtn);
    });

    await waitFor(() => {
      expect(submitMock).toHaveBeenCalled();
    });
  });

  test('Should close bottom sheet on cancel filter click', async () => {
    const submitMock = jest.fn();
    render(<App onSubmit={submitMock} />, {});
    const filterBtn = screen.getByRole('button', {
      name: /Filters/i,
    });
    expect(filterBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(filterBtn);
    });

    await waitFor(() => {
      expect(
        screen.getByRole('button', {
          name: /Cancel/i,
        }),
      ).toBeInTheDocument();
    });

    await act(async () => {
      await userEvent.click(
        screen.getByRole('button', {
          name: /Cancel/i,
        }),
      );
    });

    await waitFor(() => {
      expect(screen.queryByText(/Payment Id/i)).toBeNull();
    });
  });

  test('Should render status bottom sheet on status click', async () => {
    render(<App />, {});
    const filterBtn = screen.getByRole('button', {
      name: /Filters/i,
    });
    expect(filterBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(filterBtn);
    });
    await waitFor(() => {
      expect(screen.getByRole('combobox', { name: /Status/i })).toBeInTheDocument();
    });

    await act(async () => {
      await userEvent.click(screen.getByText('Captured'), { pointerEventsCheck: 0 });
    });
  });

  test('Form submit should work as expected', async () => {
    const submitMock = jest.fn();
    render(<App onSubmit={submitMock} />, {});
    const filterBtn = screen.getByRole('button', {
      name: /Filters/i,
    });
    expect(filterBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(filterBtn);
    });

    await waitFor(() => {
      expect(
        screen.getByRole('button', {
          name: /Apply/i,
        }),
      ).toBeInTheDocument();
    });

    await act(async () => {
      await userEvent.type(screen.getByLabelText('Payment Id'), 'pay_1234');
      await userEvent.type(screen.getByLabelText('Payment Reference Number'), '1234');
      await userEvent.type(screen.getByLabelText('Bank Reference Number'), '1234');
      await userEvent.click(
        screen.getByRole('button', {
          name: /Apply/i,
        }),
      );
    });

    await waitFor(() => {
      expect(submitMock).toHaveBeenCalledWith(
        expect.objectContaining({
          id: 'pay_1234',
          rrn: '1234',
          va_transaction_id: '1234',
        }),
      );
    });
  });

  test('Date filter should work as expected', async () => {
    const submitMock = jest.fn();
    render(<App onSubmit={submitMock} />, {});
    const filterBtn = screen.getByLabelText(/From/i);
    expect(filterBtn).toBeInTheDocument();
    const dateBtn = screen.getAllByPlaceholderText('DD/MM/YYYY');
    expect(dateBtn).toHaveLength(2);
    await act(async () => {
      await userEvent.click(dateBtn[0]);
    });

    await waitFor(() => {
      expect(screen.getByText('Last 7 days')).toBeInTheDocument();
      expect(
        screen.getByRole('button', {
          name: /Apply/i,
        }),
      ).toBeInTheDocument();
    });

    await act(async () => {
      await userEvent.click(screen.getByText('Last 7 days'));
      await userEvent.click(
        screen.getByRole('button', {
          name: /Apply/i,
        }),
      );
    });

    await waitFor(() => {
      expect(submitMock).toHaveBeenCalledWith(
        expect.objectContaining({
          from: expect.any(Number),
          to: expect.any(Number),
        }),
      );
    });
  });
});
