import React from 'react';
import { render, screen } from 'test-utils';
import StatsCard from 'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/StatsCard';

jest.mock(
  'merchant/views/Transactions/v2/UploadInvoices/components/InvoiceStats/StatsLoader',
  () => () => <div>Loading...</div>,
);

const defaultProps = {
  type: 'invoice_auto_synced',
  name: 'Auto Synced Invoices',
  tooltipText: 'Invoices that are automatically synced',
  count: '10',
  isLoading: false,
  history: { push: jest.fn() },
};

const renderApp = (props = {}) => {
  render(<StatsCard {...defaultProps} {...props} />);
};

describe('Test Cases for StatsCard', () => {
  test('Should render name, count', () => {
    renderApp();

    expect(screen.getByText('Auto Synced Invoices')).toBeInTheDocument();
    expect(screen.getByText('10')).toBeInTheDocument();
  });

  test('Should render StatsLoader when isLoading is true', () => {
    renderApp({ isLoading: true });
    expect(screen.getByText('Loading...')).toBeInTheDocument();
  });

  test('Should show correct count when count prop is provided', () => {
    renderApp({ count: '20' });

    expect(screen.getByText('20')).toBeInTheDocument();
  });

  test('Should show default count as 0 if count is not provided', () => {
    renderApp({ count: undefined });

    expect(screen.getByText('0')).toBeInTheDocument();
  });
});
