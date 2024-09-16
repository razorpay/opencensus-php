import React from 'react';
import DeviceOrderSummaryItem from '../DeviceOrderSummaryItem';
import { TestDeviceConfig } from 'apps/pos/src/services/mocks/fixtures/deviceSelection';
import { TestDeviceOrderSummaryItem } from 'apps/pos/src/services/mocks/fixtures/modularConfig';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';

const defaultProps = {
  device: {
    ...TestDeviceOrderSummaryItem,
    deviceConfig: TestDeviceConfig,
  },
  deviceConfig: TestDeviceConfig,
  isUpdateModularLoading: false,
  isDisabled: false,
  handleUpdateModular: jest.fn(),
};

const renderApp = () => {
  render(<DeviceOrderSummaryItem {...defaultProps} />);
};

describe('DeviceOrderSummaryItem', () => {
  test('should render device order summary item', () => {
    renderApp();
    expect(screen.getByText('Test Device')).toBeInTheDocument();
    expect(screen.getByText('Monthly')).toBeInTheDocument();
    expect(screen.getByText('2,000.00')).toBeInTheDocument();
    expect(screen.getByText('Qty: 1')).toBeInTheDocument();
    expect(screen.getByText('Edit')).toBeInTheDocument();
  });

  test('should trigger delete with correct params', async () => {
    renderApp();
    await userEvent.click(screen.getByLabelText('delete device from cart'));
    expect(defaultProps.handleUpdateModular).toHaveBeenCalledWith({
      device_cart_item_id_field: '9527f561-e109-4c47-b64c-85b82402fba9',
      device_delete_from_cart_field: true,
      modular_callback: expect.any(Function),
    });
  });
});
