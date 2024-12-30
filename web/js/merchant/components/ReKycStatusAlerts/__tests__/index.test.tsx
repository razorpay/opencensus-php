import React from 'react';

import { ReKycStatusModal, ReKycStatusBanner } from 'merchant/components/ReKycStatusAlerts';
import { render, screen } from 'test-utils';

import { REKYC_STATUS_OPTIONS } from '../constants';

jest.mock('common/splitz', () => ({
  useSplitzService: jest.fn(() => ({
    abExperiments: { enable_manual_rekyc: { variables: { result: 'on' } } },
  })),
}));

const defaultUser = {
  isAdminOrOwner: true,
  rekyc_status: REKYC_STATUS_OPTIONS.NEEDS_CLARIFICATION,
};

describe('ReKycStatusModal', () => {
  const renderApp = (user = {}) => {
    return render(<ReKycStatusModal />, {
      initialState: { session: { user: { ...defaultUser, ...user } } },
    });
  };

  test('should render modal', () => {
    renderApp();
    expect(screen.getByText('Action Required')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Update KYC' })).toBeInTheDocument();
  });

  test('should not render modal if status is not actionable', () => {
    renderApp({ rekyc_status: REKYC_STATUS_OPTIONS.APPROVED });
    expect(screen.queryByText('Action Required')).not.toBeInTheDocument();
  });

  test('should not render modal cta if role is not admin or owner', () => {
    renderApp({ isAdminOrOwner: false });
    expect(screen.getByText('Action Required')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Update KYC' })).not.toBeInTheDocument();
  });
});

describe('ReKycStatusBanner', () => {
  const renderApp = (user = {}) => {
    return render(<ReKycStatusBanner />, {
      initialState: { session: { user: { ...defaultUser, ...user } } },
    });
  };

  test('should render banner', () => {
    renderApp();
    expect(screen.getByText('Important!')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Update KYC' })).toBeInTheDocument();
  });

  test('should not render banner cta if status is not actionable', () => {
    renderApp({ rekyc_status: REKYC_STATUS_OPTIONS.UNDER_REVIEW });
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  test('should not render banner cta if role is not admin or owner', () => {
    renderApp({ isAdminOrOwner: false });
    expect(screen.getByText('Important!')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Update KYC' })).not.toBeInTheDocument();
  });
});
