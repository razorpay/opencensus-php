import React from 'react';
import { render, screen } from 'test-utils';

import { FindDetails } from 'merchant/views/Optimizer/OnBoarding/components/FindDetails';

const renderComponent = (props = { gateway: 'payu' }) => {
  return render(<FindDetails {...props} />);
};

describe('Optimizer OnBoarding - FindDetails', () => {
  test('Should render without errors', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('should render payu points', () => {
    renderComponent();
    expect(screen.getByText("Click on 'Payment Gateway' in left panel")).toBeInTheDocument();
    expect(screen.getByText("Scroll down to 'Key Salt Details' section")).toBeInTheDocument();
    const payuImg = screen.getByRole('img');
    expect(payuImg).toHaveAttribute('alt', 'payu');
  });

  test('should render paytm points', () => {
    renderComponent({ gateway: 'paytm' });
    expect(screen.getByText("Click 'Developer Settings' in the left panel")).toBeInTheDocument();
    expect(screen.getByText("Click 'API Keys'")).toBeInTheDocument();
    expect(screen.getByText("Click 'Production API Details' tab")).toBeInTheDocument();
    const paytmImg = screen.getByRole('img');
    expect(paytmImg).toHaveAttribute('alt', 'paytm');
  });

  test('should render cashfree points', () => {
    renderComponent({ gateway: 'cashfree' });
    expect(
      screen.getByText('Open the Payment Gateway view from Cashfree Home page'),
    ).toBeInTheDocument();
    expect(screen.getByText("Click on 'Developers' in the left panel")).toBeInTheDocument();
    expect(screen.getByText("Click on 'API Keys'")).toBeInTheDocument();
    const cashfreeImg = screen.getByRole('img');
    expect(cashfreeImg).toHaveAttribute('alt', 'cashfree');
  });
});
