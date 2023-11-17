import React from 'react';

import '@testing-library/jest-dom/extend-expect';
import WalletsMultiSelect from 'merchant/views/Optimizer/AddProvider/components/WalletsMultiSelect';
import { render, screen } from 'test-utils';

describe('WalletMultiSelect Component', () => {
  const props = {
    isFormEdit: false,
    walletOptions: ['freecharge', 'itzcash', 'jiomoney', 'mobikwik', 'paytm'],
    walletSelected: ['freecharge', 'paytm'],
  };

  test('should render without any errors', () => {
    expect(() => <WalletsMultiSelect {...props} />).not.toThrowError();
  });

  test('should show "Wallets" label', () => {
    render(<WalletsMultiSelect {...props} />);
    expect(screen.getByText('Wallets')).toBeInTheDocument();
  });

  test('should show comma separated selected wallets', () => {
    render(<WalletsMultiSelect {...props} />);
    expect(screen.getByText('Freecharge, Paytm')).toBeInTheDocument();
  });
});
