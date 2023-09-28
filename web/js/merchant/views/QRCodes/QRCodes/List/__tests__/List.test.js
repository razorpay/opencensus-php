import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import QRList from 'merchant/views/QRCodes/QRCodes/List';
import { getInitialReduxState } from 'merchant/views/mocks/fixtures';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';

global.rzpQ = {
  qrCode: () => ({ interaction: jest.fn() }),
  component: jest.fn(),
};

export const reduxStateWithCustomerFeeBearer = getInitialReduxState({
  merchant: { fee_bearer: FEE_BEARER_TYPES.CUSTOMER },
});

describe('QR List', () => {
  test('should render disabled Create QR Codes button when fee_bearer is customer', () => {
    render(<QRList />, { initialState: reduxStateWithCustomerFeeBearer });
    expect(screen.getByRole('button', { name: 'Create QR Codes' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Create QR Codes' })).toBeDisabled();
    expect(
      screen.getByText(
        'QR Code is not supported for merchants accepting payments as per the convenience fee model. To enable, click',
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
