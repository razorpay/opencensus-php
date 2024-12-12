import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { SUPPORTED_GATEWAYS } from 'merchant/views/Navigator/tests/data/mockData';

import { GatewayCreds } from 'merchant/views/Optimizer/OnBoarding/components/GatewayCreds';

const MOCK_PROPS = {
  currentExpandedIndex: -1,
  handleAccordionExpand: jest.fn(),
  selectedGateways: ['payu'],
  savedGateway: [],
  supportedGateways: { ...SUPPORTED_GATEWAYS },
  isPopoverOpen: false,
  setIsPopoverOpen: jest.fn(),
  handleChange: jest.fn(),
  saveCredentials: {},
  isSaving: false,
  gatewayDetails: {},
};

const renderComponent = (props = MOCK_PROPS) => {
  return render(<GatewayCreds {...props} />);
};

describe('Optimizer OnBoarding - GatewayCreds', () => {
  test('Should render without errors', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('should render gateway creds details', async () => {
    renderComponent();
    screen.getByText('PayU').click();
    const keyInput = await screen.getByPlaceholderText('Enter Key');
    expect(keyInput).toBeInTheDocument();
    const saltInput = await screen.getByPlaceholderText('Enter Salt');
    expect(saltInput).toBeInTheDocument();
    const saveBtn = screen.getByText('Save credentials').closest('button');
    expect(saveBtn).toBeInTheDocument();
    expect(saveBtn).toBeDisabled();
    await userEvent.type(keyInput, 'askn123');
    await userEvent.type(saltInput, 'askn12334');
    expect(keyInput).toHaveValue('askn123');
    expect(saltInput).toHaveValue('askn12334');
  });

  test('should render gateway creds details for multiple gateways', async () => {
    const props = {
      ...MOCK_PROPS,
      selectedGateways: ['payu', 'paytm'],
    };
    renderComponent(props);
    screen.getByText('PayU').click();
    const keyInput = await screen.getByPlaceholderText('Enter Key');
    expect(keyInput).toBeInTheDocument();
    const saltInput = await screen.getByPlaceholderText('Enter Salt');
    expect(saltInput).toBeInTheDocument();
    await userEvent.type(keyInput, 'askn123');
    await userEvent.type(saltInput, 'askn12334');
    expect(keyInput).toHaveValue('askn123');
    expect(saltInput).toHaveValue('askn12334');
    screen.getByText('PayTm').click();
    const industryInput = await screen.getByPlaceholderText('Enter INDUSTRY_TYPE_ID');
    const paytmKeyInput = await screen.getByPlaceholderText('Enter KEY');
    const midInput = await screen.getByPlaceholderText('Enter MID');
    expect(industryInput).toBeInTheDocument();
    expect(paytmKeyInput).toBeInTheDocument();
    expect(midInput).toBeInTheDocument();
    await userEvent.type(industryInput, 'askn123');
    await userEvent.type(paytmKeyInput, 'askn12334');
    await userEvent.type(midInput, 'askn123345');
    expect(industryInput).toHaveValue('askn123');
    expect(paytmKeyInput).toHaveValue('askn12334');
    expect(midInput).toHaveValue('askn123345');
  });

  test('should render button as enabled when gatewaydetails are filled', async () => {
    const props = {
      ...MOCK_PROPS,
      gatewayDetails: {
        payu: {
          Key: 'askn123',
          Salt: 'askn12334',
        },
      },
    };
    renderComponent(props);
    screen.getByText('PayU').click();
    const saveBtn = screen.getByText('Save credentials').closest('button');
    expect(saveBtn).not.toBeDisabled();
  });
});
