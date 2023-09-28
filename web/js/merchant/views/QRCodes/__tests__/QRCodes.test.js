import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import QRCodes from 'merchant/views/QRCodes';
import { getInitialReduxState } from 'merchant/views/mocks/fixtures';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';

global.rzpQ = {
  qrCode: () => ({ interaction: jest.fn() }),
  component: jest.fn(),
};

describe('QR Codes', () => {
  test('should render the alert correctly when fee_bearer is customer', () => {
    const reduxStateWithCustomerFeeBearer = getInitialReduxState({
      merchant: { fee_bearer: FEE_BEARER_TYPES.CUSTOMER },
      isQRCodeProductEnabled: true,
    });
    render(<QRCodes />, {
      initialState: reduxStateWithCustomerFeeBearer,
    });
    expect(screen.getByText('QR Code is not available for you')).toBeInTheDocument();
    expect(
      screen.getByText(
        'This product is not supported for merchants accepting payments as per the convenience fee model. Any payments accepted via QR will be auto refunded.',
      ),
    ).toBeInTheDocument();
  });

  test('should render the onboarding blocker correctly when fee_bearer is customer and qr-codes feature is disabled', () => {
    const reduxState = getInitialReduxState({
      merchant: { fee_bearer: FEE_BEARER_TYPES.CUSTOMER },
      isQRCodeProductEnabled: false,
    });
    render(<QRCodes />, {
      initialState: reduxState,
    });
    expect(screen.getByText('QR Codes')).toBeInTheDocument();
    expect(
      screen.getByText(
        'This product is not supported for merchants accepting payments as per the convenience fee model. If you wish to enable this product click',
        { exact: false },
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toHaveAttribute(
      'href',
      '/app/payments-and-refunds-settings/capture-refund-settings',
    );
  });
});
