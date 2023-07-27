import React from 'react';
import { render, screen } from 'test-utils';
import { STATUS_MAP } from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/constants';
import ActivationStatus from 'merchant/views/Settings/PaymentMethods/components/InternationalPayments/ActivationStatus';

describe('Test ActivationStatus', () => {
  test('renders activation status for in review status', () => {
    render(<ActivationStatus status={STATUS_MAP.in_review} />);
    expect(
      screen.getByText(/Your request to enable international payments has been received/i),
    ).toBeInTheDocument();
  });

  test('renders activation status for rejected status', () => {
    render(<ActivationStatus status={STATUS_MAP.rejected} />);
    expect(
      screen.getByText(/Your request to enable international payments was rejected/i),
    ).toBeInTheDocument();
  });

  test('renders activation status for no action received status', () => {
    render(<ActivationStatus status={STATUS_MAP.no_action_received} />);
    expect(screen.getByText(/Your request couldn't be approved/i)).toBeInTheDocument();
  });

  test('renders default activation status', () => {
    render(<ActivationStatus status="unknown_status" />);
    expect(
      screen.getByText(/Enable international payments: Cards, Bank Transfers/i),
    ).toBeInTheDocument();
  });
});
