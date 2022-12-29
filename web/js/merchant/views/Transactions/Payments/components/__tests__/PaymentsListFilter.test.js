import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentsListFilter from 'merchant/views/Transactions/Payments/components/PaymentsListFilter';
import { render, screen, userEvent } from 'test-utils';

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
    },
  };

  const App = (props) => {
    return <PaymentsListFilter {...defaultProps} {...props} />;
  };

  test('should render payment list filters', () => {
    render(<App />);
    [
      'Payment Id',
      'Duration',
      'Batch Id',
      'Status',
      'Email',
      'Phone',
      'Processed by',
      'Notes',
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

  test('should allow to search by bank reference number', async () => {
    render(<App />);
    const bankReferenceNumberFilter = screen.getAllByRole('textbox')[5];
    const bankReferenceNumber = '123456';
    await userEvent.type(bankReferenceNumberFilter, bankReferenceNumber);
    expect(bankReferenceNumberFilter).toHaveAttribute('value', bankReferenceNumber);
  });

  test('should allow to search by status', async () => {
    render(<App />);
    await userEvent.selectOptions(screen.getByRole('combobox'), ['authorized']);
    expect(screen.getByRole('option', { name: 'Authorized' }).selected).toBe(true);
  });
});
