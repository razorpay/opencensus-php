import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import * as allFetch from 'merchant/utils/ajax';
import ProviderView from 'merchant/views/Optimizer/AddProvider/ProviderView';
import * as utils from 'merchant/views/Optimizer/AddProvider/utils';

describe('Optimizer IntegrationTesting PaymentTesting', () => {
  beforeEach(() => {
    jest
      .spyOn(allFetch, 'merchantFetch')
      .mockReturnValue(Promise.resolve({ success: true, isError: false, data: undefined }));
    jest.spyOn(utils, 'isIntegrationAuditEnabled').mockReturnValue(true);
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const mockProps = {
    location: {
      pathname: 'optimizer/update-provider/HdvEjdKKJMBX89',
    },
  };

  const initialState = {
    navigator: {
      terminalProviders: [
        {
          Provider_name: 'payu test edit',
          Description: 'testing new name',
          Gateway: 'payu',
          Gateway_details: {
            Key: 'hujyb3123',
            'Payment Methods': ['card', 'emi', 'netbanking', 'upi', 'emandate'],
            Recurring: true,
            Salt: '',
            Sodexo: false,
            optimizer_seamless_disabled: false,
          },
          Currency: ['INR'],
          Gateway_acquirer: 'payu',
          Terminal_id: 'HdvEjdKKJMBX89',
          Status: 'activated',
          created_at: 1715591857,
          updated_at: 1715591857,
        },
        {
          Provider_name: 'Test_paytm1',
          Description: 'testing',
          Gateway: 'paytm',
          Gateway_details: {
            CLIENT_KEY: '',
            CLIENT_SECRET: '',
            ENABLE_AUTO_DEBIT: false,
            INDUSTRY_TYPE_ID: '5',
            KEY: '',
            MID: 'rdftgyhuij23',
            'Payment Methods': ['card', 'netbanking', 'upi', 'wallet'],
            WEBSITE: 'http:/test.com',
            optimizer_seamless_disabled: false,
            wallet_metadata: {
              wallets: ['paytm'],
            },
          },
          Currency: ['INR'],
          Gateway_acquirer: 'paytm',
          Terminal_id: 'HsbqEA5aJ9tWzu',
          Status: 'activated',
          created_at: 1715591857,
          updated_at: 1715591857,
        },
        {
          Provider_name: 'payu pending',
          Description: 'test pending',
          Gateway: 'payu',
          Gateway_details: {
            Key: 'gXELU7',
            Recurring: false,
            Salt: '',
            Sodexo: false,
            optimizer_seamless_disabled: true,
          },
          Currency: ['INR'],
          Gateway_acquirer: 'payu',
          Terminal_id: 'O7ZADX9u47aJrZ',
          Status: 'pending',
          created_at: 1715076695,
          updated_at: 1715076695,
        },
      ],
      providers_loading: false,
    },
  };

  const App = ({ initialState, props }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <BladeProvider themeTokens={bladeTheme}>
          <ProviderView {...props} />
        </BladeProvider>
      </Provider>
    );
  };

  it('should render PaymentTesting without any errors', () => {
    expect(() => render(<App initialState={initialState} props={mockProps} />)).not.toThrowError();
  });

  it('should render for payu', () => {
    render(<App initialState={initialState} props={mockProps} />);
    expect(screen.getByRole('heading', { name: 'payu test edit' })).toBeInTheDocument();
    expect(screen.getByText('LIVE')).toBeInTheDocument();
    expect(screen.getByText('Created on Mon May 13 2024, 9:17am')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Details' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Method settings' })).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByText('testing new name')).toBeInTheDocument();
    expect(screen.getByText('Gateway')).toBeInTheDocument();
    expect(screen.getByText('payu')).toBeInTheDocument();
    expect(screen.getByText('Methods enabled')).toBeInTheDocument();
    expect(screen.getByText('Card, EMI, Netbanking, UPI, E-Mandate')).toBeInTheDocument();
    expect(screen.getByText('Recurring')).toBeInTheDocument();
    expect(screen.getByText('Enabled')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Go back' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Edit Details' })).toBeInTheDocument();
    expect(
      screen.getByRole('button', { name: 'View detailed provider settings' }),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Restart integration testing' })).toBeInTheDocument();
  });

  it('should render for paytm', () => {
    const props = {
      location: {
        pathname: 'optimizer/update-provider/HsbqEA5aJ9tWzu',
      },
    };
    render(<App initialState={initialState} props={props} />);
    expect(screen.getByRole('heading', { name: 'Test_paytm1' })).toBeInTheDocument();
    expect(screen.getByText('LIVE')).toBeInTheDocument();
    expect(screen.getByText('Created on Mon May 13 2024, 9:17am')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Details' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Method settings' })).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByText('testing')).toBeInTheDocument();
    expect(screen.getByText('Gateway')).toBeInTheDocument();
    expect(screen.getByText('paytm')).toBeInTheDocument();
    expect(screen.getByText('Methods enabled')).toBeInTheDocument();
    expect(screen.getByText('Card, Netbanking, UPI, Wallet')).toBeInTheDocument();
    expect(screen.getByText('Wallets enabled')).toBeInTheDocument();
    expect(screen.getByText('Paytm')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Go back' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Edit Details' })).toBeInTheDocument();
    expect(
      screen.getByRole('button', { name: 'View detailed provider settings' }),
    ).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Restart integration testing' })).toBeNull();
  });

  it('should render for payu pending', () => {
    const props = {
      location: {
        pathname: 'optimizer/update-provider/O7ZADX9u47aJrZ',
      },
    };
    render(<App initialState={initialState} props={props} />);
    expect(screen.getByRole('heading', { name: 'payu pending' })).toBeInTheDocument();
    expect(screen.getByText('PENDING')).toBeInTheDocument();
    expect(screen.getByText('Created on Tue May 7 2024, 10:11am')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Details' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Provider settings' })).toBeInTheDocument();
    expect(screen.getByText('Description')).toBeInTheDocument();
    expect(screen.getByText('test pending')).toBeInTheDocument();
    expect(screen.getByText('Gateway')).toBeInTheDocument();
    expect(screen.getByText('payu')).toBeInTheDocument();
    expect(screen.queryByText('Methods enabled')).toBeNull();
    expect(screen.getByText('Recurring')).toBeInTheDocument();
    expect(screen.getByText('Disabled')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Go back' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Edit Details' })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'View detailed provider settings' })).toBeNull();
    expect(screen.getByRole('button', { name: 'Restart integration testing' })).toBeInTheDocument();
  });
});
