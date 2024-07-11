import React from 'react';
import { render } from 'test-utils';

import LandingPage from '../landingPage';

describe('Optimizer Landing Page', () => {
  const renderApp = () => {
    return render(<LandingPage />, {
      initialState: {
        navigator: {
          rules: [],
          loading: false,
          default_rule: {},
          providers_loading: false,
          terminalProviders: [
            {
              Provider_name: 'payu_token_testing',
              Description: 'Payu Token testing',
              Gateway: 'payu',
              Gateway_details: {
                Key: 'rrlwpu',
                'Payment Methods': ['card'],
                Recurring: false,
                Salt: '',
                Sodexo: false,
                optimizer_seamless_disabled: false,
              },
              Currency: ['INR'],
              Gateway_acquirer: 'payu',
              Terminal_id: 'JeWAMAcySy4Thr',
              Status: 'activated',
              created_at: 1654585928,
              updated_at: 1677732969,
            },
            {
              Provider_name: 'razorpay',
              Description: 'Razorpay provider',
              Gateway: 'razorpay',
              Gateway_details: {
                'Payment Methods': ['card', 'netbanking', 'upi', 'wallet'],
                wallet_metadata: {
                  wallets: [
                    'jiomoney',
                    'payzapp',
                    'amazonpay',
                    'payumoney',
                    'mcash',
                    'boost',
                    'touchngo',
                    'grabpay',
                    'freecharge',
                    'razorpaywallet',
                    'phonepe',
                    'paypal',
                    'paytm',
                    'mobikwik',
                    'airtelmoney',
                    'bajajpay',
                    'sbibuddy',
                    'olamoney',
                    'mpesa',
                    'openwallet',
                    'phonepeswitch',
                  ],
                },
              },
              Currency: ['INR'],
              Gateway_acquirer: 'razorpay',
              Status: '',
              created_at: 0,
              updated_at: 0,
            },
          ],
        },
      },
    });
  };

  it('should render without any errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  it('should render the landing page', () => {
    const { getByText, queryByText, getByRole, queryAllByText } = renderApp();
    expect(getByText('Payment Provider')).toBeInTheDocument();
    expect(getByText('2')).toBeInTheDocument();
    const documentation = queryByText('Documentation');
    expect(documentation).toBeInTheDocument();
    expect(documentation).toHaveAttribute('href', 'https://razorpay.com/docs/payments/optimizer/');
    expect(getByText('Add provider')).toBeInTheDocument();
    expect(getByText('payu_token_testing')).toBeInTheDocument();
    expect(getByText('razorpay')).toBeInTheDocument();
    expect(getByText('card, netbanking, upi, wallet')).toBeInTheDocument();
    expect(getByText('Default Rule')).toBeInTheDocument();
    expect(
      getByText('All transactions which do not fall under the custom rules will be routed via'),
    ).toBeInTheDocument();
    expect(getByText('All Custom Rules')).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Priority' })).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Rule Name' })).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Condition On' })).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Provider Used' })).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Created At' })).toBeInTheDocument();
    expect(getByRole('columnheader', { name: 'Status' })).toBeInTheDocument();
    expect(getByText('No custom rule set!')).toBeInTheDocument();
    expect(getByText('Create a new custom rule now.')).toBeInTheDocument();
    expect(queryAllByText('Add New Rule')).toHaveLength(2);
  });
});
