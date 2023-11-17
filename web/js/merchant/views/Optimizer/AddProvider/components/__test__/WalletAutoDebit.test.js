import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import { deepClone } from 'common/utils/rzp-utils';
import { WALLET_AUTO_DEBIT_KEY } from 'merchant/views/Navigator/constants';
import WalletAutoDebit from 'merchant/views/Optimizer/AddProvider/components/WalletAutoDebit';
import { render, screen } from 'test-utils';

describe('Wallet Auto Debit Component', () => {
  const renderApp = (props = {}) => {
    render(<WalletAutoDebit {...props} />);
  };

  const PROPS = {
    label: WALLET_AUTO_DEBIT_KEY,
    provider: {
      Gateway_details: {
        [WALLET_AUTO_DEBIT_KEY]: false,
      },
    },
    changeEnableAutoDebitSwitch: jest.fn(),
  };

  test('should render without any errors', () => {
    expect(() => renderApp(PROPS)).not.toThrowError();
  });

  test('should show wallet auto-debit disabled', () => {
    renderApp(PROPS);
    expect(screen.getByText('Disabled')).toBeInTheDocument();
  });

  test('should show wallet auto-debit enabled', () => {
    const enabled_props = deepClone(PROPS);
    enabled_props.provider.Gateway_details.ENABLE_AUTO_DEBIT = true;
    renderApp(enabled_props);
    expect(screen.getByText('Enabled')).toBeInTheDocument();
  });
});
