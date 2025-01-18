import {
  getAccountName,
  getProviderName,
} from 'merchant/views/Marketplace/OptimizerAccounts/utils';

import { Provider } from 'merchant/views/Optimizer/types';
import { OptimizerAccount } from '../types';

const ACCOUNTS: OptimizerAccount[] = [
  {
    id: 'ONrJjKP9UkjAFh',
    account_name: 'random vendor account',
    account_status: 'activated',
    accounts_map: [
      {
        provider_id: 'PHrThH7HErdjpU',
        gateway_account_id: 'account_id_1',
        provider_name: 'payu for card',
      },
      {
        provider_id: 'KUrThH6J8rdkTu',
        gateway_account_id: 'account_id_2',
        provider_name: 'billdesk for upi',
      },
    ],
    created_at: 1730861614,
    updated_at: 1730948034,
  },
];

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
