import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { createMemoryHistory } from 'history';

import PaymentsListFilter from 'merchant/views/Transactions/v1/Payments/components/PaymentsListFilter';
import { render, screen, userEvent, fireEvent } from 'test-utils';

let history;
jest.mock('common/ui/DateRangePicker', () => () => <div>DateRangePicker</div>);

describe('PaymentsListFilter', () => {
  const defaultProps = {
    terminalProviders: [
      {
        Terminal_id: 'terminal name',
        Provider_name: 'provider name',
      },
    ],
    form: 'payments-list-filter',
    showBatchIdFilter: true,
    user: {
      isSingleReconEnabled: true,
      isOptimizerEnabled: true,
      isOmniChannelMerchant: true,
      merchant: {
        country_code: 'IN',
      },
    },
  };

  const App = (props) => {
    return <PaymentsListFilter {...defaultProps} {...props} />;
  };

  beforeAll(() => {
    history = createMemoryHistory();
    history.push = jest.fn();
  });

  test('should render payment list filters', () => {
    render(<App />);
    [
      'Payment Id',
      'Duration',
      'Batch Id',
      'Status',
      'Email',
      'Phone number',
      'Processed by',
      'Notes',
      'Receiver Type',
      'Bank Reference Number',
    ].forEach((filter) => expect(screen.getByText(filter)).toBeInTheDocument());
  });

  test('should allow to search by payment id', async () => {
    render(<App />);
    const paymentIdFilter = screen.getAllByRole('textbox')[0];
    const paymentId = '123456';
    await userEvent.type(paymentIdFilter, paymentId);
    expect(paymentIdFilter).toHaveAttribute('value', paymentId);
  });

  test('should allow to search by batch id', async () => {
    render(<App />);
    const batchIdFilter = screen.getAllByRole('textbox')[1];
    const batchId = '123456';
    await userEvent.type(batchIdFilter, batchId);
    expect(batchIdFilter).toHaveAttribute('value', batchId);
  });

  test('should allow to search by email id', async () => {
    render(<App />);
    const emailIdFilter = screen.getAllByRole('textbox')[2];
    const emailId = '123456@gmail.com';
    await userEvent.type(emailIdFilter, emailId);
    expect(emailIdFilter).toHaveAttribute('value', emailId);
  });

  test('should allow to search by readonly terminal id', () => {
    render(<App />);
    const terminalIdFilter = screen.getAllByRole('textbox')[4];
    expect(terminalIdFilter).toHaveAttribute('readOnly', '');
    expect(terminalIdFilter).toHaveAttribute('value', 'All');
  });

  test('should allow to search by notes', async () => {
    render(<App />);
    const notesFilter = screen.getAllByRole('textbox')[5];
    const notes = 'note text';
    await userEvent.type(notesFilter, notes);
    expect(notesFilter).toHaveAttribute('value', notes);
  });

  test('should allow to search by status', async () => {
    render(<App />);
    await userEvent.selectOptions(screen.getAllByRole('combobox')[0], ['authorized']);
    expect(screen.getByRole('option', { name: 'Authorized' }).selected).toBe(true);
  });

  test('should allow to search by receiver type', async () => {
    render(<App />);
    await userEvent.selectOptions(screen.getAllByRole('combobox')[1], ['offline']);
    expect(screen.getByRole('option', { name: 'Offline' }).selected).toBe(true);
  });

  test('should allow to search by bank reference number', async () => {
    render(<App />);
    const bankReferenceNumberFilter = screen.getAllByRole('textbox')[5];
    const bankReferenceNumber = '123456';
    await userEvent.type(bankReferenceNumberFilter, bankReferenceNumber);
    expect(bankReferenceNumberFilter).toHaveAttribute('value', bankReferenceNumber);
  });

  test('should filter transaction and hit search anayltics with current params', async () => {
    const onSearchAnalytics = jest.fn();
    const props = {
      history: {
        push: jest.fn(),
      },
      onSearchAnalytics,
      onSubmit: jest.fn(),
    };
    render(<App {...props} />);
    const paymentIdFilter = screen.getAllByRole('textbox')[0];
    const paymentId = '123456';
    await userEvent.type(paymentIdFilter, paymentId);
    await userEvent.selectOptions(screen.getAllByRole('combobox')[1], ['offline']);

    const submitButton = screen.getByText('Search');
    fireEvent.click(submitButton);

    expect(onSearchAnalytics).toHaveBeenCalledWith(
      expect.objectContaining({
        id: '123456',
        from: '',
        to: '',
        terminal_id: '',
        txn_receiver_type: 'offline',
      }),
      expect.any(String),
    );
  });

  test('should filter transaction and remove query params from url if present in filtersToHideInQueryParams', async () => {
    const onSearchAnalytics = jest.fn();
    const props = {
      filtersToHideInQueryParams: ['txn_receiver_type'],
      history: {
        push: jest.fn(),
      },
      onSearchAnalytics,
      onSubmit: jest.fn(),
    };
    render(<App {...props} />);
    const paymentIdFilter = screen.getAllByRole('textbox')[0];
    const paymentId = '123456';
    await userEvent.type(paymentIdFilter, paymentId);
    await userEvent.selectOptions(screen.getAllByRole('combobox')[1], ['offline']);

    const submitButton = screen.getByText('Search');
    fireEvent.click(submitButton);

    expect(onSearchAnalytics).toHaveBeenCalledWith(
      expect.objectContaining({
        id: '123456',
        from: '',
        to: '',
        terminal_id: '',
      }),
      expect.any(String),
    );
  });

  test('should have dial-code +60 for MY merchants', () => {
    const props = {
      user: {
        merchant: {
          country_code: 'MY',
        },
      },
    };
    render(<App {...props} />);
    expect(screen.getByTestId('dialCodeValue')).toHaveTextContent('+60');
  });

  test('should have dial-code +91 for IN merchants', () => {
    render(<App />);
    expect(screen.getByTestId('dialCodeValue')).toHaveTextContent('+91');
  });
});
