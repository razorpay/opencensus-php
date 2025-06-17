import React from 'react';
import { RadioGroup } from '@razorpay/blade/components';

import DeliveryAddress from 'apps/pos/src/app/views/SelfServe/OrderSummary/DeliveryAddresses/DeliveryAddress';
import { MOCK_GTM } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import * as posCustomHooks from 'apps/pos/src/app/views/SelfServe/hooks';
import { render, screen } from 'test-utils';

jest.mock('common/splitz', () => ({
  ...(jest.requireActual('common/splitz') as Record<string, string>),
  useSplitzService: () => ({
    abExperiments: MOCK_GTM,
  }),
}));

const MOCK_DELIVERY_ADDRESS = {
  id: '3',
  type: 'default',
  isSelected: true,
  name: 'Test Name',
  phoneNumber: '1234567892',
  pincode: '560034',
  address: 'Test Address',
  city: 'Test City',
  state: 'DL',
};

const initProps = {
  id: '3',
  deliveryAddress: MOCK_DELIVERY_ADDRESS,
  isNewAddress: false,
  isEditing: false,
  isSelectDisabled: false,
  onEditClick: jest.fn(),
  onEditCancel: jest.fn(),
  onSubmit: jest.fn(),
};

const renderApp = (props = initProps) => {
  render(
    <RadioGroup label="Test Radio">
      <DeliveryAddress {...props} />
    </RadioGroup>,
  );
};

describe('<DeliveryAddress/>', () => {
  test('should render address component if not editing', () => {
    renderApp();
    expect(screen.getByText('Test Name')).toBeVisible();
  });

  test('should render address form if editing', () => {
    const newProps = {
      ...initProps,
      isEditing: true,
    };
    renderApp(newProps);
    expect(screen.getByText('Edit Address')).toBeVisible();
    expect(screen.getByRole('textbox', { name: /Full Name/ })).toBeVisible();
  });

  test('should render address form with address if editing in mobile view', () => {
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    const newProps = {
      ...initProps,
      isEditing: true,
    };
    renderApp(newProps);
    expect(screen.getByText('Test Name')).toBeVisible();
    expect(screen.getByText('Edit Address')).toBeVisible();
    expect(screen.getByRole('textbox', { name: /Full Name/ })).toBeVisible();
  });
});
