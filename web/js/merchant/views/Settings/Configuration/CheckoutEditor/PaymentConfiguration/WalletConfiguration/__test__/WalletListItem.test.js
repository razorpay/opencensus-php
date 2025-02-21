import React from 'react';
import { render, fireEvent } from 'test-utils';
import WalletListItem from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/WalletConfiguration/WalletListItem';

describe('WalletListItem', () => {
  const item = {
    name: 'Wallet Item',
    code: 'wallet',
    isActivated: true,
    requestLink: 'https://razorpay.com/docs/',
    isDisabled: false,
  };
  const activeBanks = { wallets: [] };
  const setActiveBanks = jest.fn();

  it('should render title and icon', () => {
    const { getByText, getByAltText } = render(
      <WalletListItem item={item} activeBanks={activeBanks} setActiveBanks={setActiveBanks} />,
    );
    expect(getByText(item.name)).toBeInTheDocument();
    expect(getByAltText(item.name)).toBeInTheDocument();
  });

  it('should render activated badge and switch when activated', () => {
    const { getByText, getByRole } = render(
      <WalletListItem item={item} activeBanks={activeBanks} setActiveBanks={setActiveBanks} />,
    );
    expect(getByText('Activated')).toBeInTheDocument();
    expect(getByRole('switch')).toBeInTheDocument();
  });

  it('should update active banks when switch is toggled', () => {
    const { getByRole } = render(
      <WalletListItem item={item} activeBanks={activeBanks} setActiveBanks={setActiveBanks} />,
    );
    fireEvent.click(getByRole('switch'));
    expect(setActiveBanks).toHaveBeenCalledTimes(1);
    expect(setActiveBanks.mock.calls[0][0](activeBanks)).toEqual({
      wallets: [item.code],
    });
  });
});
