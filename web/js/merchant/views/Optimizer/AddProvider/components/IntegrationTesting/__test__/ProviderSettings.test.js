import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { ProviderSettings } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/ProviderSettings';

import { PROVIDER_SETTING_GATEWAY_META } from './mocks';

describe('Optimizer IntegrationTesting ProviderSettings', () => {
  const mockProps = {
    providerName: 'payu test',
    gatewayMetaData: PROVIDER_SETTING_GATEWAY_META,
    methods: {},
    setMethods: jest.fn(),
    integrationType: 'instant',
    isRouteEnabled: false,
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <ProviderSettings {...props} />
      </BladeProvider>
    );
  };

  it('should render ProviderSettings without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrowError();
  });

  it('should render all the methods', () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Provider Settings')).toBeInTheDocument();
    expect(
      screen.getByText(
        'You can take a final decision about which methods to enable and disable for payu test',
      ),
    ).toBeInTheDocument();
    ['Card', 'UPI', 'Netbanking', 'EMI', 'E-Mandate', 'Recurring', 'Wallet'].forEach((method) => {
      expect(screen.getByRole('switch', { name: method })).toBeInTheDocument();
      if (method === 'Recurring') {
        expect(screen.getByRole('switch', { name: method })).toBeDisabled();
      }
    });
  });

  it('should render all the wallets', () => {
    const wallets = mockProps.gatewayMetaData['Payment Methods'].meta_data.wallet_metadata.wallets;
    const props = {
      ...mockProps,
      methods: {
        wallet: true,
        wallet_metadata: {
          wallets,
        },
      },
    };
    render(<App {...props} />);
    const walletSwitch = screen.getByRole('switch', { name: 'Wallet' });
    expect(walletSwitch).toBeInTheDocument();
    expect(walletSwitch).toBeChecked();
    wallets.forEach((wallet) => {
      const walletCheckbox = screen.getByRole('checkbox', { name: wallet });
      expect(walletCheckbox).toBeInTheDocument();
      expect(walletCheckbox).toBeChecked();
    });
  });

  it('should call setMethods on change of methods', async () => {
    render(<App {...mockProps} />);
    const cardSwitch = screen.getByRole('switch', { name: 'Card' });
    expect(cardSwitch).toBeInTheDocument();
    expect(cardSwitch).not.toBeChecked();
    await userEvent.click(cardSwitch);
    expect(mockProps.setMethods).toHaveBeenCalledWith({
      ...mockProps.methods,
      card: true,
    });
    const walletSwitch = screen.getByRole('switch', { name: 'Wallet' });
    expect(walletSwitch).toBeInTheDocument();
    expect(walletSwitch).not.toBeChecked();
    await userEvent.click(walletSwitch);
    expect(mockProps.setMethods).toHaveBeenCalledWith({
      ...mockProps.methods,
      wallet: true,
      wallet_metadata: {
        wallets: mockProps.gatewayMetaData['Payment Methods'].meta_data.wallet_metadata.wallets,
      },
    });
  });

  it('should call setMethods on change of wallet method', async () => {
    const wallets = mockProps.gatewayMetaData['Payment Methods'].meta_data.wallet_metadata.wallets;
    const props = {
      ...mockProps,
      methods: {
        wallet: true,
        wallet_metadata: {
          wallets,
        },
      },
    };
    render(<App {...props} />);
    const walletSwitch = screen.getByRole('switch', { name: 'Wallet' });
    expect(walletSwitch).toBeInTheDocument();
    expect(walletSwitch).toBeChecked();
    await userEvent.click(walletSwitch);
    expect(mockProps.setMethods).toHaveBeenCalledWith({
      ...mockProps.methods,
      wallet: false,
      wallet_metadata: {},
    });
  });

  it('should call setMethods on change of wallets', async () => {
    const wallets = mockProps.gatewayMetaData['Payment Methods'].meta_data.wallet_metadata.wallets;
    const props = {
      ...mockProps,
      methods: {
        wallet: true,
        wallet_metadata: {
          wallets,
        },
      },
    };
    render(<App {...props} />);
    const wallet = 'phonepe';
    const walletCheckbox = screen.getByRole('checkbox', { name: wallet });
    expect(walletCheckbox).toBeInTheDocument();
    expect(walletCheckbox).toBeChecked();
    await userEvent.click(walletCheckbox);
    expect(mockProps.setMethods).toHaveBeenCalledWith({
      ...mockProps.methods,
      wallet: true,
      wallet_metadata: {
        wallets: props.methods.wallet_metadata.wallets.filter((w) => w !== wallet),
      },
    });
  });

  it('should render pluxee switch disabled for s2s integration when card is not selected', () => {
    const props = {
      ...mockProps,
      integrationType: 's2s',
    };
    render(<App {...props} />);
    const pluxeeSwitch = screen.getByRole('switch', { name: 'Pluxee' });
    expect(pluxeeSwitch).toBeInTheDocument();
    expect(pluxeeSwitch).toBeDisabled();
  });

  it('should render pluxee switch for s2s integration when card is selected', () => {
    const props = {
      ...mockProps,
      integrationType: 's2s',
      methods: {
        card: true,
      },
    };
    render(<App {...props} />);
    const pluxeeSwitch = screen.getByRole('switch', { name: 'Pluxee' });
    expect(pluxeeSwitch).toBeInTheDocument();
    expect(pluxeeSwitch).not.toBeDisabled();
    expect(pluxeeSwitch).not.toBeChecked();
  });

  it('should render recurring switch enabled when card or upi is selected', () => {
    const props = {
      ...mockProps,
      methods: {
        card: true,
      },
    };
    render(<App {...props} />);
    const recurringSwitch = screen.getByRole('switch', { name: 'Recurring' });
    expect(recurringSwitch).toBeInTheDocument();
    expect(recurringSwitch).toBeEnabled();
  });

  it('should render TPV input when TPV is present', () => {
    const props = {
      ...mockProps,
      gatewayMetaData: {
        ...mockProps.gatewayMetaData,
        TPV: {
          data_type: 'string',
          data_value: 'tpv',
          terminals_key: '',
        },
      },
    };
    render(<App {...props} />);
    expect(screen.getByText('TPV')).toBeInTheDocument();
    expect(screen.getByText('Non TPV')).toBeInTheDocument();
    expect(screen.getByText('TPV Only')).toBeInTheDocument();
    expect(screen.getByText('Both (TPV and Non TPV)')).toBeInTheDocument();
  });

  it('should render Route switch when route is enabled', () => {
    const props = {
      ...mockProps,
      gatewayMetaData: {
        ...mockProps.gatewayMetaData,
        optimizer_route: {
          data_type: 'bool',
          data_value: 'optimizer_route',
          terminals_key: '',
        },
      },
      isRouteEnabled: true,
    };
    render(<App {...props} />);
    expect(screen.getByRole('switch', { name: 'Route' })).toBeInTheDocument();
  });

  it('should not render Route switch when route is not enabled', () => {
    const props = {
      ...mockProps,
      gatewayMetaData: {
        ...mockProps.gatewayMetaData,
        optimizer_route: {
          data_type: 'bool',
          data_value: 'optimizer_route',
          terminals_key: '',
        },
      },
    };
    render(<App {...props} />);
    expect(screen.queryByRole('switch', { name: 'Route' })).toBeNull();
  });
});
