import React from 'react';
import DeviceCard from '../DeviceCard';
import { TestDeviceConfig } from 'apps/pos/src/services/mocks/fixtures/deviceSelection';
import { render, screen } from 'apps/pos/src/services/test/test-utils';

jest.mock(
  'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceOrdering/DeviceSelectionCatalog/AddDeviceToCart',
  () => ({
    __esModule: true,
    default: () => <div>AddDeviceToCart</div>,
  }),
);

const defaultProps = {
  deviceConfig: TestDeviceConfig,
  isUpdateModularLoading: false,
  addedDevices: [],
  handleModularUpdate: jest.fn(),
};

const renderApp = (props = {}) => {
  const initProps = { ...defaultProps, ...props };
  return render(<DeviceCard {...initProps} />);
};

describe('DeviceCard', () => {
  test('should render device card on screen', () => {
    renderApp();
    expect(screen.getByText('Test Device')).toBeInTheDocument();
    expect(screen.getByAltText('device icon')).toBeInTheDocument();
    expect(screen.getByText('AddDeviceToCart')).toBeInTheDocument();
  });
});
