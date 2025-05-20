import React from 'react';
import AddDeviceToCart from '../AddDeviceToCart';
import { TestDeviceConfig } from 'apps/pos/src/services/mocks/fixtures/deviceSelection';
import { screen, render, userEvent, within, waitFor } from 'apps/pos/src/services/test/test-utils';
import { EditDeviceInCartForm } from 'apps/pos/src/app/types/DeviceSelection';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';

const defaultValues: EditDeviceInCartForm = {
  device_item_quantity_field: 1,
  device_item_plan_field: 'monthly',
  device_item_setup_fee_type_field: 'standard',
  device_item_custom_setup_fee_field: '',
  device_item_advanced_rental_field: false,
  device_item_custom_rental_charges_field: '',
  device_item_advanced_rental_periods_field: '',
  device_item_rental_charges_type_field: '',
  device_item_name_field: 'Test Device',
  device_item_id_field: 'testid',
};

const initProps = {
  deviceConfig: TestDeviceConfig,
  isEditFlow: false,
  isDisabled: false,
  isDeviceAlreadyAdded: false,
  isUpdateModularLoading: false,
  handleModularUpdate: jest.fn((payload) => {
    payload[MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]();
  }),
  defaultValues,
};

const renderApp = (props = {}) => {
  render(<AddDeviceToCart {...initProps} {...props} />);
};

describe('AddDeviceToCart', () => {
  test('should render the component on screen with default values for add flow', async () => {
    renderApp();
    expect(screen.getByText('Add Device')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Add Device'));
    expect(screen.getByText('Test Device')).toBeInTheDocument();
    expect(screen.getByLabelText('reduce quantity')).toBeInTheDocument();
    expect(screen.getByLabelText('increase quantity')).toBeInTheDocument();
    expect(screen.getByTestId('device-quantity-text')).toHaveTextContent('1');
    expect(screen.getByTestId('plan-selection-card')).toBeInTheDocument();
  });

  test('should be able to increase and reduce quantity of device', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Device'));
    await userEvent.click(screen.getByLabelText('increase quantity'));
    expect(screen.getByTestId('device-quantity-text')).toHaveTextContent('2');
    await userEvent.click(screen.getByLabelText('reduce quantity'));
    expect(screen.getByTestId('device-quantity-text')).toHaveTextContent('1');
  });

  test('should not show delete button during add flow', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Device'));
    expect(screen.queryByLabelText('device item delete')).toBeNull();
  });

  test('should show plan card on screen', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Device'));
    await userEvent.click(screen.getByTestId('lifetime-plan-card'));
    expect(screen.getByText('Lifetime')).toBeInTheDocument();
    expect(screen.getByText('Monthly')).toBeInTheDocument();
    const monthlyPlan = screen.getByTestId('monthly-plan-card');
    expect(within(monthlyPlan).getByText('Setup Fee')).toBeInTheDocument();
    expect(within(monthlyPlan).getByText('Rental Charge')).toBeInTheDocument();
    expect(within(monthlyPlan).getByText('One time charge')).toBeInTheDocument();
    expect(within(monthlyPlan).getByText('13,000.00')).toBeInTheDocument();
    expect(within(monthlyPlan).getByText('200.00')).toBeInTheDocument();
    expect(within(monthlyPlan).getByText('1,000.00')).toBeInTheDocument();
  });

  test('should be able to toggle plan cards and shoud not show rental device fee in case of lifetime', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Device'));
    expect(screen.getByText('Monthly Rental Charges')).toBeInTheDocument();
    await userEvent.click(screen.getByTestId('lifetime-plan-card-radio'));
    await userEvent.click(screen.getByText('Add to Cart'));
    const deviceFeeContainer = screen.getByTestId('device-fees-container');
    expect(within(deviceFeeContainer).queryByText('Lifetime Rental Charges')).toBeNull();
    await waitFor(() => {
      expect(initProps.handleModularUpdate).toHaveBeenCalledWith(
        expect.objectContaining({
          device_item_plan_field: 'lifetime',
        }),
      );
    });
  });

  test('should clear custom field if rental type changes', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Device'));
    await userEvent.click(screen.getByTestId('lifetime-plan-card-radio'));
    const deviceFeeContainer = screen.getByTestId('device-fees-container');
    await userEvent.click(within(deviceFeeContainer).getByText('Custom'));
    await userEvent.type(within(deviceFeeContainer).getByRole('textbox'), '1000');
    await userEvent.click(within(deviceFeeContainer).getByText('Standard'));
    expect(within(deviceFeeContainer).getByRole('textbox')).toHaveValue('');
  });

  test('should clear custom field if optional feature unchecked', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Device'));
    await userEvent.click(screen.getByTestId('monthly-plan-card-radio'));
    await userEvent.click(screen.getByText('Collecting Rental Charges in Advance (in months)'));
    const optionalField = screen.getByTestId(
      'Collecting Rental Charges in Advance (in months)-optional-field',
    );
    await userEvent.click(within(optionalField).getByRole('checkbox'));
    await userEvent.type(within(optionalField).getByRole('textbox'), '2');
    await userEvent.click(within(optionalField).getByRole('checkbox'));
    expect(within(optionalField).getByRole('textbox')).toHaveValue('');
  });

  test('should trigger api with correct payload', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Device'));
    await userEvent.click(screen.getByLabelText('increase quantity'));
    await userEvent.click(screen.getByText('Add to Cart'));
    await waitFor(() => {
      expect(initProps.handleModularUpdate).toHaveBeenCalledWith(
        expect.objectContaining({
          device_item_advanced_rental_field: false,
          device_item_advanced_rental_periods_field: 0,
          device_item_custom_rental_charges_field: 0,
          device_item_custom_setup_fee_field: 0,
          device_item_id_field: 'testid',
          device_item_name_field: 'Test Device',
          device_item_plan_field: 'monthly',
          device_item_quantity_field: 2,
          device_item_rental_charges_type_field: 0,
          device_item_setup_fee_type_field: 'standard',
          device_selection_timestamp_field: expect.any(Number),
        }),
      );
    });
  });

  test('should be able to close Device Selection drawer', async () => {
    renderApp();
    await userEvent.click(screen.getByText('Add Device'));
    await waitFor(() => {
      expect(screen.getByText('Device Selection')).toBeVisible();
    });
    await userEvent.click(screen.getByText('Cancel'));
    expect(screen.getByText('Device Selection')).not.toBeVisible();
  });

  test('should be able to delete added device', async () => {
    renderApp({ isEditFlow: true });
    await userEvent.click(screen.getByText('Edit'));
    await userEvent.click(screen.getByLabelText('delete device item'));

    expect(initProps.handleModularUpdate).toHaveBeenCalledWith({
      [MODULAR_DEVICE_FIELDS.DEVICE_CART_ID_FIELD]: expect.any(String),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: expect.any(Function),
      [MODULAR_DEVICE_FIELDS.DEVICE_DELETE_PRODUCT_FIELD]: true,
    });
  });
});
