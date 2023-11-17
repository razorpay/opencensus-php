import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

import IntegrationType from 'merchant/views/Optimizer/AddProvider/components/IntegrationType';
import { render, screen, userEvent } from 'test-utils';

describe('Add Provider > IntegrationType', () => {
  let mockProps;

  beforeEach(() => {
    mockProps = {
      isEdit: false,
      isFormEdit: true,
      selectedProvider: null,
      gatewayDetails: { optimizer_seamless_disabled: false },
      validateStep: jest.fn(),
      toggleIntegrationType: jest.fn(),
    };
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const App = (props = {}) => {
    return (
      <BladeProvider themeTokens={paymentTheme}>
        <IntegrationType {...props} />
      </BladeProvider>
    );
  };

  it('should render IntegrationType without any errors', () => {
    expect(() => <App {...mockProps} />).not.toThrowError();
  });

  it('should render headingText, subText and Next button', () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Select integration type')).toBeInTheDocument();
    expect(
      screen.getByText('Select the type of integration for the selected gateway'),
    ).toBeInTheDocument();
    expect(screen.getByText('Next')).toBeInTheDocument();
  });

  it('should render Instant (beta) and Server-to-Server option', async () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Instant (beta)')).toBeInTheDocument();
    expect(screen.getByText('Server-to-Server')).toBeInTheDocument();

    await userEvent.click(screen.getByText('Instant (beta)'));
    expect(mockProps.toggleIntegrationType).toHaveBeenCalled();
  });

  it('should render Integration type: Instant (beta)', () => {
    mockProps.isFormEdit = false;
    mockProps.gatewayDetails.optimizer_seamless_disabled = true;
    render(<App {...mockProps} />);
    expect(screen.getByText(/Integration type/)).toBeInTheDocument();
    expect(screen.getByText('Instant (beta)')).toBeInTheDocument();
    expect(screen.queryByText('Server-to-Server')).not.toBeInTheDocument();
  });

  it('should render Integration type: Server-to-Server', () => {
    mockProps.isFormEdit = false;
    render(<App {...mockProps} />);
    expect(screen.getByText(/Integration type/)).toBeInTheDocument();
    expect(screen.getByText('Server-to-Server')).toBeInTheDocument();
    expect(screen.queryByText('Instant (beta)')).not.toBeInTheDocument();
  });
});
