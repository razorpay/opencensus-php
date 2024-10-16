import React from 'react';

import PaymentOptimizerProvider from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentOptimizerProvider';
import * as utils from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentOptimizerProvider/helper';
import { render, screen } from 'apps/self-serve/src/services/test/test-utils';

const item = {
  id: 'pay_P6TLcxQIDj2zPd',
  entity: 'payment',
  amount: 112,
  method: 'upi',
  email: 'qa@rzp.com',
  gateway_provider: 'payu',
  created_at: 1728374960,
  settled_by: 'payu',
  optimizer_provider: 'OhI2gt1aVKXEQ9',
};

const terminalProvider = {
  Provider_name: 'payu_sandbox_22222',
  Gateway: 'payu',
  Gateway_details: {
    Key: 'payukey2222',
    'Payment Methods': ['upi'],
    Recurring: true,
    Salt: '',
    Sodexo: false,
    optimizer_seamless_disabled: true,
  },
  Gateway_acquirer: 'payu',
  Terminal_id: 'OUfvDn3f01NM6I',
};

const findProviderSpy = jest.spyOn(utils, 'findProviderDetails');

function renderComponent() {
  render(<PaymentOptimizerProvider item={item} terminalProviders={[terminalProvider]} />);
}

describe('Testing PaymentOptimizerProvider Component', () => {
  test("should render the provider's name and logo", () => {
    renderComponent();

    expect(findProviderSpy).toHaveBeenCalledTimes(1);

    expect(screen.getByText('Payu')).toBeInTheDocument();

    const providerImage = screen.getByRole('img');
    expect(providerImage).toHaveAttribute('alt', terminalProvider.Gateway_acquirer);
  });

  test('should render placeholder incase provider is not found', () => {
    (findProviderSpy as jest.Mock).mockReturnValueOnce(null);
    renderComponent();

    expect(screen.getByText('--')).toBeInTheDocument();
  });
});
