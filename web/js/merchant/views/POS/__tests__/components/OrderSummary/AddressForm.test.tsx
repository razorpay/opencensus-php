import React from 'react';

import AddressForm from 'merchant/views/POS/OrderSummary/DeliveryAddresses/AddressForm';
import { MOCK_GTM } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getPincodeInfoHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import * as posCustomHooks from 'merchant/views/POS/hooks';
import { render, screen, server, userEvent, waitFor } from 'test-utils';

jest.mock('common/splitz', () => ({
  ...(jest.requireActual('common/splitz') as Record<string, string>),
  useSplitzService: () => ({
    abExperiments: MOCK_GTM,
  }),
}));

const MOCK_DELIVERY_ADDRESS = {
  name: 'Test Name',
  phoneNumber: '1234567892',
  pincode: '560034',
  address: 'Test Address',
  city: 'Test City',
  state: 'KA',
  type: 'custom',
};

const initProps = {
  isEdit: false,
  isBottomSheetOpen: false,
  onCancelClick: jest.fn(),
  onSubmit: jest.fn(),
};

jest.setTimeout(35000);

describe('<AddressForm/>', () => {
  test('should render Address Form on screen', () => {
    render(<AddressForm {...initProps} />);
    expect(screen.getByText('Add New Address')).toBeVisible();
  });

  test('should call on submit with correct form data', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_available' }));
    const { name, phoneNumber, pincode, city, address } = MOCK_DELIVERY_ADDRESS;
    render(<AddressForm {...initProps} />);
    await userEvent.type(screen.getByRole('textbox', { name: /Full Name/i }), name);
    await userEvent.type(
      screen.getByRole('textbox', { name: /Mobile Number/i }),
      phoneNumber.toString(),
    );
    await userEvent.type(screen.getByRole('textbox', { name: /Pincode/i }), pincode.toString());
    await userEvent.type(screen.getByRole('textbox', { name: /City/i }), city);
    await userEvent.type(screen.getByRole('textbox', { name: /Address/i }), address);
    const stateDropdown = screen.getByPlaceholderText('Select a state');
    await userEvent.click(stateDropdown);
    await userEvent.click(screen.getByTestId('Karnataka-option'));
    await userEvent.click(screen.getByText('Save Address'));

    await waitFor(() => {
      expect(initProps.onSubmit).toHaveBeenCalledWith(MOCK_DELIVERY_ADDRESS);
    });
  });

  test('should have data pre-filled if is Edit and call onSubmit with correct form data', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_available' }));
    const newInitProps = {
      ...initProps,
      isEdit: true,
      deliveryAddress: {
        ...MOCK_DELIVERY_ADDRESS,
        id: '3',
        isSelected: true,
        type: 'default',
      },
    };
    render(<AddressForm {...newInitProps} />);
    expect(screen.getByText('Edit Address')).toBeVisible();
    await userEvent.click(screen.getByText('Save Address'));
    await waitFor(() => {
      expect(initProps.onSubmit).toHaveBeenCalledWith(MOCK_DELIVERY_ADDRESS);
    });
  });

  test('should trigger cancel handler if cancel clicked', async () => {
    render(<AddressForm {...initProps} />);
    await userEvent.click(screen.getByText('Cancel'));
    expect(initProps.onCancelClick).toHaveBeenCalled();
  });

  test('should show validation error if phone number not equal to 10 characters', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_available' }));
    const newInitProps = {
      ...initProps,
      deliveryAddress: {
        ...MOCK_DELIVERY_ADDRESS,
        phoneNumber: '',
        id: '3',
        isSelected: true,
        type: 'default',
      },
    };
    render(<AddressForm {...newInitProps} />);

    await userEvent.type(screen.getByRole('textbox', { name: /Mobile Number/i }), '321');
    await userEvent.click(screen.getByText('Save Address'));

    await waitFor(() => {
      expect(screen.getByText('Phone number entered is invalid')).toBeVisible();
    });
  });

  test('should show validation error if pincode not deliverable', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_unavailable' }));
    const newInitProps = {
      ...initProps,
      deliveryAddress: {
        ...MOCK_DELIVERY_ADDRESS,
        id: '3',
        isSelected: true,
        type: 'default',
        pincode: '781019',
      },
    };
    render(<AddressForm {...newInitProps} />);
    await userEvent.click(screen.getByText('Save Address'));

    await waitFor(() => {
      expect(screen.getByText('Pincode not serviceable! Arriving Soon.')).toBeVisible();
    });
  });

  test('should show validation error if pincode not equal to 6 characters', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_available' }));
    const newInitProps = {
      ...initProps,
      deliveryAddress: {
        ...MOCK_DELIVERY_ADDRESS,
        pincode: '123',
        id: '3',
        isSelected: true,
        type: 'default',
      },
    };
    render(<AddressForm {...newInitProps} />);

    await userEvent.click(screen.getByText('Save Address'));
    await waitFor(() => {
      expect(screen.getByText('Pincode must be exactly 6 characters')).toBeVisible();
    });
  });

  test('should render Address Form with bottom sheet on screen', () => {
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    const newInitProps = {
      ...initProps,
      isBottomSheetOpen: true,
    };
    render(<AddressForm {...newInitProps} />);
    expect(screen.getByText('Add New Address')).toBeVisible();
  });

  test('should call on submit with correct form data if opened using bottom sheet', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_available' }));
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });

    const newInitProps = {
      ...initProps,
      isEdit: true,
      isBottomSheetOpen: true,
      deliveryAddress: {
        ...MOCK_DELIVERY_ADDRESS,
        id: '3',
        isSelected: true,
        type: 'default',
      },
    };
    render(<AddressForm {...newInitProps} />);
    expect(screen.getByText('Edit Address')).toBeVisible();
    await userEvent.click(screen.getByText('Save Address'));

    await waitFor(() => {
      expect(initProps.onSubmit).toHaveBeenCalledWith(MOCK_DELIVERY_ADDRESS);
    });
  });

  test('should call on submit with correct form data in mWeb flow', async () => {
    server.use(getPincodeInfoHandler({ type: 'delivery_available' }));
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });

    const newInitProps = {
      ...initProps,
      isBottomSheetOpen: true,
    };

    const { name, phoneNumber, pincode, city, address } = MOCK_DELIVERY_ADDRESS;
    render(<AddressForm {...newInitProps} />);
    await userEvent.type(screen.getByRole('textbox', { name: /Full Name/ }), name);
    await userEvent.type(
      screen.getByRole('textbox', { name: /Mobile Number/ }),
      phoneNumber.toString(),
    );
    await userEvent.type(screen.getByRole('textbox', { name: /Pincode/ }), pincode.toString());
    await userEvent.type(screen.getByRole('textbox', { name: /City/ }), city);
    await userEvent.type(screen.getByRole('textbox', { name: /Address/ }), address);
    const stateDropdown = screen.getByPlaceholderText('Select a state');
    await userEvent.click(stateDropdown);
    await userEvent.click(screen.getByTestId('Karnataka-option'), { pointerEventsCheck: 0 });
    await userEvent.click(screen.getByText('Save Address'));
    await waitFor(() => {
      expect(initProps.onSubmit).toHaveBeenCalledWith(MOCK_DELIVERY_ADDRESS);
    });
  });
});
