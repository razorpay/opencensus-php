import React from 'react';
import { render, screen } from 'test-utils';

import { SeamlessOption } from 'merchant/views/Navigator/components/Provider/SeamlessOption';
import { SUPPORTED_GATEWAYS } from 'merchant/views/Navigator/tests/data/mockData';

describe('SeamlessOption component', () => {
  const mockProps = {
    providers: SUPPORTED_GATEWAYS,
    selectedProvider: 'payu',
  };

  const renderComponent = (props) => render(<SeamlessOption {...props} />);

  test('should render SeamlessOption without any errors', () => {
    expect(() => renderComponent(mockProps)).not.toThrowError();
  });

  test('should render seamless option details for ccavenue', () => {
    const props = {
      ...mockProps,
      selectedProvider: 'ccavenue',
    };
    renderComponent(props);
    expect(screen.getByText('Enable seamless option*')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your CCAvenue account should have the seamless option enabled to use optimizer.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('How to enable seamless option on CCAvenue?')).toBeInTheDocument();
    expect(
      screen.getByText('*Please proceed next, if you have already enabled'),
    ).toBeInTheDocument();
  });

  test('should render prerequisites for ingenico', () => {
    const props = {
      ...mockProps,
      selectedProvider: 'ingenico',
    };
    renderComponent(props);
    expect(screen.getByText('Ingenico (Tech Process) Prerequisites*')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your Ingenico (Tech Process) account should have the required features enabled to use optimizer.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Steps to enable Ingenico (Tech Process)')).toBeInTheDocument();
    expect(
      screen.getByText('*Please proceed next, if you have already enabled'),
    ).toBeInTheDocument();
  });

  test('should render prerequisites for simpl', () => {
    const props = {
      ...mockProps,
      selectedProvider: 'getsimpl_optimizer',
    };
    renderComponent(props);
    expect(screen.getByText('Simpl Prerequisites*')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your Simpl account should have the required features enabled to use optimizer.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Steps to enable Simpl')).toBeInTheDocument();
    expect(
      screen.getByText('*Please proceed next, if you have already enabled'),
    ).toBeInTheDocument();
  });
});
