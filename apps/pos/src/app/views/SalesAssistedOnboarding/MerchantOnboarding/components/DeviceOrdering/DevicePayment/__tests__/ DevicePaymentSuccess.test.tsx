import React from 'react';
import DevicePaymentSuccess from '../DevicePaymentSuccess';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';

const initProps = {
  handleGoToNextStep: jest.fn(),
};

describe('DevicePaymentSuccess', () => {
  test('should render DevicePaymentSuccess component', async () => {
    render(<DevicePaymentSuccess {...initProps} />);
    expect(screen.getByText('Order is successfully placed!')).toBeInTheDocument();
    expect(screen.getByText('Continue to next step')).toBeInTheDocument();
    expect(screen.getByAltText('success-icon')).toBeInTheDocument();
  });

  test('should call handleGoToNextStep on click of Continue to next step', async () => {
    render(<DevicePaymentSuccess {...initProps} />);
    const continueButton = screen.getByText('Continue to next step');
    await userEvent.click(continueButton);
    expect(initProps.handleGoToNextStep).toHaveBeenCalled();
  });
});
