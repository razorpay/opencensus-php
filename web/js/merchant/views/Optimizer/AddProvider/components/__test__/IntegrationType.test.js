import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

import { SUPPORTED_GATEWAYS } from 'merchant/views/Navigator/components/AddProvider/components/__test__/mocks/constants';
import { RAZORPAY_GATEWAY_KEY, PROVIDER_KEYS } from 'merchant/views/Navigator/constants';
import IntegrationType from 'merchant/views/Optimizer/AddProvider/components/IntegrationType';
import { render, screen, userEvent } from 'test-utils';

describe('Add Provider > IntegrationType', () => {
  let mockProps;

  beforeEach(() => {
    mockProps = {
      isEdit: false,
      isFormEdit: true,
      providers: SUPPORTED_GATEWAYS,
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

  it('should render Account type with header and both account type', () => {
    mockProps.selectedProvider = RAZORPAY_GATEWAY_KEY;
    mockProps.validateStep = jest.fn(() => true);
    render(<App {...mockProps} />);
    expect(screen.getByRole('heading', { name: 'Select account type' })).toBeInTheDocument();
    expect(
      screen.getByText('Select the type of account for the selected gateway'),
    ).toBeInTheDocument();
    expect(screen.getByText('STEP 2 OUT OF 4')).toBeInTheDocument();
    expect(screen.getByText('Account type')).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'Regular' })).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'Banking VAS' })).toBeInTheDocument();
    const nextBtn = screen.getByRole('button', { name: 'Next' });
    expect(nextBtn).toBeInTheDocument();
    expect(nextBtn).toBeDisabled();
  });

  it('should render Account type with bank input on selecting banking vas account', async () => {
    mockProps.selectedProvider = RAZORPAY_GATEWAY_KEY;
    const changeGatewayDetails = jest.fn();
    mockProps.changeGatewayDetails = changeGatewayDetails;
    render(<App {...mockProps} />);
    expect(screen.getByRole('heading', { name: 'Select account type' })).toBeInTheDocument();
    expect(
      screen.getByText('Select the type of account for the selected gateway'),
    ).toBeInTheDocument();
    expect(screen.getByText('STEP 2 OUT OF 4')).toBeInTheDocument();
    expect(screen.getByText('Account type')).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: 'Regular' })).toBeInTheDocument();
    const bankingVas = screen.getByRole('radio', { name: 'Banking VAS' });
    expect(bankingVas).toBeInTheDocument();
    await userEvent.click(bankingVas);
    expect(changeGatewayDetails).toBeCalled();
    expect(screen.getByText('Bank', { exact: true })).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Select bank')).toBeInTheDocument();
  });

  it('should render Account type and bank name', () => {
    mockProps.selectedProvider = RAZORPAY_GATEWAY_KEY;
    mockProps.isFormEdit = false;
    mockProps.gatewayDetails[PROVIDER_KEYS.GATEWAY_ACQUIRER] = 'axis_vas';
    render(<App {...mockProps} />);
    expect(screen.getByRole('heading', { name: 'Select account type' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Edit account type' })).toBeInTheDocument();
    expect(screen.getByText('Account type')).toBeInTheDocument();
    expect(screen.getByText('Banking VAS')).toBeInTheDocument();
    expect(screen.getByText('Bank', { exact: true })).toBeInTheDocument();
    expect(screen.getByText('Axis Bank')).toBeInTheDocument();
  });
});
