import React from 'react';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import KycActionButton from 'merchant/views/PartnerDashboard/SubMerchant/POS/Components/KycActionButton';

import { posSubmerchantDetailsResponse } from './mocks/fixtures';

const state = {
  session: {
    user: {
      isFeatureEnabled: jest.fn().mockImplementation(() => false),
    },
  },
};

const mockedUseNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUseNavigate,
}));

describe('KycActionButton', () => {
  const renderApp = (initialState = state, submerchant) => {
    return render(<KycActionButton submerchant={submerchant} />, {
      initialState,
      showModal: true,
    });
  };

  test('should render null if activation status is activated', () => {
    const activation_status = 'activated';
    const kyc_access = {};
    const submerchant = {
      ...posSubmerchantDetailsResponse.data,
      details: { activation_status },
      kyc_access,
    };
    renderApp(state, submerchant);
    expect(screen.queryByText('Request for KYC')).toBeNull();
  });

  test('should render Resend KYC request button if kyc_access state is rejected', () => {
    const activation_status = null;
    const kyc_access = { state: 'rejected' };
    const submerchant = {
      ...posSubmerchantDetailsResponse.data,
      details: { activation_status },
      kyc_access,
    };
    renderApp(state, submerchant);
    expect(screen.getByText('Resend KYC request')).toBeInTheDocument();
  });

  test('should show Resubmit KYC details button if activation status is needs clarification', async () => {
    const activation_status = 'needs_clarification';
    const kyc_access = {};
    const submerchant = {
      ...posSubmerchantDetailsResponse.data,
      details: { activation_status },
      kyc_access,
    };
    renderApp(state, submerchant);
    const resubmitButton = screen.getByText('Resubmit KYC details');
    expect(resubmitButton).toBeInTheDocument();
    await userEvent.click(resubmitButton);

    await waitFor(() => {
      expect(mockedUseNavigate).toHaveBeenCalled();
    });
  });

  test('should render Request Not Accepted disabled button if kyc_access state is rejected and rejection_count is greater than 3', () => {
    const activation_status = null;
    const kyc_access = { state: 'rejected', rejection_count: 3 };
    const submerchant = {
      ...posSubmerchantDetailsResponse.data,
      details: { activation_status },
      kyc_access,
    };
    renderApp(state, submerchant);
    expect(screen.getByRole('button', { name: 'Request Not Accepted' })).toBeDisabled();
  });

  test('should render perform KYC if isSubmerchantKYCAccess is true', () => {
    const activation_status = null;
    const kyc_access = {};
    const state = {
      session: {
        user: {
          isFeatureEnabled: jest.fn().mockImplementation(() => true),
        },
      },
    };
    const submerchant = {
      ...posSubmerchantDetailsResponse.data,
      details: { activation_status },
      kyc_access,
    };
    renderApp(state, submerchant);
    expect(screen.getByText('Perform KYC')).toBeInTheDocument();
  });
});
