import React from 'react';
import { render, screen } from 'apps/pos/src/services/test/test-utils';
import DeviceSelectionCatalog from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DeviceSelectionCatalog/DeviceSelectionCatalog';
import { addedDevices, deviceConfig } from './mocks/fixtures';

const renderApp = (props) => {
  render(<DeviceSelectionCatalog {...props} />);
};
const commonProps = {
  heading: 'Choose Suitable Devices for your merchant',
  deviceConfig: deviceConfig,
  addedDevices: addedDevices,
};
describe('<DeviceSelectionCatalog/>', () => {
  test('should show device selection catalog', () => {
    const mockProps = {
      ...commonProps,
      isUpdateModularLoading: false,
      isStepCompleted: false,
      isPosEkycAgent: false,
    };
    renderApp(mockProps);
    expect(screen.getByText(/Choose Suitable Devices for your merchant/i)).toBeInTheDocument();
    expect(screen.getByText(/Android Smart Pos/i)).toBeInTheDocument();
    expect(screen.getByText(/Android Smart mini Pos/i)).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: /proceed to cart/i,
      }),
    ).toBeEnabled();
    expect(screen.getByRole('button', { name: /Add device/i })).toBeEnabled();
    expect(screen.getByRole('button', { name: /Add another device/i })).toBeEnabled();
  });
  test('should show device selection catalog when device ordering is complete', () => {
    const mockProps = {
      ...commonProps,
      isUpdateModularLoading: false,
      isStepCompleted: true,
      isPosEkycAgent: false,
    };
    renderApp(mockProps);
    expect(screen.getByText(/Choose Suitable Devices for your merchant/i)).toBeInTheDocument();
    expect(screen.getByText(/Android Smart Pos/i)).toBeInTheDocument();
    expect(screen.getByText(/Android Smart mini Pos/i)).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: /proceed to cart/i,
      }),
    ).toBeEnabled();
    expect(screen.getByRole('button', { name: /Add device/i })).not.toBeEnabled();
    expect(screen.getByRole('button', { name: /Add another device/i })).not.toBeEnabled();
  });
});
