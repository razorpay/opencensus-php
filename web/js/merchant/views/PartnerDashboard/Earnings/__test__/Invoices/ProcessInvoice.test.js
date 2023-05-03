import React from 'react';
import { render, screen, userEvent } from 'common/services/test/test-utils';
import ProcessInvoice from 'merchant/views/PartnerDashboard/Earnings/Invoices/ProcessInvoice';

const defaultProps = {
  className: 'process-invoice',
  commissionInvoice: {
    id: 'comm_G8vny1PSg5hY5Q',
  },
};
describe('test suite for Earnings Process Invoice', () => {
  test('should render process invoice', () => {
    render(<ProcessInvoice {...defaultProps} />);
    expect(screen.getByRole('button', { name: 'Process Invoice' })).toBeVisible();
  });
  test('should open form on process invoice and confirm button should be clickable', async () => {
    render(<ProcessInvoice {...defaultProps} />);
    const button = screen.getByRole('button', { name: 'Process Invoice' });
    await userEvent.click(button);
    expect(screen.getByText('Are you sure you want to process this invoice?')).toBeVisible();
    const confirmButton = screen.getByRole('button', { name: 'Yes, Process' });
    expect(confirmButton).toBeVisible();
    await userEvent.click(confirmButton);
  });
});
