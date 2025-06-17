import React from 'react';

import MerchantAgreement from 'apps/pos/src/app/views/SelfServe/MerchantAgreement/MerchantAgreement';
import {
  COMPLETED,
  IN_PROGRESS,
  POS_AGREEMENT_SIGN_FAILED,
} from 'apps/pos/src/app/views/SelfServe/constants';
import { render, screen, userEvent, waitFor, server } from 'test-utils';

import { getModularResponseHandler, getModularSaveResponseHandler } from '../../mocks/handlers';

const renderMerchantAgreement = () => {
  return render(<MerchantAgreement />);
};

describe('POS Merchant Agreement', () => {
  afterEach(() => jest.clearAllMocks());

  test('should not show POS agreement if agreement is not required and value is empty', async () => {
    server.use(getModularResponseHandler({ consented: '', isAgreementRequired: false }));
    const { history } = renderMerchantAgreement();
    await waitFor(() => {
      expect(screen.queryByText('Take a moment to review the following:')).not.toBeInTheDocument();
      expect(screen.queryByRole('button', { name: 'I Agree' })).not.toBeInTheDocument();
    });
    await waitFor(() => expect(history.location.pathname).toContain('/dashboard'));
  });
  test('should not show POS agreement if agreement is required but value is not in_progress', async () => {
    server.use(getModularResponseHandler({ consented: '', isAgreementRequired: true }));
    const { history } = renderMerchantAgreement();
    await waitFor(() => expect(history.location.pathname).toContain('/dashboard'));
  });

  test('should render POS merchant signing agreement component on screen when user has not yet signed', async () => {
    server.use(getModularResponseHandler({ consented: IN_PROGRESS, isAgreementRequired: true }));
    renderMerchantAgreement();
    await waitFor(() => {
      expect(screen.queryByLabelText('pos-agreement-spinner')).toBeNull();
    });
    expect(screen.getByText('Take a moment to review the following:')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'I Agree' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Cancel' })).toBeInTheDocument();
  });

  test('should show Agreement successfully signed page if already signed POS agreement', async () => {
    server.use(getModularResponseHandler({ consented: COMPLETED, isAgreementRequired: true }));
    const { history } = renderMerchantAgreement();
    await waitFor(() => expect(screen.queryByLabelText('pos-agreement-spinner')).toBeNull());
    await waitFor(() =>
      expect(screen.getByText('Thank you for the confirmation!')).toBeInTheDocument(),
    );
    const okBtn = screen.getByRole('button', { name: 'Okay, got it' });
    expect(okBtn).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'I Agree' })).toBeNull();
    expect(screen.queryByRole('button', { name: 'Cancel' })).toBeNull();
    userEvent.click(okBtn);
    await waitFor(() => expect(history.location.pathname).toEqual('/app/dashboard'));
  });

  test('should move to dashboard if Cancel button clicked', async () => {
    server.use(getModularResponseHandler({ consented: '' }));
    const { history } = renderMerchantAgreement();
    await waitFor(() => expect(screen.queryByLabelText('pos-agreement-spinner')).toBeNull());
    await waitFor(() => {
      const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
      expect(cancelBtn).toBeInTheDocument();
      userEvent.click(cancelBtn);
    });
    await waitFor(() => expect(history.location.pathname).toEqual('/app/dashboard'));
  });

  test('should show Agreement successfully signed page if I Agree clicked and api succeeds', async () => {
    server.use(
      getModularResponseHandler({}),
      getModularSaveResponseHandler({ isSuccess: true, consented: COMPLETED }),
    );
    renderMerchantAgreement();
    await waitFor(() => expect(screen.queryByLabelText('pos-agreement-spinner')).toBeNull());
    const agreenBtn = screen.getByRole('button', { name: 'I Agree' });
    expect(agreenBtn).toBeInTheDocument();
    userEvent.click(agreenBtn);
    await waitFor(() =>
      expect(screen.getByText('Thank you for the confirmation!')).toBeInTheDocument(),
    );
  });

  test('should show error notification if I Agree clicked and api fails', async () => {
    server.use(
      getModularResponseHandler({}),
      getModularSaveResponseHandler({ isSuccess: false, consented: IN_PROGRESS }),
    );
    renderMerchantAgreement();
    await waitFor(() => expect(screen.queryByLabelText('pos-agreement-spinner')).toBeNull());
    const agreenBtn = screen.getByRole('button', { name: 'I Agree' });
    expect(agreenBtn).toBeInTheDocument();
    userEvent.click(agreenBtn);
    await waitFor(() => expect(screen.getByText(POS_AGREEMENT_SIGN_FAILED)).toBeInTheDocument());
    expect(agreenBtn).toBeInTheDocument();
    await waitFor(() =>
      expect(screen.queryByText('Thank you for the confirmation!')).not.toBeInTheDocument(),
    );
  });

  test('should show pricing details if custom pricing enabled', async () => {
    server.use(getModularResponseHandler({ isCustomRateEnabled: true }));
    renderMerchantAgreement();
    await waitFor(() => expect(screen.queryByLabelText('pos-agreement-spinner')).toBeNull());
    await waitFor(() =>
      expect(screen.getByRole('link', { name: 'Pricing Agreement' })).toBeInTheDocument(),
    );
  });

  test('should not show pricing details if custom pricing not enabled', async () => {
    server.use(getModularResponseHandler({ isCustomRateEnabled: false }));
    renderMerchantAgreement();
    await waitFor(() => expect(screen.queryByLabelText('pos-agreement-spinner')).toBeNull());
    await waitFor(() =>
      expect(screen.queryByRole('link', { name: 'Pricing Agreement' })).not.toBeInTheDocument(),
    );
  });
});
