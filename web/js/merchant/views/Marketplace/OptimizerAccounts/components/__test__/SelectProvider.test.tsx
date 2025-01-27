import React from 'react';
import { render, userEvent } from 'test-utils';

import { SelectProvider } from 'merchant/views/Marketplace/OptimizerAccounts/components/SelectProvider';

import { Provider } from 'merchant/views/Optimizer/types';

describe('Optimizer Accounts -> SelectProvider', () => {
  const MOCK_PROPS = {
    providers: [
      {
        Provider_name: 'Ingenico_test',
        Description: 'Ingenico Gateway 2',
        Gateway: 'ingenico',
        Gateway_details: {
          'Encryption IV': '7084417350HNTGET671',
          'Encryption Key': '',
          'Merchant Code': 'L733998781',
          'Payment Methods': ['emi'],
        },
        Currency: ['INR'],
        Gateway_acquirer: 'ingenico',
        Terminal_id: 'PeYIi0Ikq1iUz9',
        Status: 'activated',
        created_at: 1735815968,
        updated_at: 1735815968,
      },
      {
        Provider_name: 'PayU_test',
        Description: 'ghjk',
        Gateway: 'payu',
        Gateway_details: {
          Key: 'ugyhjvhb',
          'Payment Methods': ['card'],
          Recurring: false,
          Salt: '',
          Sodexo: true,
          optimizer_seamless_disabled: true,
        },
        Currency: ['INR'],
        Gateway_acquirer: 'payu',
        Terminal_id: 'PjJsDIxWDXuwZN',
        Status: 'activated',
        created_at: 1736856858,
        updated_at: 1736856858,
      },
    ] as Provider[],
    selectedProvider: '',
    setSelectedProvider: jest.fn(),
  };

  const renderApp = (props = MOCK_PROPS) => render(<SelectProvider {...props} />);

  test('should render without errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  test('should render select provider dropdown', async () => {
    const { getByText, getByRole } = renderApp({ ...MOCK_PROPS });
    expect(getByText('Provider')).toBeInTheDocument();

    const providerInput = getByRole('combobox', { name: 'Provider' });
    expect(providerInput).toBeInTheDocument();
    await userEvent.click(providerInput);
    expect(getByText('Ingenico_test')).toBeInTheDocument();
    expect(getByText('PayU_test')).toBeInTheDocument();
  });
});
