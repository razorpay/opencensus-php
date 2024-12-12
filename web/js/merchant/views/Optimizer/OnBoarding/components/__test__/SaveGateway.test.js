import React from 'react';
import { render, screen } from 'test-utils';

import * as Ajax from 'merchant/utils/ajax';
import { SUPPORTED_GATEWAYS } from 'merchant/views/Navigator/tests/data/mockData';

import { SaveGateway } from 'merchant/views/Optimizer/OnBoarding/components/SaveGateway';

let merchantFetchSpy = jest.spyOn(Ajax, 'merchantFetch');

const renderComponent = (props) => {
  return render(<SaveGateway {...props} />);
};

describe('Optimizer OnBoarding - SaveGateway', () => {
  beforeEach(() => {
    merchantFetchSpy.mockImplementation(() => {
      return new Promise((resolve) => {
        setTimeout(() => {
          resolve({ success: true, data: { ...SUPPORTED_GATEWAYS } });
        }, 300);
      });
    });
  });

  test('Should render without errors', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('should render gateway dropdown', async () => {
    renderComponent();
    expect(screen.getByText('Share payment gateway details')).toBeInTheDocument();
    const hyperlink = screen.getByRole('link');
    expect(hyperlink).toBeInTheDocument();
    expect(hyperlink).toHaveAttribute(
      'href',
      'https://razorpay.com/docs/payments/optimizer/add-payment-providers/',
    );
    expect(hyperlink).toHaveAttribute('target', '_blank');
    expect(hyperlink).toHaveAttribute('rel', 'noopener noreferrer');

    const gatewayDropdown = await screen.findByRole('combobox');
    expect(gatewayDropdown).toBeInTheDocument();
    gatewayDropdown.click();
    expect(screen.getByText('Select payment gateways you use currently')).toBeInTheDocument();
    expect(screen.getByText('Popular gateway')).toBeInTheDocument();
    expect(screen.getByText('Others (11)')).toBeInTheDocument();
    expect(screen.getByText('PayU')).toBeInTheDocument();
    expect(screen.getByText('Atom')).toBeInTheDocument();
    expect(screen.getByText('Cashfree')).toBeInTheDocument();
    expect(screen.getByText('Paytm')).toBeInTheDocument();

    const submitDetialsBtn = screen.getByRole('button', { name: 'Submit details' });
    expect(submitDetialsBtn).toBeInTheDocument();
    expect(submitDetialsBtn).toBeDisabled();
  });
});
