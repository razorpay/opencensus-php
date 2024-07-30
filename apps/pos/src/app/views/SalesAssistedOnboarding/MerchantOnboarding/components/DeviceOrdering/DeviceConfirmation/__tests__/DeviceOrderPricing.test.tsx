import React from 'react';
import DeviceOrderPricing from '../DeviceOrderPricing';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import { TestAddedDevice } from 'apps/pos/src/services/mocks/fixtures/deviceSelection';
import { TestDeviceOrderSummary } from 'apps/pos/src/services/mocks/fixtures/modularConfig';

const initProps = {
  addedDevices: [TestAddedDevice],
  orderSummary: TestDeviceOrderSummary,
};

const renderApp = () => {
  render(<DeviceOrderPricing {...initProps} />);
};
describe('DeviceOrderPricing', () => {
  test('should render pricing component on screen with data', () => {
    renderApp();
    expect(screen.getByText('Device Charges')).toBeInTheDocument();
    expect(screen.getByText('2,000')).toBeInTheDocument();
    expect(screen.getByText('Advance Rental Charges')).toBeInTheDocument();
    expect(screen.getAllByText('0').length).toBe(3);
    expect(screen.getByText('GST @18%')).toBeInTheDocument();
    expect(screen.getByText('360')).toBeInTheDocument();
    expect(screen.getByText('Total Order Price')).toBeInTheDocument();
    expect(screen.getByText('2,478')).toBeInTheDocument();
    expect(screen.getByText('Rental Charges')).toBeInTheDocument();
    expect(screen.getAllByText('118').length).toBe(2);
  });

  test('should expand collapsible content on click', async () => {
    renderApp();
    const deviceCharges = screen.getByText('Device Charges');
    expect(deviceCharges).toBeInTheDocument();
    await userEvent.click(deviceCharges);
    expect(screen.getByText('Test Device')).toBeInTheDocument();
  });
});
