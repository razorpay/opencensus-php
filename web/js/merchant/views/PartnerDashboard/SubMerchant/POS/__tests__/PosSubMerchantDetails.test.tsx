import React from 'react';

import { render, screen, server, waitFor } from 'common/services/test/test-utils';
import PosSubmerchantDetails from 'merchant/views/PartnerDashboard/SubMerchant/POS/PosSubmerchantDetails';

import { posSubmerchantDetailsResponse as data } from './mocks/fixtures';
import { posSubmerchantDetailsSuccess } from './mocks/handlers';

const showNotification = jest.fn();
describe('PosSubmerchantDetails', () => {
  const renderApp = () => {
    return render(<PosSubmerchantDetails id={data.data.id} showNotification={showNotification} />);
  };

  test('should render loader if data is loading', () => {
    server.use(posSubmerchantDetailsSuccess(data));
    renderApp();
    const spinner = screen.getByTestId('spinner');
    expect(spinner).toBeInTheDocument();
  });

  test('should show the details once the data is loaded', async () => {
    server.use(posSubmerchantDetailsSuccess(data));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(data.data.name)).toBeInTheDocument();
    });
    expect(screen.getByText(data.data.email)).toBeInTheDocument();
    expect(screen.getByText('KYC History:')).toBeInTheDocument();
    expect(screen.getByText('Under Review')).toBeInTheDocument();

    const orderDetailsLink = screen.getByRole('link', { name: 'View Order Details' });
    expect(orderDetailsLink).toBeInTheDocument();
    expect(orderDetailsLink).toHaveAttribute(
      'href',
      `/partners/submerchants/pos/${data.data.id}/orders`,
    );
    expect(orderDetailsLink).toHaveAttribute('target', '_blank');
  });
});
