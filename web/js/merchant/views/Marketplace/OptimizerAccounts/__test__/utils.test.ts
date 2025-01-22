import {
  getAccountName,
  getProviderName,
} from 'merchant/views/Marketplace/OptimizerAccounts/utils';

import { ACCOUNTS } from './mockData';

import { Provider } from 'merchant/views/Optimizer/types';

const PROVIDERS: Provider[] = [
  {
    Provider_name: 'payu for card',
    Description: 'test',
    Gateway: 'payu',
    Gateway_details: {
      'Payment Methods': ['card'],
    },
    Currency: ['INR'],
    Gateway_acquirer: 'payu',
    Terminal_id: 'PHrThH7HErdjpU',
    Status: 'activated',
    created_at: 12783089,
    updated_at: 12839123,
  },
];

describe('Optimizer Accounts Utils -> getAccountName', () => {
  test('should return account name using id', () => {
    const result = getAccountName(ACCOUNTS, 'ONrJjKP9UkjAFh');
    expect(result).toEqual('random vendor account');
  });

  test('should return empty string if account not found', () => {
    const result = getAccountName(ACCOUNTS, 'ONrJjKP9UkjAFg');
    expect(result).toEqual('');
  });
});

describe('Optimizer Accounts Utils -> getProviderName', () => {
  test('should return provider name using id', () => {
    const result = getProviderName(PROVIDERS, 'PHrThH7HErdjpU');
    expect(result).toEqual('payu for card');
  });

  test('should return empty string if provider not found', () => {
    const result = getProviderName(PROVIDERS, 'PHrThH7HErdjpP');
    expect(result).toEqual('');
  });
});
