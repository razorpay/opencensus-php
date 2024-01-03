import React from 'react';

import Address from 'merchant/views/POS/OrderSummary/DeliveryAddresses/Address';
import { render, screen, userEvent } from 'test-utils';

const mockDeliveryAddress = {
  id: '3',
  isSelected: true,
  type: 'default',
  name: 'Test Name',
  phoneNumber: '1234567892',
  pincode: '560034',
  address: 'Test Address',
  city: 'Test City',
  state: 'DL',
};

const initProps = {
  deliveryAddress: mockDeliveryAddress,
  isDisabled: false,
  onEditClick: jest.fn(),
};

jest.setTimeout(35000);

describe('<Address/>', () => {
  test('should render Address component on screen', () => {
    render(<Address {...initProps} />);
    expect(screen.getByText('Test Name')).toBeVisible();
    expect(screen.getByText('Test Address, Test City, Delhi-560034')).toBeVisible();
    expect(screen.getByText('1234567892')).toBeVisible();
  });

  test('should trigger onEdit when click', async () => {
    render(<Address {...initProps} />);
    await userEvent.click(screen.getByText('Edit'));
    expect(initProps.onEditClick).toHaveBeenCalled();
  });

  test('should not render edit button if is disabled', () => {
    const newProps = {
      ...initProps,
      isDisabled: true,
    };
    render(<Address {...newProps} />);
    expect(screen.queryByText('Edit')).not.toBeInTheDocument();
  });
});
