import React from 'react';
import StatusBadge from '../StatusBadge';
import { render, screen } from 'apps/pos/src/services/test/test-utils';

describe('<StatusBadge />', () => {
  test('should render Status Badge with Pending value', () => {
    render(<StatusBadge type="pending" />);
    expect(screen.getByText('Pending')).toBeInTheDocument();
  });
  test('should render Status Badge with Activated value', () => {
    render(<StatusBadge type="activated" />);
    expect(screen.getByText('Activated')).toBeInTheDocument();
  });
  test('should render Status Badge with Under Review value', () => {
    render(<StatusBadge type="under_review" />);
    expect(screen.getByText('Under Review')).toBeInTheDocument();
  });
  test('should render Status Badge with Rejected value', () => {
    render(<StatusBadge type="rejected" />);
    expect(screen.getByText('Rejected')).toBeInTheDocument();
  });
  test('should render Status Badge with KYC Qualified value', () => {
    render(<StatusBadge type="kyc_qualified_stb" />);
    expect(screen.getByText('KYC Qualified')).toBeInTheDocument();
  });
  test('should render Status Badge with Payment not initiated value', () => {
    render(<StatusBadge type="payment_pending" />);
    expect(screen.getByText('Payment not initiated')).toBeInTheDocument();
  });
  test('should render Status Badge with Payment Completed value', () => {
    render(<StatusBadge type="payment_completed" />);
    expect(screen.getByText('Payment Completed')).toBeInTheDocument();
  });
  test('should render Status Badge with KYC Completed value', () => {
    render(<StatusBadge type="kyc_completed" />);
    expect(screen.getByText('KYC Completed')).toBeInTheDocument();
  });
  test('should render Status Badge with Needs clairifcation value', () => {
    render(<StatusBadge type="needs_clarification" />);
    expect(screen.getByText('Needs Clarification')).toBeInTheDocument();
  });
  test('should not render Status Badge with unknown status value', () => {
    render(<StatusBadge type="some_random" />);
    expect(screen.getByTestId('component-wrapper').firstChild).toBeNull();
  });
  test('should render Status Badge with Pricing Needs Clarifcation value', () => {
    render(<StatusBadge type="pending_agent_action" />);
    expect(screen.getByText('Pricing Needs Clarification')).toBeInTheDocument();
  });
});
