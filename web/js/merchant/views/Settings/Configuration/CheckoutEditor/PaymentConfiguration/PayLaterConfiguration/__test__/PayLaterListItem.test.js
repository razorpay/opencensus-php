import React from 'react';
import { render, fireEvent } from 'test-utils';
import PayLaterListItem from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/PayLaterConfiguration/PayLaterListItem';

describe('PayLaterListItem', () => {
  const item = {
    name: 'GetSimpl',
    code: 'getsimpl',
    isEnabled: true,
    isActivated: true,
    requestLink: 'https://razorpay.com/docs/',
    isDisabled: false,
    status: 'activated',
    slug: 'getsimpl',
  };
  const activeBanks = { method: 'paylater', providers: [] };
  const setActiveBanks = jest.fn();

  it('should render title and icon', () => {
    const { getByText, getByAltText } = render(
      <PayLaterListItem item={item} activeBanks={activeBanks} setActiveBanks={setActiveBanks} />,
    );
    expect(getByText(item.name)).toBeInTheDocument();
    expect(getByAltText(item.name)).toBeInTheDocument();
  });

  it('should render switch when activated', () => {
    const { getByRole } = render(
      <PayLaterListItem item={item} activeBanks={activeBanks} setActiveBanks={setActiveBanks} />,
    );
    expect(getByRole('switch')).toBeInTheDocument();
  });
  it('should update active banks when switch is toggled', () => {
    const { getByRole } = render(
      <PayLaterListItem item={item} activeBanks={activeBanks} setActiveBanks={setActiveBanks} />,
    );
    fireEvent.click(getByRole('switch'));
    expect(setActiveBanks).toHaveBeenCalledTimes(1);
    expect(setActiveBanks.mock.calls[0][0](activeBanks)).toEqual({
      method: 'paylater',
      providers: [item.code],
    });
  });
});
