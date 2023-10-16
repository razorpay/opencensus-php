import React from 'react';
import { render, screen } from 'test-utils';
import { App } from 'merchant/views/PaymentButton/__test__/mocks/fixtures/SuccessView';

describe('Payment Button Component SuccessView Component', () => {
  const renderApp = (props) => render(<App {...props} />);

  test('payment Button SuccessView App component to be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render payment Button SuccessView text elements & plugins options', () => {
    renderApp({ i18: { isConfigTagEnabled: jest.fn() } });
    expect(screen.getByText('Button Created Successfully')).toBeInTheDocument();
    expect(screen.getByText('Your payment button is ready for integration')).toBeInTheDocument();
    expect(screen.getByText('Drupal Plugin')).toBeInTheDocument();
    expect(screen.getByText('Wordpress Plugin')).toBeInTheDocument();
    expect(screen.getByText('Elementor Plugin')).toBeInTheDocument();
    expect(screen.getByText('Visual Composer Plugin')).toBeInTheDocument();
    expect(screen.getByText('Weebly')).toBeInTheDocument();
  });
});
