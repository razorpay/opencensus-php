import React from 'react';
import DeviceConfirmation from '../DeviceConfirmation';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import {
  TestDeviceConfig,
  TestAddedDeviceWithDeviceConfig,
  TestDeviceOrderSummary,
} from 'apps/pos/src/services/mocks/fixtures/deviceSelection';

const defaultProps = {
  deviceConfig: TestDeviceConfig,
  addedDevices: [TestAddedDeviceWithDeviceConfig],
  orderSummary: TestDeviceOrderSummary,
  customPricingDocuments: [],
  title: 'Device Confirmation',
  merchantId: 'test_nerchant_id',
  isUpdateModularLoading: false,
  isDisabled: false,
  handleUpdateModular: jest.fn(),
  handleGoToNextStep: jest.fn(),
  isCustomRatesApplicable: true,
};

const renderApp = (props = {}) => {
  const initProps = { ...defaultProps, ...props };
  render(<DeviceConfirmation {...initProps} />);
};

describe('DeviceConfirmation', () => {
  test('should render empty state if no devices added', () => {
    renderApp({ addedDevices: [] });
    expect(screen.getByText('No Devices added')).toBeInTheDocument();
  });

  test('should render device confirmation with CTA and Device Pricing', () => {
    renderApp();
    expect(screen.getByText('Confirm Order')).toBeInTheDocument();
    expect(screen.getByText('View Details')).toBeInTheDocument();
  });

  test('should render device confirmation with order summary item', () => {
    renderApp();
    expect(screen.getByText('Test Device')).toBeInTheDocument();
    expect(screen.getByText('Monthly')).toBeInTheDocument();
    expect(screen.getByText('30,000.00')).toBeInTheDocument();
  });

  test('should render delete item button and clicking on it should trigger modular with correct payload', async () => {
    renderApp();
    await userEvent.click(screen.getByLabelText('delete device from cart'));
    expect(defaultProps.handleUpdateModular).toHaveBeenCalledWith({
      device_cart_item_id_field: 'test-device',
      device_delete_from_cart_field: true,
      modular_callback: expect.any(Function),
    });
  });

  test('should trigger modular with correct payload when clicked on Confirm cta', async () => {
    renderApp({
      isCustomRatesApplicable: false,
      customPricingDocuments: [{ fileStoreId: '2312312' }],
    });
    await userEvent.click(screen.getByText('Confirm Order'));
    expect(defaultProps.handleUpdateModular).toHaveBeenCalledWith({
      device_order_confirmation_field: true,
      modular_callback: expect.any(Function),
    });
  });

  test('should show custom pricing component if isCustomRatesApplicable is true', () => {
    renderApp();
    expect(screen.getByText('Upload custom pricing proof')).toBeInTheDocument();
  });

  test('should not show custom pricing component if isCustomRatesApplicable is true', () => {
    renderApp({ isCustomRatesApplicable: false });
    expect(screen.queryByText('Upload custom pricing proof')).not.toBeInTheDocument();
  });

  test('should show error if custom pricing documents are not uploaded', async () => {
    renderApp();
    expect(screen.getByText('Upload custom pricing proof')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Confirm Order'));
    expect(screen.getByText('Please upload custom pricing proof to proceed')).toBeInTheDocument();
  });
});
